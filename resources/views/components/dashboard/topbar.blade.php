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
    <div class="dash-topbar-left">
        <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-controls="dashboard-sidebar" aria-expanded="true" aria-label="Buka atau tutup menu">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>

        <x-dashboard.brand :store="$store" />

        <div class="dash-topbar-search">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input type="text" placeholder="Cari pesanan, anabul, produk, atau pelanggan…" aria-label="Pencarian cepat" />
        </div>
    </div>

    <div class="dash-topbar-right">
        @if($showStoreBrand)
            <a class="dash-topbar-catalog" href="{{ $store->url() }}" target="_blank" rel="noopener" title="Lihat Toko Online">
                <span class="dash-status-dot"></span>
                <span>Katalog Aktif ↗</span>
            </a>
        @endif
        
        <button class="dash-topbar-bell" type="button" aria-label="Notifikasi" title="Notifikasi">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <span class="bell-badge"></span>
        </button>

        <div class="dash-user-profile">
            <span class="dash-user-avatar">{{ mb_substr($user?->name ?? 'A', 0, 1) }}</span>
            <div class="dash-user-details">
                <span class="dash-user-name">{{ $user?->name }}</span>
                <span class="dash-user-role">{{ $isSuperadmin ? 'Superadmin' : 'Admin Toko' }}</span>
            </div>
        </div>

        <form class="dash-topbar-logout" method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-topbar-logout" type="submit" title="Keluar">Keluar</button></form>
    </div>
</header>
