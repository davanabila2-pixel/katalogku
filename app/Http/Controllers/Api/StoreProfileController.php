<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\HandlesImages;
use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\AuditLogger;
use App\Services\SlugService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StoreProfileController extends Controller
{
    use HandlesImages;

    /**
     * Simpan profil toko tanpa reload halaman.
     * Aturan sama persis dengan versi web (StoreProfileController):
     * slug opsional + proteksi slug cadangan & izin ubah slug, logo opsional,
     * toggle sembunyikan komponen setelan.
     */
    public function update(Request $request, Store $store)
    {
        $this->authorize('update', $store);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'unique:stores,slug,'.$store->id,
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'contact_information' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'hide_component_products' => ['nullable', 'boolean'],
        ]);

        $data['hide_component_products'] = (bool) ($request->hide_component_products ?? false);

        if (! empty($data['slug'])) {
            if (SlugService::isReserved(strtolower($data['slug'])) && $data['slug'] !== $store->slug) {
                throw ValidationException::withMessages(['slug' => 'Slug ini dicadangkan untuk URL sistem.']);
            }
            if ($data['slug'] !== $store->slug) {
                $this->authorize('updateSlug', $store);
            }
        } else {
            unset($data['slug']);
        }

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $this->storeImage($store, $request->file('logo'), $store->logo_path);
        }
        unset($data['logo']);

        $slugChanged = isset($data['slug']) && $data['slug'] !== $store->slug;
        $store->update($data);

        AuditLogger::log('store.updated', $store, "Profil toko {$store->name} diperbarui.", $slugChanged ? ['slug_baru' => $store->slug] : null, $request->user(), $store->id);

        return response()->json([
            'message' => 'Profil toko diperbarui.'.
                ($slugChanged ? ' Slug berubah — tautan lama otomatis dialihkan (301).' : ''),
            'store' => [
                'name' => $store->name,
                'slug' => $store->slug,
                'logo_url' => $store->logoUrl(),
            ],
        ]);
    }
}
