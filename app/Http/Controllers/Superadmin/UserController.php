<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->with(['stores'])
            ->when($request->q, fn ($q, $term) => $q->where(function ($qq) use ($term) {
                $qq->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%");
            }))
            ->when($request->role, fn ($q, $role) => $q->where('role', $role))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('superadmin.users.index', compact('users'));
    }

    public function edit(User $user)
    {
        $stores = Store::orderBy('name')->get();

        return view('superadmin.users.form', compact('user', 'stores'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'role' => ['required', 'in:superadmin,admin'],
            'is_active' => ['nullable', 'boolean'],
            'stores' => ['nullable', 'array'],
            'stores.*' => ['integer', 'exists:stores,id'],
            'primary_store' => ['nullable', 'integer', 'exists:stores,id'],
        ]);

        if ($user->id === $request->user()->id && ($data['role'] !== 'superadmin' || ! $request->boolean('is_active', true))) {
            return back()->withErrors(['role' => 'Tidak dapat menurunkan/menonaktifkan akun sendiri.']);
        }

        $user->update([
            'name' => $data['name'],
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active', false),
        ]);

        if ($data['role'] === 'admin') {
            $sync = [];
            foreach ((array) ($data['stores'] ?? []) as $storeId) {
                $sync[$storeId] = ['is_primary' => (int) $storeId === (int) ($data['primary_store'] ?? 0)];
            }
            $user->stores()->sync($sync);
        } else {
            $user->stores()->detach();
        }

        AuditLogger::log('user.updated', $user, "Pengguna {$user->email} diperbarui.", ['role' => $user->role], $request->user());

        return redirect()->route('superadmin.users.index')->with('status', 'Pengguna diperbarui.');
    }
}
