<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreAccess
{
    /**
     * Admin hanya boleh mengakses toko yang menjadi tanggung jawabnya.
     * Superadmin dilewatkan (akses lintas toko). store_id dari request
     * TIDAK pernah dipercaya tanpa validasi relasi user–toko di server.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403, 'Akun tidak aktif.');
        }

        if ($user->isSuperadmin()) {
            return $next($request);
        }

        $store = $request->route('store');

        if (! $store) {
            abort(403, 'Toko tidak dikenali.');
        }

        $storeId = is_object($store) ? $store->id : (int) $store;

        if (! $user->managesStore($storeId)) {
            abort(403, 'Anda tidak memiliki akses ke toko ini.');
        }

        return $next($request);
    }
}
