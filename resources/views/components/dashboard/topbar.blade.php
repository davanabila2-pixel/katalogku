@props(['store' => null])

@php
    $user = auth()->user();
    $isSuperadmin = $user?->isSuperadmin() ?? false;
    $onAdminRoute = request()->routeIs('admin.*');

    if ($onAdminRoute && ! $store && $user) {
        $store = $user->primaryStores()->first() ?? $user->stores()->first();
    }

    $showStoreBrand = $onAdminRoute && $store !== null;
@endphp

<header class="dash-topbar">
    <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-controls="dashboard-sidebar" aria-expanded="true" aria-label="Buka atau tutup menu">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
    </button>

    <x-dashboard.brand :store="$store" />

    <div class="dash-topbar-right">
        @if($showStoreBrand)
            <a class="btn btn-ghost btn-sm dash-topbar-catalog" href="{{ $store->url() }}" target="_blank" rel="noopener">Lihat Katalog ↗</a>
        @endif
        <span class="dash-user-name">{{ $user?->name }}</span>
        <form class="dash-topbar-logout" method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-danger btn-sm" type="submit">Keluar</button></form>
    </div>
</header>
