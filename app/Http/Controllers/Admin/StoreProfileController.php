<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesImages;
use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\AuditLogger;
use App\Services\QrisService;
use App\Support\Couriers;
use App\Services\SlugService;
use Illuminate\Http\Request;

class StoreProfileController extends Controller
{
    use HandlesImages;

    public function edit(Store $store)
    {
        $this->authorize('update', $store);
        $googleMapsApiKey = config('services.google_maps.key');

        return view('admin.profile', compact('store', 'googleMapsApiKey'));
    }

    public function update(Request $request, Store $store, QrisService $qris)
    {
        $this->authorize('update', $store);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slogan' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'unique:stores,slug,'.$store->id,
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'contact_information' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
            'remove_banner' => ['nullable', 'boolean'],
            'qr_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'qr_account_name' => ['nullable', 'string', 'max:255'],
            'remove_qr' => ['nullable', 'boolean'],
            'qris_payload' => ['nullable', 'string', 'max:2000'],
            'couriers_submitted' => ['nullable', 'boolean'],
            'active_couriers' => ['nullable', 'array'],
            'active_couriers.*' => ['string', 'max:30'],
            'hide_component_products' => ['nullable', 'boolean'],
        ]);

        $data['hide_component_products'] = (bool) ($request->hide_component_products ?? false);

        if (! empty($data['slug'])) {
            if (SlugService::isReserved(strtolower($data['slug'])) && $data['slug'] !== $store->slug) {
                return back()->withErrors(['slug' => 'Slug ini dicadangkan untuk URL sistem.'])->withInput();
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

        if ($request->boolean('remove_banner')) {
            if ($store->banner_path) {
                $this->deleteImage($store, $store->banner_path);
            }
            $data['banner_path'] = null;
        } elseif ($request->hasFile('banner')) {
            $data['banner_path'] = $this->storeBannerImage($store, $request->file('banner'), $store->banner_path);
        }
        unset($data['banner'], $data['remove_banner']);

        // Master ekspedisi: simpan ekspedisi yang diaktifkan (checkbox kosong semua = tidak ada ekspedisi aktif).
        if ($request->boolean('couriers_submitted')) {
            $data['active_couriers'] = Couriers::sanitize($request->input('active_couriers', []));
        } else {
            unset($data['active_couriers']);
        }
        unset($data['couriers_submitted']);

        $manualPayload = trim((string) ($data['qris_payload'] ?? ''));
        unset($data['qris_payload']);

        if ($request->boolean('remove_qr')) {
            if ($store->qr_image_path) {
                $this->deleteImage($store, $store->qr_image_path);
            }
            $data['qr_image_path'] = null;
            $data['qris_payload'] = null;
        } else {
            if ($request->hasFile('qr_image')) {
                $decoded = $qris->extractFromImage($request->file('qr_image')->getRealPath());
                $data['qr_image_path'] = $this->storeImage($store, $request->file('qr_image'), $store->qr_image_path);
                // Gambar baru: pakai hasil baca otomatis; bila tidak terbaca, pakai isian manual (jika ada).
                $data['qris_payload'] = $decoded ?? ($manualPayload !== '' ? $qris->normalize($manualPayload) : null);
            } elseif ($manualPayload !== '') {
                $normalized = $qris->normalize($manualPayload);
                if ($normalized === null) {
                    return back()->withErrors(['qris_payload' => 'Teks QRIS tidak valid. Pastikan teks diawali 000201 dan utuh (CRC cocok).'])->withInput();
                }
                $data['qris_payload'] = $normalized;
            }
        }
        unset($data['qr_image'], $data['remove_qr']);

        $slugChanged = isset($data['slug']) && $data['slug'] !== $store->slug;
        $store->update($data);

        AuditLogger::log('store.updated', $store, "Profil toko {$store->name} diperbarui.", $slugChanged ? ['slug_baru' => $store->slug] : null, $request->user(), $store->id);

        return redirect()->route('admin.profile.edit', $store)
            ->with('status', 'Profil toko diperbarui.'.
                ($slugChanged ? ' Slug berubah — tautan lama otomatis dialihkan (301).' : ''));
    }
}
