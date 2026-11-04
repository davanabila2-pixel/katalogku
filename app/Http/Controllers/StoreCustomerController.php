<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Store;
use App\Models\StoreCustomerAddress;
use App\Models\StoreCustomerProfile;
use Illuminate\Http\Request;

class StoreCustomerController extends Controller
{
    private function getStore(string $storeSlug): Store
    {
        return Store::where('slug', $storeSlug)->where('status', 'active')->firstOrFail();
    }

    public function profile(string $storeSlug)
    {
        $store = $this->getStore($storeSlug);
        $user = auth()->user();

        $profile = StoreCustomerProfile::firstOrCreate(
            ['store_id' => $store->id, 'user_id' => $user->id],
            [
                'name' => $user->name,
                'phone' => null,
                'address' => null,
            ]
        );

        $seo = [
            'title' => "Profil Saya: {$store->name}",
            'description' => "Pengaturan data profil pelanggan di {$store->name}.",
            'canonical' => route('store.profile', $store->slug),
            'image' => $store->logoUrl(),
        ];

        return view('public.customer.profile', compact('store', 'user', 'profile', 'seo'));
    }

    public function updateProfile(Request $request, string $storeSlug)
    {
        $store = $this->getStore($storeSlug);
        $user = auth()->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:25'],
            'address' => ['nullable', 'string', 'max:1000'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        StoreCustomerProfile::updateOrCreate(
            ['store_id' => $store->id, 'user_id' => $user->id],
            $data
        );

        return redirect()->route('store.profile', $store->slug)
            ->with('status', "Profil Anda di {$store->name} berhasil diperbarui.");
    }

    public function address(string $storeSlug)
    {
        $store = $this->getStore($storeSlug);
        $user = auth()->user();

        $profile = StoreCustomerProfile::firstOrCreate(
            ['store_id' => $store->id, 'user_id' => $user->id],
            [
                'name' => $user->name,
                'phone' => null,
                'address' => null,
            ]
        );

        $addresses = StoreCustomerAddress::where('store_id', $store->id)
            ->where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        if ($addresses->isEmpty() && ! empty($profile->address)) {
            $created = StoreCustomerAddress::create([
                'store_id' => $store->id,
                'user_id' => $user->id,
                'label' => 'Alamat Utama',
                'recipient_name' => $profile->name ?: $user->name,
                'phone' => $profile->phone ?: '',
                'address' => $profile->address,
                'postal_code' => $profile->postal_code,
                'city' => $profile->city,
                'district' => $profile->district,
                'latitude' => $profile->latitude,
                'longitude' => $profile->longitude,
                'notes' => $profile->notes,
                'is_default' => true,
            ]);
            $addresses = collect([$created]);
        }

        $googleMapsApiKey = config('services.google_maps.key');

        $seo = [
            'title' => "Alamat Saya: {$store->name}",
            'description' => "Pengaturan daftar alamat pengiriman dan titik lokasi Google Maps di {$store->name}.",
            'canonical' => route('store.address', $store->slug),
            'image' => $store->logoUrl(),
        ];

        return view('public.customer.address', compact('store', 'user', 'profile', 'addresses', 'googleMapsApiKey', 'seo'));
    }

    public function storeAddress(Request $request, string $storeSlug)
    {
        $store = $this->getStore($storeSlug);
        $user = auth()->user();

        $data = $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['nullable'],
        ]);

        $hasAddresses = StoreCustomerAddress::where('store_id', $store->id)
            ->where('user_id', $user->id)
            ->exists();

        $isDefault = ! $hasAddresses || ! empty($data['is_default']);

        if ($isDefault) {
            StoreCustomerAddress::where('store_id', $store->id)
                ->where('user_id', $user->id)
                ->update(['is_default' => false]);
        }

        $address = StoreCustomerAddress::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'label' => $data['label'],
            'recipient_name' => $data['recipient_name'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'postal_code' => $data['postal_code'] ?? null,
            'city' => $data['city'] ?? null,
            'district' => $data['district'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_default' => $isDefault,
        ]);

