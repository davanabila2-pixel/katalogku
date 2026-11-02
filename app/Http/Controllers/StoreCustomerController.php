<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Store;
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

        $googleMapsApiKey = config('services.google_maps.key');

        $seo = [
            'title' => "Alamat Saya: {$store->name}",
            'description' => "Pengaturan alamat pengiriman dan titik lokasi Google Maps di {$store->name}.",
            'canonical' => route('store.address', $store->slug),
            'image' => $store->logoUrl(),
        ];

        return view('public.customer.address', compact('store', 'user', 'profile', 'googleMapsApiKey', 'seo'));
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

        StoreCustomerProfile::updateOrCreate(
            ['store_id' => $store->id, 'user_id' => $user->id],
            array_merge(['name' => $user->name], $data)
        );

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

