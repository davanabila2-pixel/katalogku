<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleOAuthController extends Controller
{
    public static function resolveRedirectUrl(Request $request): string
    {
        $configured = (string) config('services.google.redirect', '');
        if ($configured !== '' && str_starts_with($configured, 'http')) {
            return $configured;
        }

        $path = $configured !== '' ? '/' . ltrim($configured, '/') : '/auth/google/callback';
        $schemeAndHost = $request->getSchemeAndHttpHost();

        if (
            isset($_ENV['VERCEL'])
            || isset($_SERVER['VERCEL'])
            || str_contains($schemeAndHost, '.vercel.app')
            || $request->header('x-forwarded-proto') === 'https'
        ) {
            $schemeAndHost = preg_replace('/^http:\/\//i', 'https://', $schemeAndHost);
        }

        return rtrim($schemeAndHost, '/') . $path;
    }

    public function redirect(Request $request)
    {
        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
            return redirect()->route('login')->withErrors(['email' => 'Login Google belum dikonfigurasi. Hubungi Superadmin.']);
        }

        $redirectUrl = self::resolveRedirectUrl($request);

        return Socialite::driver('google')
            ->stateless()
            ->redirectUrl($redirectUrl)
            ->redirect();
    }

    public function callback(Request $request)
    {
        $customerStoreSlug = null;
        $customerReturnUrl = null;

        // 1. Cek dari parameter state (kebal terhadap cookie mismatch antar domain / port)
        if ($request->filled('state')) {
            try {
                $decoded = json_decode(base64_decode($request->query('state')), true);
                if (is_array($decoded) && ! empty($decoded['store_slug'])) {
                    if (isset($decoded['sig']) && hash_equals(sha1($decoded['store_slug'] . config('app.key')), $decoded['sig'])) {
                        $customerStoreSlug = $decoded['store_slug'];
                        $customerReturnUrl = $decoded['return_url'] ?? null;
                    }
                }
            } catch (\Throwable $e) {}
        }

        // 2. Fallback cek dari session jika ada
        if (! $customerStoreSlug && $request->session()->has('customer_store_slug')) {
            $customerStoreSlug = $request->session()->pull('customer_store_slug');
            $customerReturnUrl = $request->session()->pull('customer_return_url');
        }

        if ($customerStoreSlug) {
            return app(StoreCustomerAuthController::class)->handleGoogleCallback($request, $customerStoreSlug, $customerReturnUrl);
        }

        try {
            $redirectUrl = self::resolveRedirectUrl($request);
            $google = Socialite::driver('google')
                ->stateless()
                ->redirectUrl($redirectUrl)
                ->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'Login Google gagal. Coba lagi.']);
        }

        if (! $google->getEmail()) {
            return redirect()->route('login')->withErrors(['email' => 'Akun Google tidak memberikan email.']);
        }

        $user = User::where('google_id', $google->getId())->first()
            ?? User::where('email', $google->getEmail())->first();

        if ($user) {
            if (! $user->is_active) {
                return redirect()->route('login')->withErrors(['email' => 'Akun dinonaktifkan. Hubungi Superadmin.']);
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
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            AuditLogger::log('auth.register.google', $user, "Akun {$user->email} dibuat via Google.", null, $user);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        AuditLogger::log('auth.login.google', $user, "Pengguna {$user->email} masuk via Google.", null, $user);

        if ($user->isSuperadmin()) {
            return redirect()->intended(route('superadmin.dashboard'));
        }

        $store = $user->stores()->orderByDesc('store_user.is_primary')->first();

        return $store
            ? redirect()->intended(route('admin.dashboard', $store))
            : redirect()->route('admin.nostore');
    }
}
