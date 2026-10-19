<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Models\Store;
use App\Services\AuditLogger;
use App\Services\SlugService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use HandlesImages;

    public function index(Request $request, Store $store)
    {
        $categories = Category::forStore($store->id)
            ->with(['parent'])
            ->withCount(['children', 'products'])
            ->when($request->q, fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.categories.index', compact('store', 'categories'));
    }

    public function create(Store $store)
    {
        $this->authorize('create', [Category::class, $store->id]);
        $parents = Category::forStore($store->id)->roots()->orderBy('name')->get();

        return view('admin.categories.form', compact('store', 'parents') + ['category' => new Category(['store_id' => $store->id])]);
    }

    public function store(CategoryRequest $request, Store $store)
    {
        $data = $request->validated();
        $data['store_id'] = $store->id;
        $data['slug'] = $data['slug'] ?? SlugService::uniqueCategorySlug($store->id, $data['name']);
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeImage($store, $request->file('image'));
        }
        unset($data['image']);

        $category = Category::create($data);
        AuditLogger::log('category.created', $category, "Kategori {$category->name} dibuat.", null, $request->user(), $store->id);

        return redirect()->route('admin.categories.index', $store)->with('status', 'Kategori dibuat.');
    }

    public function edit(Store $store, Category $category)
    {
        $this->ensureSameStore($store, $category);
        $this->authorize('update', $category);
        $parents = Category::forStore($store->id)
            ->where('id', '!=', $category->id)
            ->orderBy('name')
            ->get();

        return view('admin.categories.form', compact('store', 'category', 'parents'));
    }

    public function update(CategoryRequest $request, Store $store, Category $category)
    {
        $this->ensureSameStore($store, $category);
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', false);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeImage($store, $request->file('image'), $category->image_path);
        }
        unset($data['image']);
        unset($data['store_id']);

        $category->update($data);
        AuditLogger::log('category.updated', $category, "Kategori {$category->name} diperbarui.", null, $request->user(), $store->id);

        return redirect()->route('admin.categories.index', $store)->with('status', 'Kategori diperbarui.');
    }

    public function destroy(Request $request, Store $store, Category $category)
    {
        $this->ensureSameStore($store, $category);
        $this->authorize('delete', $category);

        if ($category->children()->exists() || $category->products()->exists()) {
            return back()->withErrors(['category' => 'Kategori memiliki subkategori/produk. Pindahkan atau arsipkan dulu — penghapusan tidak boleh merusak data.']);
        }

        $name = $category->name;
        if ($category->image_path) {
            $this->deleteImage($store, $category->image_path);
        }
        $category->delete();
        AuditLogger::log('category.deleted', null, "Kategori {$name} dihapus.", ['store_id' => $store->id], $request->user(), $store->id);

        return redirect()->route('admin.categories.index', $store)->with('status', 'Kategori dihapus.');
    }

    protected function ensureSameStore(Store $store, Category $category): void
    {
        if ((int) $category->store_id !== (int) $store->id) {
            abort(404);
        }
    }
}
