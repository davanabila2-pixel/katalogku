@props(['store' => null, 'variant' => 'topbar'])

@php
    $user = auth()->user();
    $isSuperadmin = $user?->isSuperadmin() ?? false;
    $onAdminRoute = request()->routeIs('admin.*');

    if ($onAdminRoute && ! $store && $user) {
        $store = $user->primaryStores()->first() ?? $user->stores()->first();
    }

    $showStoreBrand = $onAdminRoute && $store !== null;
    $classes = 'dash-brand'.($variant === 'sidebar' ? ' dash-brand-on-dark' : '');
@endphp

@if($showStoreBrand)
    <a class="{{ $classes }}" href="{{ route('admin.dashboard', $store) }}" title="{{ $store->name }}">
        @if($store->logoUrl())
            <img class="dash-brand-logo" src="{{ $store->logoUrl() }}" alt="Logo {{ $store->name }}">
        @else
            <span class="dash-brand-initial">{{ mb_substr($store->name, 0, 1) }}</span>
        @endif
        <span class="dash-brand-text">
            <span class="dash-brand-name">{{ $store->name }}</span>
            <span class="dash-brand-meta"><span class="dash-brand-badge-dot"></span>Admin Toko</span>
        </span>
    </a>
@else
    <a class="{{ $classes }}" href="{{ $isSuperadmin ? route('superadmin.dashboard') : route('home') }}">
        <span class="dash-brand-initial dash-brand-initial-app">K</span>
        <span class="dash-brand-text">
            <span class="dash-brand-name">Kata<span class="dash-brand-accent">log</span></span>
            <span class="dash-brand-meta"><span class="dash-brand-badge-dot"></span>{{ $isSuperadmin ? 'Superadmin' : 'Panel' }}</span>
        </span>
    </a>
@endif
