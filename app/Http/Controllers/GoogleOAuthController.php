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
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $google = Socialite::driver('google')->user();
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