        if ($isDefault) {
            StoreCustomerProfile::updateOrCreate(
                ['store_id' => $store->id, 'user_id' => $user->id],
                [
                    'name' => $data['recipient_name'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'postal_code' => $data['postal_code'] ?? null,
                    'city' => $data['city'] ?? null,
                    'district' => $data['district'] ?? null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]
            );
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Alamat baru berhasil ditambahkan.',
                'address' => $address,
            ]);
        }

        return redirect()->route('store.address', $store->slug)
            ->with('status', 'Alamat baru berhasil ditambahkan.');
    }

    public function updateAddressItem(Request $request, string $storeSlug, int $addressId)
    {
        $store = $this->getStore($storeSlug);
        $user = auth()->user();

        $address = StoreCustomerAddress::where('store_id', $store->id)
            ->where('user_id', $user->id)
            ->where('id', $addressId)
            ->firstOrFail();

        $data = $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['nullable'],
        ]);

        $makeDefault = ! empty($data['is_default']) || $address->is_default;

        if ($makeDefault) {
            StoreCustomerAddress::where('store_id', $store->id)
                ->where('user_id', $user->id)
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        $address->update([
            'label' => $data['label'],
            'recipient_name' => $data['recipient_name'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'postal_code' => $data['postal_code'] ?? null,
            'city' => $data['city'] ?? null,
            'district' => $data['district'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_default' => $makeDefault,
        ]);

        if ($makeDefault) {
            StoreCustomerProfile::updateOrCreate(
                ['store_id' => $store->id, 'user_id' => $user->id],
                [
                    'name' => $data['recipient_name'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'postal_code' => $data['postal_code'] ?? null,
                    'city' => $data['city'] ?? null,
                    'district' => $data['district'] ?? null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]
            );
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Alamat berhasil diperbarui.',
                'address' => $address,
            ]);
        }

        return redirect()->route('store.address', $store->slug)
            ->with('status', 'Alamat berhasil diperbarui.');
    }

    public function deleteAddress(Request $request, string $storeSlug, int $addressId)
    {
        $store = $this->getStore($storeSlug);
        $user = auth()->user();

        $address = StoreCustomerAddress::where('store_id', $store->id)
            ->where('user_id', $user->id)
            ->where('id', $addressId)
            ->firstOrFail();

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $nextDefault = StoreCustomerAddress::where('store_id', $store->id)
                ->where('user_id', $user->id)
                ->first();
            if ($nextDefault) {
                $nextDefault->update(['is_default' => true]);
                StoreCustomerProfile::updateOrCreate(
                    ['store_id' => $store->id, 'user_id' => $user->id],
                    [
                        'name' => $nextDefault->recipient_name,
                        'phone' => $nextDefault->phone,
                        'address' => $nextDefault->address,
                        'postal_code' => $nextDefault->postal_code,
                        'city' => $nextDefault->city,
                        'district' => $nextDefault->district,
                        'latitude' => $nextDefault->latitude,
                        'longitude' => $nextDefault->longitude,
                        'notes' => $nextDefault->notes,
                    ]
                );
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Alamat berhasil dihapus.',
            ]);
        }

        return redirect()->route('store.address', $store->slug)
            ->with('status', 'Alamat berhasil dihapus.');
    }

    public function setDefaultAddress(Request $request, string $storeSlug, int $addressId)
    {
        $store = $this->getStore($storeSlug);
        $user = auth()->user();

        $address = StoreCustomerAddress::where('store_id', $store->id)
            ->where('user_id', $user->id)
            ->where('id', $addressId)
            ->firstOrFail();

        StoreCustomerAddress::where('store_id', $store->id)
            ->where('user_id', $user->id)
            ->update(['is_default' => false]);

        $address->update(['is_default' => true]);

        StoreCustomerProfile::updateOrCreate(
            ['store_id' => $store->id, 'user_id' => $user->id],
            [
                'name' => $address->recipient_name,
                'phone' => $address->phone,
                'address' => $address->address,
                'postal_code' => $address->postal_code,
                'city' => $address->city,
                'district' => $address->district,
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,
                'notes' => $address->notes,
            ]
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Alamat utama berhasil diubah.',
                'address' => $address,
            ]);
        }

        return redirect()->route('store.address', $store->slug)
            ->with('status', 'Alamat utama berhasil diubah.');
    }

    public function updateAddress(Request $request, string $storeSlug)
    {
        $store = $this->getStore($storeSlug);
        $user = auth()->user();

        $data = $request->validate([
            'address' => ['required', 'string', 'max:1000'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $profile = StoreCustomerProfile::updateOrCreate(
            ['store_id' => $store->id, 'user_id' => $user->id],
            array_merge(['name' => $user->name], $data)
        );

        $defaultAddress = StoreCustomerAddress::where('store_id', $store->id)
            ->where('user_id', $user->id)
            ->where('is_default', true)
            ->first();

        if ($defaultAddress) {
            $defaultAddress->update([
                'address' => $data['address'],
                'postal_code' => $data['postal_code'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'city' => $data['city'] ?? null,
                'district' => $data['district'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        } else {
            StoreCustomerAddress::create([
                'store_id' => $store->id,
                'user_id' => $user->id,
                'label' => 'Alamat Utama',
                'recipient_name' => $user->name,
                'phone' => $profile->phone ?: '',
                'address' => $data['address'],
                'postal_code' => $data['postal_code'] ?? null,
                'city' => $data['city'] ?? null,
                'district' => $data['district'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_default' => true,
            ]);
        }

        return redirect()->route('store.address', $store->slug)
            ->with('status', 'Alamat pengiriman Anda berhasil diperbarui.');
    }

    public function orders(string $storeSlug)
    {
        $store = $this->getStore($storeSlug);
        $user = auth()->user();

        $orders = Order::where('store_id', $store->id)
            ->where('user_id', $user->id)
            ->with(['product'])
            ->latest()
            ->paginate(10);

        $seo = [
            'title' => "Pesanan Saya: {$store->name}",
            'description' => "Daftar riwayat pesanan Anda di toko {$store->name}.",
            'canonical' => route('store.orders', $store->slug),
            'image' => $store->logoUrl(),
        ];

        return view('public.customer.orders', compact('store', 'user', 'orders', 'seo'));
    }
}

