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
            'account_type' => ['required', 'in:user,toko'],
            'store_name' => ['required_if:account_type,toko', 'nullable', 'string', 'max:100'],
            'store_description' => ['nullable', 'string', 'max:2000'],
            'store_contact' => ['nullable', 'string', 'max:255'],
        ]);

        $store = null;
        $user = \Illuminate\Support\Facades\DB::transaction(function () use ($data, &$store) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'admin',
                'is_active' => true,
            ]);

            AuditLogger::log('auth.register', $user, "Akun admin {$user->email} terdaftar.", null, $user);

            if (($data['account_type'] ?? 'user') === 'toko') {
                $store = $this->makeStoreFor($user, $data['store_name'], [
                    'description' => $data['store_description'] ?? null,
                    'contact_information' => $data['store_contact'] ?? null,
                ]);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        if ($store) {
            return redirect()->route('admin.dashboard', $store)
                ->with('status', "Toko {$store->name} dibuat. Katalog publik: {$store->url()}");
        }

        return redirect()->route('admin.nostore')->with('status', 'Akun dibuat. Anda bisa membuat toko sendiri atau menunggu Superadmin menautkan Anda ke toko.');
    }

    /**
     * User yang sudah login (mis. via Google) membuat tokonya sendiri
     * dan otomatis menjadi admin utama toko tersebut.
     */
    public function createStore(Request $request)
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:100'],
            'store_description' => ['nullable', 'string', 'max:2000'],
            'store_contact' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        if (! $user->is_active) {
            abort(403, 'Akun tidak aktif.');
        }

        $store = \Illuminate\Support\Facades\DB::transaction(function () use ($user, $data) {
            return $this->makeStoreFor($user, $data['store_name'], [
                'description' => $data['store_description'] ?? null,
                'contact_information' => $data['store_contact'] ?? null,
            ]);
        });

        return redirect()->route('admin.dashboard', $store)
            ->with('status', "Toko {$store->name} dibuat. Katalog publik: {$store->url()}");
    }

    protected function makeStoreFor(User $user, string $storeName, array $extra = []): \App\Models\Store
    {
        $store = \App\Models\Store::create([
            'name' => $storeName,
            'description' => $extra['description'] ?? null,
            'contact_information' => $extra['contact_information'] ?? null,
            'status' => 'active',
        ]);

        $store->users()->syncWithoutDetaching([$user->id => ['is_primary' => true]]);

        AuditLogger::log(
            'store.created.self',
            $store,
            "Toko {$store->name} dibuat via registrasi oleh {$user->email}.",
            ['url' => $store->url()],
            $user,
            $store->id
        );

        return $store;
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
