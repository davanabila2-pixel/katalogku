<?php

namespace App\Http\Controllers\Api\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

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
}
