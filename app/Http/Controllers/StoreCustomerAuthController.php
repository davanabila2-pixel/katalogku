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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class StoreCustomerAuthController extends Controller
{
    private function getStore(string $storeSlug): Store
    {
        return Store::where('slug', $storeSlug)->where('status', 'active')->firstOrFail();
    }

    public function showLogin(Request $request, string $storeSlug)
    {
        $store = $this->getStore($storeSlug);

        if (Auth::check()) {
            return redirect()->route('store.show', $store->slug);
        }

        if ($request->filled('return_url')) {
            $returnUrl = $request->query('return_url');
            if (str_starts_with($returnUrl, '/') && ! str_starts_with($returnUrl, '//')) {
                $request->session()->put('url.intended', $returnUrl);
            }
        }

        $showcaseProducts = $store->products()
            ->where('status', 'active')
            ->latest()
            ->take(4)
            ->get();

        $seo = [
            'title' => "Masuk ke {$store->name}",
            'description' => "Masuk ke akun pelanggan {$store->name}.",
            'canonical' => route('store.login', $store->slug),
            'image' => $store->logoUrl(),
        ];

        return view('public.customer.login', compact('store', 'seo', 'showcaseProducts'));
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

    public function showRegister(Request $request, string $storeSlug)
    {
        $store = $this->getStore($storeSlug);

        if (Auth::check()) {
            return redirect()->route('store.show', $store->slug);
        }

        if ($request->filled('return_url')) {
            $returnUrl = $request->query('return_url');
            if (str_starts_with($returnUrl, '/') && ! str_starts_with($returnUrl, '//')) {
                $request->session()->put('url.intended', $returnUrl);
            }
        }

        $showcaseProducts = $store->products()
            ->where('status', 'active')
            ->latest()
            ->take(4)
            ->get();

        $seo = [
            'title' => "Daftar Pelanggan {$store->name}",
            'description' => "Daftar akun pelanggan baru di {$store->name}.",
            'canonical' => route('store.register', $store->slug),
            'image' => $store->logoUrl(),
        ];

        return view('public.customer.register', compact('store', 'seo', 'showcaseProducts'));
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

        return redirect()->intended(route('store.show', $store->slug))
            ->with('status', "Selamat datang di {$store->name}! Akun pelanggan Anda berhasil dibuat.");
    }

    public function redirectToGoogle(Request $request, string $storeSlug)
    {
        $store = $this->getStore($storeSlug);

        if (Auth::check()) {
            if ($request->filled('return_url')) {
                $returnUrl = $request->query('return_url');
                if (str_starts_with($returnUrl, '/') && ! str_starts_with($returnUrl, '//')) {
                    return redirect($returnUrl);
                }
            }
            return redirect()->route('store.show', $store->slug);
        }

        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
            return redirect()->route('store.login', $store->slug)
                ->withErrors(['email' => 'Login Google belum dikonfigurasi di server. Silakan login dengan email & password.']);
        }

        $returnUrl = null;
        if ($request->filled('return_url')) {
            $candidate = $request->query('return_url');
            if (str_starts_with($candidate, '/') && ! str_starts_with($candidate, '//')) {
                $returnUrl = $candidate;
            }
        } elseif ($request->session()->has('url.intended')) {
            $returnUrl = $request->session()->get('url.intended');
        }

        if ($returnUrl) {
            $request->session()->put('customer_return_url', $returnUrl);
        }
        $request->session()->put('customer_store_slug', $store->slug);

        $statePayload = base64_encode(json_encode([
            'store_slug' => $store->slug,
            'return_url' => $returnUrl,
            'sig' => sha1($store->slug . config('app.key')),
        ]));

        $redirectUrl = GoogleOAuthController::resolveRedirectUrl($request);
        $driver = Socialite::driver('google')->stateless()->redirectUrl($redirectUrl);

        return $driver->with(['state' => $statePayload])->redirect();
    }

    public function handleGoogleCallback(Request $request, ?string $storeSlug = null, ?string $fallbackReturnUrl = null)
    {
        $resolvedSlug = $storeSlug;
        $resolvedReturnUrl = $fallbackReturnUrl;

        // Coba ekstrak dari parameter state (kebal terhadap cookie mismatch antar domain/port)
        if ($request->filled('state')) {
            try {
                $decoded = json_decode(base64_decode($request->query('state')), true);
                if (is_array($decoded) && ! empty($decoded['store_slug'])) {
                    if (isset($decoded['sig']) && hash_equals(sha1($decoded['store_slug'] . config('app.key')), $decoded['sig'])) {
                        $resolvedSlug = $resolvedSlug ?: $decoded['store_slug'];
                        $resolvedReturnUrl = $resolvedReturnUrl ?: ($decoded['return_url'] ?? null);
                    }
                }
            } catch (\Throwable $e) {}
        }

        $resolvedSlug = $resolvedSlug ?: $request->session()->pull('customer_store_slug');
        if (! $resolvedSlug) {
            return redirect()->route('login')->withErrors(['email' => 'Sesi login toko telah berakhir. Silakan coba lagi.']);
        }

        $store = Store::where('slug', $resolvedSlug)->where('status', 'active')->first();
        if (! $store) {
            return redirect()->route('login')->withErrors(['email' => 'Toko tidak ditemukan atau sedang tidak aktif.']);
        }

        try {
            $redirectUrl = GoogleOAuthController::resolveRedirectUrl($request);
            $driver = Socialite::driver('google')->stateless()->redirectUrl($redirectUrl);
            $google = $driver->user();
        } catch (\Throwable $e) {
            return redirect()->route('store.login', $store->slug)
                ->withErrors(['email' => 'Gagal masuk dengan Google: proses dibatalkan atau terjadi gangguan. Silakan coba lagi.']);
        }

        if (! $google->getEmail()) {
            return redirect()->route('store.login', $store->slug)
                ->withErrors(['email' => 'Akun Google Anda tidak menyediakan akses alamat email.']);
        }

        $user = User::where('google_id', $google->getId())->first()
            ?? User::where('email', $google->getEmail())->first();

        if ($user) {
            if (! $user->is_active) {
                return redirect()->route('store.login', $store->slug)
                    ->withErrors(['email' => 'Akun dinonaktifkan. Silakan hubungi admin toko.']);
            }

            if (! $user->google_id) {
                $user->forceFill(['google_id' => $google->getId()])->save();
            }

            if (! $user->email_verified_at) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
        } else {
            $user = User::create([
                'name' => $google->getName() ?: Str::before($google->getEmail(), '@'),
                'email' => $google->getEmail(),
                'password' => null,
                'google_id' => $google->getId(),
                'role' => 'user',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            AuditLogger::log(
                'auth.customer.register.google',
                $user,
                "Akun pelanggan {$user->email} terdaftar via Google di toko {$store->name}.",
                ['store_slug' => $store->slug, 'google_id' => $google->getId()],
                $user,
                $store->id
            );
        }

        // Pastikan profil pelanggan untuk toko ini ada
        StoreCustomerProfile::firstOrCreate(
            [
                'store_id' => $store->id,
                'user_id' => $user->id,
            ],
            [
                'name' => $user->name,
            ]
        );

        Auth::login($user, true);
        $request->session()->regenerate();

        AuditLogger::log(
            'auth.customer.login.google',
            $user,
            "Pelanggan {$user->email} masuk via Google ke toko {$store->name}.",
            ['store_slug' => $store->slug],
            $user,
            $store->id
        );

        $returnUrl = $resolvedReturnUrl ?: $request->session()->pull('customer_return_url');
        if ($returnUrl && str_starts_with($returnUrl, '/') && ! str_starts_with($returnUrl, '//')) {
            return redirect($returnUrl)->with('status', "Selamat datang di {$store->name}!");
        }

        return redirect()->intended(route('store.show', $store->slug))
            ->with('status', "Selamat datang di {$store->name}!");
    }
}
