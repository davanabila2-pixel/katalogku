<?php

namespace App\Http\Controllers\Api\Superadmin;

use App\Http\Controllers\Concerns\HandlesImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequest;
use App\Models\Store;
use App\Services\AuditLogger;
use App\Services\SlugService;
use App\Services\StoreAdminService;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    use HandlesImages;

    /**
     * Daftar seluruh toko (JSON) untuk superadmin tanpa reload.
     * Filter: q, status.
     */
    public function index(Request $request)
    {
        $stores = Store::query()
            ->withCount(['products', 'categories', 'purchaseBatches'])
            ->when($request->q, fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $stores->getCollection()->map(fn (Store $store) => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'url' => $store->url(),
                'products_count' => (int) $store->products_count,
                'categories_count' => (int) $store->categories_count,
                'purchase_batches_count' => (int) $store->purchase_batches_count,
                'status' => $store->status,
                'show_url' => route('superadmin.stores.show', $store),
                'form_data_url' => route('api.superadmin.stores.form_data', $store),
            ])->values(),
            'meta' => [
                'current_page' => $stores->currentPage(),
                'last_page' => $stores->lastPage(),
                'per_page' => $stores->perPage(),
                'total' => $stores->total(),
                'from' => $stores->firstItem(),
                'to' => $stores->lastItem(),
            ],
        ]);
    }

    /**
     * Data untuk modal "Edit toko" (nilai toko + email admin utama).
     */
    public function formData(Store $store)
    {
        return response()->json([
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'description' => $store->description,
                'contact_information' => $store->contact_information,
                'address' => $store->address,
                'status' => $store->status,
                'logo_url' => $store->logoUrl(),
                'update_url' => route('api.superadmin.stores.update', $store),
            ],
            'admin_email' => $store->primaryAdmin()?->email,
        ]);
    }

    /**
     * Buat toko baru tanpa reload halaman (termasuk penunjukan admin utama).
     * Aturan sama dengan versi web (StoreRequest + SlugService).
     */
    public function store(StoreRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? SlugService::uniqueStoreSlug($data['name']);

        $store = Store::create(collect($data)->except(['logo', 'qr_image', 'admin_email', 'admin_name'])->toArray());

        if ($request->hasFile('logo')) {
            $store->update(['logo_path' => $this->storeImage($store, $request->file('logo'))]);
        }

        if ($request->hasFile('qr_image')) {
            $store->update(['qr_image_path' => $this->storeImage($store, $request->file('qr_image'))]);
        }

        if (! empty($data['admin_email'])) {
            app(StoreAdminService::class)->assignPrimaryAdmin($store, $data['admin_email'], $data['admin_name'] ?? null);
        }

        AuditLogger::log('store.created', $store, "Toko {$store->name} dibuat.", ['url' => $store->url()], $request->user(), $store->id);

        return response()->json([
            'message' => "Toko dibuat. URL katalog: {$store->url()}",
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'url' => $store->url(),
            ],
        ], 201);
    }

    /**
     * Perbarui toko tanpa reload halaman.
     */
    public function update(StoreRequest $request, Store $store)
    {
        $data = $request->validated();

        if (empty($data['slug'])) {
            unset($data['slug']);
        }

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $this->storeImage($store, $request->file('logo'), $store->logo_path);
        }
        unset($data['logo']);

        if ($request->hasFile('qr_image')) {
            $data['qr_image_path'] = $this->storeImage($store, $request->file('qr_image'), $store->qr_image_path);
        }
        unset($data['qr_image']);

        $slugChanged = isset($data['slug']) && $data['slug'] !== $store->slug;
        $store->update(collect($data)->except(['admin_email', 'admin_name'])->toArray());

        if (! empty($data['admin_email'])) {
            app(StoreAdminService::class)->assignPrimaryAdmin($store, $data['admin_email'], $data['admin_name'] ?? null);
        }

        AuditLogger::log('store.updated', $store, "Toko {$store->name} diperbarui.", $slugChanged ? ['slug_baru' => $store->slug] : null, $request->user(), $store->id);

        return response()->json([
            'message' => 'Toko diperbarui.'.($slugChanged ? ' Slug berubah — tautan lama dialihkan (301).' : ''),
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'url' => $store->url(),
            ],
        ]);
    }
}
