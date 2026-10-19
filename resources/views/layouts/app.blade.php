<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $seo['title'] ?? config('app.name') }}</title>
  <meta name="description" content="{{ $seo['description'] ?? 'Katalog penjualan multi-toko.' }}">
  @isset($seo['canonical'])<link rel="canonical" href="{{ $seo['canonical'] }}">@endisset
  <meta property="og:title" content="{{ $seo['title'] ?? config('app.name') }}">
  <meta property="og:description" content="{{ $seo['description'] ?? '' }}">
  @isset($seo['image'])<meta property="og:image" content="{{ $seo['image'] }}">@endisset
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="topbar">
  <div class="container topbar-inner">
    @if(isset($store) && $store)
      {{-- Topbar Khusus Toko --}}
      <div class="topbar-store-left">
        <a class="topbar-back-pill" href="{{ route('stores.index') }}" title="Lihat semua toko">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
          <span>Semua Toko</span>
        </a>
        <div class="topbar-divider"></div>
        <a class="brand store-brand-link" href="{{ $store->url() }}">
          @if($store->logoUrl())
            <img class="store-topbar-logo" src="{{ $store->logoUrl() }}" alt="{{ $store->name }}">
          @else
            <div class="store-topbar-initial">{{ mb_substr($store->name, 0, 1) }}</div>
          @endif
          <span class="store-brand-name">{{ $store->name }}</span>
        </a>
      </div>

      <nav class="pillnav">
        <a class="pill-tab {{ !isset($activeCategory) && !request('q') && !request()->routeIs('store.product') ? 'active' : '' }}" href="{{ $store->url() }}">Semua Produk</a>
        @if(isset($rootCategories) && count($rootCategories) > 0)
          @foreach($rootCategories->take(3) as $cat)
            <a class="pill-tab {{ isset($activeCategory) && ($activeCategory->id === $cat->id || $activeCategory->parent_id === $cat->id) ? 'active' : '' }}" href="{{ route('store.category', [$store->slug, $cat->slug]) }}">{{ $cat->name }}</a>
          @endforeach
        @endif
      </nav>

      <div class="topbar-right">
        @if($store->contact_information)
          @php
            $cleanPhone = preg_replace('/[^0-9]/', '', $store->contact_information);
            if (str_starts_with($cleanPhone, '0')) {
                $cleanPhone = '62' . substr($cleanPhone, 1);
            }
          @endphp
          @if(strlen($cleanPhone) >= 9)
            <a class="btn btn-ghost btn-sm store-contact-btn" href="https://wa.me/{{ $cleanPhone }}?text=Halo%20{{ urlencode($store->name) }},%20saya%20tertarik%20dengan%20produk%20Anda" target="_blank" rel="noopener">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              <span>Hubungi</span>
            </a>
          @endif
        @endif

        @auth
          @if(auth()->user()->managesStore($store->id))
            <a class="btn btn-primary btn-sm" href="{{ route('admin.dashboard', $store) }}">Kelola Toko</a>
          @elseif(auth()->user()->isSuperadmin())
            <a class="btn btn-ghost btn-sm" href="{{ route('superadmin.dashboard') }}">Superadmin</a>
          @endif
          <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost btn-sm" type="submit">Keluar</button></form>
        @else
          <a class="btn btn-ghost btn-sm" href="{{ route('login') }}">Masuk</a>
        @endauth
      </div>
    @else
      {{-- Topbar Default (Beranda / Semua Toko) --}}
      <a class="brand" href="{{ route('home') }}">Kata<span>log</span></a>
      <nav class="pillnav">
        <a class="pill-tab {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Beranda</a>
        <a class="pill-tab {{ request()->routeIs('stores.*') ? 'active' : '' }}" href="{{ route('stores.index') }}">Toko</a>
      </nav>
      <div class="topbar-right">
        @auth
          @if(auth()->user()->isSuperadmin())
            <a class="btn btn-ghost btn-sm" href="{{ route('superadmin.dashboard') }}">Superadmin</a>
          @elseif(auth()->user()->stores()->exists())
            <a class="btn btn-ghost btn-sm" href="{{ route('admin.dashboard', auth()->user()->stores()->first()) }}">Dashboard</a>
          @endif
          <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-primary btn-sm" type="submit">Keluar</button></form>
        @else
          <a class="btn btn-ghost btn-sm" href="{{ route('login') }}">Masuk</a>
          <a class="btn btn-primary btn-sm" href="{{ route('register') }}">Daftar</a>
        @endauth
      </div>
    @endif
  </div>
</header>
<main class="container">
  @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
  @if($errors->any())<div class="alert alert-error"><ul style="margin:0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
  @yield('content')
</main>
<footer class="footer">
  <div class="container">Katalog multi-toko — satu domain, satu database. &copy; {{ date('Y') }}</div>
</footer>
<script>
    // Refresh CSRF token every 20 minutes (before default 120-minute expiry)
    setInterval(function() {
        var tokenInput = document.querySelector('meta[name="csrf-token"]');
        var csrfInputs = document.querySelectorAll('input[name="_token"]');
        if (tokenInput) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '/sanctum/csrf-cookie', true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var newToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                    // Update the meta tag
                    if (tokenInput) {
                        tokenInput.setAttribute('content', newToken);
                    }
                    // Update all hidden CSRF inputs in forms
                    csrfInputs.forEach(function(input) {
                        input.value = newToken;
                    });
                }
            };
            xhr.send();
        }
    }, 20 * 60 * 1000); // 20 minutes
</script>
</body>
</html>
