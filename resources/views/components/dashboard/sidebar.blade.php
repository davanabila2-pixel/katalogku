@props(['store' => null])

@php
    $user = auth()->user();
    $isSuperadmin = $user?->isSuperadmin() ?? false;
    $onAdminRoute = request()->routeIs('admin.*');

    if ($onAdminRoute && ! $store && $user) {
        $store = $user->primaryStores()->first() ?? $user->stores()->first();
    }
@endphp

<aside class="sidebar" id="dashboard-sidebar" aria-label="Menu dashboard">
    <x-dashboard.brand :store="$store" variant="sidebar" />
    @if($onAdminRoute && $store)
        <nav class="sidebar-nav">
            <p class="sidebar-label">Ringkasan Toko</p>
            <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard', $store) }}" title="Dashboard Utama"><x-dashboard.nav-icon name="home" /><span class="nav-label">Dashboard Utama</span></a>

            <p class="sidebar-label">Transaksi & Pesanan</p>
            <a class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index', $store) }}" title="Pesanan Masuk"><x-dashboard.nav-icon name="shopping-bag" /><span class="nav-label">Pesanan Masuk</span></a>

            <p class="sidebar-label">Setup & Konfigurasi</p>
            <a class="{{ request()->routeIs('admin.profile.*') ? 'active' : '' }}" href="{{ route('admin.profile.edit', $store) }}" title="Profil Toko"><x-dashboard.nav-icon name="store" /><span class="nav-label">Profil Toko</span></a>

            <p class="sidebar-label">Katalog Produk</p>
            <a class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index', $store) }}" title="Kategori"><x-dashboard.nav-icon name="folder" /><span class="nav-label">Kategori</span></a>
            <a class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index', $store) }}" title="Produk"><x-dashboard.nav-icon name="box" /><span class="nav-label">Daftar Produk</span></a>

            <p class="sidebar-label">Inventaris & Logistik</p>
            <a class="{{ request()->routeIs('admin.batches.*') ? 'active' : '' }}" href="{{ route('admin.batches.index', $store) }}" title="Batch Pembelian"><x-dashboard.nav-icon name="layers" /><span class="nav-label">Batch Pembelian</span></a>
            <a class="{{ request()->routeIs('admin.stock.index') ? 'active' : '' }}" href="{{ route('admin.stock.index', $store) }}" title="Stok"><x-dashboard.nav-icon name="archive" /><span class="nav-label">Stok</span></a>
            <a class="{{ request()->routeIs('admin.stock.history') ? 'active' : '' }}" href="{{ route('admin.stock.history', $store) }}" title="Riwayat Stok"><x-dashboard.nav-icon name="history" /><span class="nav-label">Riwayat Stok</span></a>
            <a class="{{ request()->routeIs('admin.activity') ? 'active' : '' }}" href="{{ route('admin.activity', $store) }}" title="Aktivitas"><x-dashboard.nav-icon name="activity" /><span class="nav-label">Aktivitas</span></a>

            <p class="sidebar-label">Akses Publik</p>
            <a class="{{ request()->routeIs('admin.links.*') ? 'active' : '' }}" href="{{ route('admin.links.index', $store) }}" title="Tautan"><x-dashboard.nav-icon name="link" /><span class="nav-label">Tautan</span></a>
            <a href="{{ $store->url() }}" target="_blank" rel="noopener" title="Lihat Katalog"><x-dashboard.nav-icon name="external" /><span class="nav-label">Lihat Katalog</span></a>
        </nav>
    @else
        <nav class="sidebar-nav">
            <p class="sidebar-label">Ringkasan Sistem</p>
            <a class="{{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}" href="{{ route('superadmin.dashboard') }}" title="Dashboard"><x-dashboard.nav-icon name="home" /><span class="nav-label">Dashboard</span></a>

            <p class="sidebar-label">Manajemen Toko & User</p>
            <a class="{{ request()->routeIs('superadmin.stores.*') ? 'active' : '' }}" href="{{ route('superadmin.stores.index') }}" title="Toko"><x-dashboard.nav-icon name="store" /><span class="nav-label">Toko</span></a>
            <a class="{{ request()->routeIs('superadmin.users.*') ? 'active' : '' }}" href="{{ route('superadmin.users.index') }}" title="Pengguna"><x-dashboard.nav-icon name="users" /><span class="nav-label">Pengguna</span></a>

            <p class="sidebar-label">Katalog & Inventaris</p>
            <a class="{{ request()->routeIs('superadmin.categories') ? 'active' : '' }}" href="{{ route('superadmin.categories') }}" title="Kategori"><x-dashboard.nav-icon name="folder" /><span class="nav-label">Kategori</span></a>
            <a class="{{ request()->routeIs('superadmin.products') ? 'active' : '' }}" href="{{ route('superadmin.products') }}" title="Produk"><x-dashboard.nav-icon name="box" /><span class="nav-label">Produk</span></a>
            <a class="{{ request()->routeIs('superadmin.batches') ? 'active' : '' }}" href="{{ route('superadmin.batches') }}" title="Batch"><x-dashboard.nav-icon name="layers" /><span class="nav-label">Batch</span></a>
            <a class="{{ request()->routeIs('superadmin.stock') ? 'active' : '' }}" href="{{ route('superadmin.stock') }}" title="Stok"><x-dashboard.nav-icon name="archive" /><span class="nav-label">Stok</span></a>

            <p class="sidebar-label">Sistem & Audit</p>
            <a class="{{ request()->routeIs('superadmin.audit') ? 'active' : '' }}" href="{{ route('superadmin.audit') }}" title="Audit Log"><x-dashboard.nav-icon name="clipboard" /><span class="nav-label">Audit Log</span></a>
        </nav>
    @endif

    <div class="sidebar-footer">
        @if($onAdminRoute && $store)
            <div class="sidebar-sync-card">
                <span class="sync-dot"></span>
                <div class="sync-info">
                    <span class="sync-title">Katalog Online</span>
                    <span class="sync-sub">Siap Melayani</span>
                </div>
            </div>
        @endif
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sidebar-logout" type="submit">Keluar</button></form>
    </div>
</aside>
