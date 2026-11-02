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
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        StoreCustomerProfile::updateOrCreate(
            ['store_id' => $store->id, 'user_id' => $user->id],
            $data
        );

        return redirect()->route('store.profile', $store->slug)
            ->with('status', "Profil Anda di {$store->name} berhasil diperbarui.");
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

