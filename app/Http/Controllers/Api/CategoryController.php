<?php

namespace App\Http\Controllers\Api;

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
            ->when($request->parent_id === 'root', fn ($q) => $q->whereNull('parent_id'))
            ->when(is_numeric($request->parent_id), fn ($q) => $q->where('parent_id', (int) $request->parent_id))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        $adminCatPrefix = route('admin.categories.index', $store);
        $apiCatPrefix = route('api.categories.index', $store);

        return response()->json([
            'data' => $categories->getCollection()->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'parent' => $category->parent?->name,
                'children_count' => (int) $category->children_count,
                'products_count' => (int) $category->products_count,
                'sort_order' => (int) $category->sort_order,
                'is_active' => (bool) $category->is_active,
                'edit_url' => "{$adminCatPrefix}/{$category->id}/edit",
                'update_url' => "{$apiCatPrefix}/{$category->id}",
                'destroy_url' => "{$apiCatPrefix}/{$category->id}",
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
     * Struktur pohon kategori lengkap (berdasarkan hirarki induk & subkategori).
     * Mengelompokkan kategori utama (roots) bersama seluruh subkategori di bawahnya.
     */
    public function tree(Request $request, Store $store)
    {
        $status = $request->status;
        $q = trim((string) $request->q);

        $categories = Category::forStore($store->id)
            ->withCount('products')
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $nodes = [];
        $adminCatPrefix = route('admin.categories.index', $store);
        $apiCatPrefix = route('api.categories.index', $store);

        foreach ($categories as $cat) {
            $nodes[$cat->id] = [
                'id' => $cat->id,
                'parent_id' => $cat->parent_id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'description' => $cat->description,
                'sort_order' => (int) $cat->sort_order,
                'is_active' => (bool) $cat->is_active,
                'products_count' => (int) $cat->products_count,
                'edit_url' => "{$adminCatPrefix}/{$cat->id}/edit",
                'update_url' => "{$apiCatPrefix}/{$cat->id}",
                'destroy_url' => "{$apiCatPrefix}/{$cat->id}",
                'matches_search' => $q !== '' && stripos($cat->name, $q) !== false,
                'children' => [],
            ];
        }

        $roots = [];
        $orphans = [];

        foreach ($nodes as $id => &$node) {
            if ($node['parent_id'] !== null) {
                if (isset($nodes[$node['parent_id']])) {
                    $nodes[$node['parent_id']]['children'][] = &$node;
                } else {
                    $orphans[] = &$node;
                }
            } else {
                $roots[] = &$node;
            }
        }
        unset($node);

        if ($q !== '') {
            $filterMatches = function (&$branch) use (&$filterMatches) {
                $hasMatch = false;
                foreach ($branch as $key => &$item) {
                    $childHasMatch = false;
                    if (!empty($item['children'])) {
                        $childHasMatch = $filterMatches($item['children']);
                    }
                    if ($item['matches_search'] || $childHasMatch) {
                        $item['expanded'] = true;
                        $hasMatch = true;
                    } else {
                        unset($branch[$key]);
                    }
                }
                $branch = array_values($branch);
                return $hasMatch;
            };

            $filterMatches($roots);
            $filterMatches($orphans);
        }

        $enrichTree = function (&$branch, $depth = 1) use (&$enrichTree) {
            $totalSub = 0;
            $totalProds = 0;
            foreach ($branch as &$item) {
                $item['depth'] = $depth;
                $item['direct_children_count'] = count($item['children']);
                $subStats = $enrichTree($item['children'], $depth + 1);
                $item['total_descendants_count'] = $item['direct_children_count'] + $subStats['subs'];
                $item['total_products_count'] = $item['products_count'] + $subStats['prods'];
                $totalSub += $item['total_descendants_count'];
                $totalProds += $item['total_products_count'];
            }
            return ['subs' => $totalSub, 'prods' => $totalProds];
        };

        $enrichTree($roots);
        $enrichTree($orphans);

        return response()->json([
            'data' => $roots,
            'orphans' => $orphans,
            'meta' => [
                'total_roots' => count($roots),
                'total_categories' => count($nodes),
            ],
        ]);
    }

    /**
     * Pilihan kategori induk untuk form modal.
     * Meniru form web: saat menambah hanya kategori utama (roots) yang
     * ditawarkan; saat mengedit (ada ?exclude={id}) semua kategori toko
     * kecuali kategori itu sendiri.
     */
    public function options(Request $request, Store $store)
    {
        $categories = Category::forStore($store->id)
            ->when(
                $request->filled('exclude'),
                fn ($q) => $q->where('id', '!=', (int) $request->exclude),
                fn ($q) => $request->boolean('all_parents')
                    ? $q->where(function ($sub) {
                        $sub->whereNull('parent_id')
                            ->orWhereIn('parent_id', function ($r) {
                                $r->select('id')->from('categories')->whereNull('parent_id');
                            });
                    })
                    : $q->roots()
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        return response()->json([
            'data' => $categories->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'parent_id' => $category->parent_id,
            ])->values(),
        ]);
    }

    /**
     * Simpan kategori baru tanpa reload halaman.
     * Aturan sama dengan versi web (slug otomatis, gambar opsional ke Drive).
     */
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

        return response()->json([
            'message' => 'Kategori dibuat.',
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
        ], 201);
    }

    /**
     * Perbarui kategori tanpa reload halaman.
     */
    public function update(CategoryRequest $request, Store $store, Category $category)
    {
        $this->ensureSameStore($store, $category);
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', false);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeImage($store, $request->file('image'), $category->image_path);
        }
        unset($data['image'], $data['store_id']);

        $category->update($data);
        AuditLogger::log('category.updated', $category, "Kategori {$category->name} diperbarui.", null, $request->user(), $store->id);

        return response()->json([
            'message' => 'Kategori diperbarui.',
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
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
