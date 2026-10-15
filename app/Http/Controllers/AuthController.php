<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], (string) $user->password)) {
            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        if (! $user->is_active) {
            return back()->withErrors(['email' => 'Akun dinonaktifkan. Hubungi Superadmin.'])->onlyInput('email');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        AuditLogger::log('auth.login', $user, "Pengguna {$user->email} masuk.", null, $user);

        return $this->redirectByRole($user);
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'admin',
            'is_active' => true,
        ]);

        AuditLogger::log('auth.register', $user, "Akun admin {$user->email} terdaftar.", null, $user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('admin.nostore')->with('status', 'Akun dibuat. Minta Superadmin menautkan akun Anda ke toko.');
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            AuditLogger::log('auth.logout', $user, "Pengguna {$user->email} keluar.", null, $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function noStore()
    {
        return view('admin.nostore');
    }

    protected function redirectByRole(User $user)
    {
        if ($user->isSuperadmin()) {
            return redirect()->intended(route('superadmin.dashboard'));
        }

        $store = $user->stores()->orderByDesc('store_user.is_primary')->first();
        if ($store) {
            return redirect()->intended(route('admin.dashboard', $store));
        }

        return redirect()->route('admin.nostore');
    }
}
