<?php

namespace App\Http\Controllers\Api\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Daftar seluruh pengguna (JSON) untuk superadmin tanpa reload.
     * Filter: q (nama/email), role.
     */
    public function index(Request $request)
    {
        $users = User::query()
            ->with(['stores'])
            ->when($request->q, fn ($q, $term) => $q->where(function ($qq) use ($term) {
                $qq->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            }))
            ->when($request->role, fn ($q, $role) => $q->where('role', $role))
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $users->getCollection()->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => (bool) $user->is_active,
                'stores' => $user->stores->pluck('name')->join(', '),
                'edit_url' => route('superadmin.users.edit', $user),
                'form_data_url' => route('api.superadmin.users.form_data', $user),
            ])->values(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
        ]);
    }

    /**
     * Data untuk modal "Kelola pengguna": nilai akun + seluruh toko +
     * toko yang saat ini dikelola & toko utama.
     */
    public function formData(User $user)
    {
        $user->loadMissing('stores');
        $primary = $user->stores->firstWhere('pivot.is_primary', true);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => (bool) $user->is_active,
                'store_ids' => $user->stores->pluck('id')->values(),
                'primary_store_id' => $primary?->id,
                'update_url' => route('api.superadmin.users.update', $user),
            ],
            'stores' => Store::orderBy('name')->get(['id', 'name'])->map(fn (Store $store) => [
                'id' => $store->id,
                'name' => $store->name,
            ])->values(),
        ]);
    }

    /**
     * Perbarui pengguna tanpa reload halaman.
     * Aturan sama dengan versi web (termasuk larangan menurunkan akun sendiri).
     */
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
            throw ValidationException::withMessages(['role' => 'Tidak dapat menurunkan/menonaktifkan akun sendiri.']);
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

        return response()->json([
            'message' => 'Pengguna diperbarui.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'is_active' => (bool) $user->is_active,
            ],
        ]);
    }
}
