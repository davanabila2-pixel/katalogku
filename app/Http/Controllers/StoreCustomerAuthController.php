<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreCustomerProfile;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StoreCustomerAuthController extends Controller
{
    private function getStore(string $storeSlug): Store
    {
        return Store::where('slug', $storeSlug)->where('status', 'active')->firstOrFail();
    }

    public function showLogin(string $storeSlug)
    {
        $store = $this->getStore($storeSlug);

        if (Auth::check()) {
            return redirect()->route('store.show', $store->slug);
        }

        $seo = [
            'title' => "Masuk ke {$store->name}",
            'description' => "Masuk ke akun pelanggan {$store->name}.",
            'canonical' => route('store.login', $store->slug),
            'image' => $store->logoUrl(),
        ];

        return view('public.customer.login', compact('store', 'seo'));
    }

    public function login(Request $request, string $storeSlug)
    {
        $store = $this->getStore($storeSlug);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi yang Anda masukkan salah.',
            ]);
        }

        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Akun dinonaktifkan. Silakan hubungi admin.',
            ]);
        }

        $request->session()->regenerate();

        AuditLogger::log(
            'auth.customer.login',
            $user,
            "Pelanggan {$user->email} masuk ke toko {$store->name}.",
            ['store_slug' => $store->slug],
            $user,
            $store->id
        );

        return redirect()->intended(route('store.show', $store->slug))
            ->with('status', "Selamat datang kembali di {$store->name}!");
    }

    public function showRegister(string $storeSlug)
    {
        $store = $this->getStore($storeSlug);

        if (Auth::check()) {
            return redirect()->route('store.show', $store->slug);
        }

        $seo = [
            'title' => "Daftar Pelanggan {$store->name}",
            'description' => "Daftar akun pelanggan baru di {$store->name}.",
            'canonical' => route('store.register', $store->slug),
            'image' => $store->logoUrl(),
        ];

        return view('public.customer.register', compact('store', 'seo'));
    }

    public function register(Request $request, string $storeSlug)
    {
        $store = $this->getStore($storeSlug);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:25'],
            'address' => ['nullable', 'string', 'max:1000'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($data, $store) {
            // Cek apakah user dengan email ini sudah ada di sistem
            $existingUser = User::where('email', $data['email'])->first();

            if ($existingUser) {
                // Jika sudah ada tapi belum diverifikasi kata sandinya:
                if (! Hash::check($data['password'], $existingUser->password)) {
                    throw ValidationException::withMessages([
                        'email' => 'Email ini sudah terdaftar di platform. Silakan masuk atau gunakan kata sandi yang sesuai.',
                    ]);
                }
                $user = $existingUser;
            } else {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'role' => 'user', // Pelanggan biasa
                    'is_active' => true,
                ]);
            }

            // Simpan profil pelanggan khusus untuk toko ini
            StoreCustomerProfile::updateOrCreate(
                [
                    'store_id' => $store->id,
                    'user_id' => $user->id,
                ],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'address' => $data['address'] ?? null,
                ]
            );

            return $user;
        });

        Auth::login($user, true);
        $request->session()->regenerate();

        AuditLogger::log(
            'auth.customer.register',
            $user,
            "Pelanggan {$user->email} terdaftar di toko {$store->name}.",
            ['store_slug' => $store->slug],
            $user,
            $store->id
        );

        return redirect()->route('store.show', $store->slug)
            ->with('status', "Selamat datang di {$store->name}! Akun pelanggan Anda berhasil dibuat.");
    }
}
