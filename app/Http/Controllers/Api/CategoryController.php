<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\HandlesImages;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Store;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use HandlesImages;

    /**
     * Daftar kategori (JSON) untuk tabel admin tanpa reload.
     * Filter: q, status (active/inactive).
     */
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
            ->paginate(15);

        return response()->json([
            'data' => $categories->getCollection()->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'parent' => $category->parent?->name,
                'children_count' => (int) $category->children_count,
                'products_count' => (int) $category->products_count,
                'sort_order' => (int) $category->sort_order,
                'is_active' => (bool) $category->is_active,
                'edit_url' => route('admin.categories.edit', [$store, $category]),
                'destroy_url' => route('api.categories.destroy', [$store, $category]),
            ])->values(),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'from' => $categories->firstItem(),
                'to' => $categories->lastItem(),
            ],
        ]);
    }

    /**
     * Hapus kategori tanpa reload halaman.
     * Aturan sama dengan versi web: kategori yang masih dipakai
     * (punya subkategori/produk) tidak boleh dihapus.
     */
    public function destroy(Request $request, Store $store, Category $category)
    {
        $this->ensureSameStore($store, $category);
        $this->authorize('delete', $category);

        if ($category->children()->exists() || $category->products()->exists()) {
            return response()->json([
                'message' => 'Kategori memiliki subkategori/produk. Pindahkan atau arsipkan dulu — penghapusan tidak boleh merusak data.',
            ], 422);
        }

        $name = $category->name;
        if ($category->image_path) {
            $this->deleteImage($store, $category->image_path);
        }
        $category->delete();
        AuditLogger::log('category.deleted', null, "Kategori {$name} dihapus.", ['store_id' => $store->id], $request->user(), $store->id);

        return response()->json([
            'message' => 'Kategori dihapus.',
            'action' => 'deleted',
        ]);
    }

    protected function ensureSameStore(Store $store, Category $category): void
    {
        if ((int) $category->store_id !== (int) $store->id) {
            abort(404);
        }
    }
}
