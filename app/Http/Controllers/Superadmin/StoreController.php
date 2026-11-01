<?php

namespace App\Http\Controllers\Superadmin;

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

    public function index(Request $request)
    {
        // Daftar dimuat lewat API JSON (Api\Superadmin\StoreController)
        // agar filter/pencarian/pagination berjalan tanpa reload halaman.
        return view('superadmin.stores.index');
    }

    public function create()
    {
        return view('superadmin.stores.form', ['store' => new Store(['status' => 'active'])]);
    }

    public function store(StoreRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? SlugService::uniqueStoreSlug($data['name']);

        $store = Store::create(collect($data)->except(['logo', 'banner', 'qr_image', 'admin_email', 'admin_name'])->toArray());

        if ($request->hasFile('logo')) {
            $store->update(['logo_path' => $this->storeImage($store, $request->file('logo'))]);
        }

        if ($request->hasFile('banner')) {
            $store->update(['banner_path' => $this->storeBannerImage($store, $request->file('banner'))]);
        }

        if ($request->hasFile('qr_image')) {
            $store->update(['qr_image_path' => $this->storeImage($store, $request->file('qr_image'))]);
        }

        if (! empty($data['admin_email'])) {
            app(StoreAdminService::class)->assignPrimaryAdmin($store, $data['admin_email'], $data['admin_name'] ?? null);
        }

        AuditLogger::log('store.created', $store, "Toko {$store->name} dibuat.", ['url' => $store->url()], $request->user(), $store->id);

        return redirect()->route('superadmin.stores.index')->with('status', "Toko dibuat. URL katalog: {$store->url()}");
    }

    public function show(Store $store)
    {
        $store->load(['users', 'slugHistories']);
        $stats = [
            'products' => $store->products()->count(),
            'categories' => $store->categories()->count(),
            'batches' => $store->purchaseBatches()->count(),
            'stock' => (int) $store->products()->sum('stock'),
        ];

        return view('superadmin.stores.show', compact('store', 'stats'));
    }

    public function edit(Store $store)
    {
        return view('superadmin.stores.form', compact('store'));
    }

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

        if ($request->hasFile('banner')) {
            $data['banner_path'] = $this->storeBannerImage($store, $request->file('banner'), $store->banner_path);
        }
        unset($data['banner']);

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

        return redirect()->route('superadmin.stores.show', $store)
            ->with('status', 'Toko diperbarui.'.($slugChanged ? ' Slug berubah — tautan lama dialihkan (301).' : ''));
    }

    public function destroy(Request $request, Store $store)
    {
        $name = $store->name;
        $store->delete();
        AuditLogger::log('store.deleted', null, "Toko {$name} dinonaktifkan (soft delete).", null, $request->user(), $store->id);

        return redirect()->route('superadmin.stores.index')->with('status', 'Toko diarsipkan.');
    }
}
