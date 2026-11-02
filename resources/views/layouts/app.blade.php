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
@php
  $shouldHideAppbarFooter = ($hideAppbarFooter ?? false) || View::hasSection('hide-appbar-footer');
@endphp
<body class="{{ isset($store) && $store ? 'is-store-page' : '' }} {{ $shouldHideAppbarFooter ? 'is-standalone-links' : '' }}">
@if(! $shouldHideAppbarFooter)
<header class="topbar {{ isset($store) && $store ? 'topbar-store' : '' }}">
  <div class="container topbar-inner">
    @if(isset($store) && $store)
      {{-- Topbar Khusus Toko --}}
      <a class="store-brand-link" href="{{ $store->url() }}" title="{{ $store->name }}">
        @if($store->logoUrl())
          <img class="store-topbar-logo" src="{{ $store->logoUrl() }}" alt="{{ $store->name }}">
        @else
          <div class="store-topbar-initial">{{ mb_substr($store->name, 0, 1) }}</div>
        @endif
        <span class="store-brand-name">{{ $store->name }}</span>
      </a>

      <div class="topbar-right">
        <button type="button" class="btn-cart-nav" onclick="openCartDrawer()" title="Keranjang Belanja" aria-label="Buka Keranjang Belanja">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
          </svg>
          <span class="cart-pill-badge" data-cart-count style="display:none;">0</span>
        </button>

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
          <div class="user-dropdown-wrap">
            <button type="button" class="user-dropdown-btn" id="userStoreMenuBtn" aria-expanded="false" aria-haspopup="true" onclick="toggleUserDropdown(event)" title="Akun {{ auth()->user()->name }}">
              <img class="user-nav-avatar" src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
              <span class="user-nav-avatar-initial" style="display:none;">{{ auth()->user()->initial() }}</span>
              <span class="user-nav-name">{{ auth()->user()->name }}</span>
              <svg class="user-dropdown-chevron" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                <path d="m6 9 6 6 6-6"/>
              </svg>
            </button>
            <div class="user-dropdown-menu" id="userStoreDropdownMenu" role="menu">
              <div class="user-dropdown-header">
                <div class="user-dropdown-avatar-lg">
                  <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
                  <span class="user-nav-avatar-initial" style="display:none;">{{ auth()->user()->initial() }}</span>
                </div>
                <div class="user-dropdown-meta">
                  <span class="user-dropdown-name">{{ auth()->user()->name }}</span>
                  <span class="user-dropdown-email">{{ auth()->user()->email }}</span>
                </div>
              </div>
              <div class="user-dropdown-divider"></div>
              <a class="user-dropdown-item" href="{{ route('user.profile') }}" role="menuitem">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>Profile Saya</span>
              </a>
              <a class="user-dropdown-item" href="{{ route('user.orders') }}" role="menuitem">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <span>Pesanan Saya</span>
              </a>
              @if(auth()->user()->managesStore($store->id))
                <div class="user-dropdown-divider"></div>
                <a class="user-dropdown-item user-dropdown-admin-item" href="{{ route('admin.dashboard', $store) }}" role="menuitem">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                  <span>Kelola Toko</span>
                </a>
              @elseif(auth()->user()->isSuperadmin())
                <div class="user-dropdown-divider"></div>
                <a class="user-dropdown-item user-dropdown-admin-item" href="{{ route('superadmin.dashboard') }}" role="menuitem">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                  <span>Panel Superadmin</span>
                </a>
              @endif
              <div class="user-dropdown-divider"></div>
              <form method="POST" action="{{ route('logout') }}" class="user-dropdown-logout-form">
                @csrf
                <button type="submit" class="user-dropdown-item user-dropdown-logout-btn" role="menuitem">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                  <span>Keluar</span>
                </button>
              </form>
            </div>
          </div>
        @else
          <a class="btn btn-ghost btn-sm" href="{{ route('login') }}">Masuk</a>
        @endauth
      </div>
    @else
      {{-- Topbar Default (Beranda / Semua Toko) --}}
      <a class="brand" href="{{ route('home') }}">Kata<span class="brand-accent">log</span></a>
      <nav class="pillnav">
        <a class="pill-tab {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Beranda</a>
        <a class="pill-tab {{ request()->routeIs('stores.*') ? 'active' : '' }}" href="{{ route('stores.index') }}">Toko</a>
      </nav>
      <div class="topbar-right">
        <button type="button" class="btn-cart-nav" onclick="openCartDrawer()" title="Keranjang Belanja" aria-label="Buka Keranjang Belanja">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
          </svg>
          <span class="cart-pill-badge" data-cart-count style="display:none;">0</span>
        </button>

        @auth
          <div class="user-dropdown-wrap">
            <button type="button" class="user-dropdown-btn" id="userMainMenuBtn" aria-expanded="false" aria-haspopup="true" onclick="toggleUserDropdown(event)" title="Akun {{ auth()->user()->name }}">
              <img class="user-nav-avatar" src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
              <span class="user-nav-avatar-initial" style="display:none;">{{ auth()->user()->initial() }}</span>
              <span class="user-nav-name">{{ auth()->user()->name }}</span>
              <svg class="user-dropdown-chevron" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                <path d="m6 9 6 6 6-6"/>
              </svg>
            </button>
            <div class="user-dropdown-menu" id="userMainDropdownMenu" role="menu">
              <div class="user-dropdown-header">
                <div class="user-dropdown-avatar-lg">
                  <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
                  <span class="user-nav-avatar-initial" style="display:none;">{{ auth()->user()->initial() }}</span>
                </div>
                <div class="user-dropdown-meta">
                  <span class="user-dropdown-name">{{ auth()->user()->name }}</span>
                  <span class="user-dropdown-email">{{ auth()->user()->email }}</span>
                </div>
              </div>
              <div class="user-dropdown-divider"></div>
              <a class="user-dropdown-item" href="{{ route('user.profile') }}" role="menuitem">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>Profile Saya</span>
              </a>
              <a class="user-dropdown-item" href="{{ route('user.orders') }}" role="menuitem">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <span>Pesanan Saya</span>
              </a>
              @if(auth()->user()->isSuperadmin())
                <div class="user-dropdown-divider"></div>
                <a class="user-dropdown-item user-dropdown-admin-item" href="{{ route('superadmin.dashboard') }}" role="menuitem">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                  <span>Panel Superadmin</span>
                </a>
              @elseif(auth()->user()->stores()->exists())
                <div class="user-dropdown-divider"></div>
                <a class="user-dropdown-item user-dropdown-admin-item" href="{{ route('admin.dashboard', auth()->user()->stores()->first()) }}" role="menuitem">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                  <span>Dashboard Toko</span>
                </a>
              @endif
              <div class="user-dropdown-divider"></div>
              <form method="POST" action="{{ route('logout') }}" class="user-dropdown-logout-form">
                @csrf
                <button type="submit" class="user-dropdown-item user-dropdown-logout-btn" role="menuitem">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                  <span>Keluar</span>
                </button>
              </form>
            </div>
          </div>
        @else
          <a class="btn btn-ghost btn-sm" href="{{ route('login') }}">Masuk</a>
          <a class="btn btn-primary btn-sm" href="{{ route('register') }}">Daftar</a>
        @endauth
      </div>
    @endif
  </div>
</header>
@endif
<main class="{{ $shouldHideAppbarFooter ? 'container-standalone' : 'container' }}">
  @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
  @if($errors->any())<div class="alert alert-error"><ul style="margin:0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
  @yield('content')
</main>
@if(! $shouldHideAppbarFooter)
<footer class="footer {{ isset($store) && $store ? 'footer-store' : '' }}">
  @if(isset($store) && $store)
    {{-- Footer Khusus Toko --}}
    <div class="container store-footer-inner">
      <div class="store-footer-grid">
        {{-- Kolom 1: Profil Toko --}}
        <div class="store-footer-col store-footer-brand-col">
          <a class="store-brand-link" href="{{ $store->url() }}" title="{{ $store->name }}">
            @if($store->logoUrl())
              <img class="store-topbar-logo" src="{{ $store->logoUrl() }}" alt="{{ $store->name }}">
            @else
              <div class="store-topbar-initial">{{ mb_substr($store->name, 0, 1) }}</div>
            @endif
            <span class="store-brand-name">{{ $store->name }}</span>
          </a>
          @if($store->description)
            <p class="store-footer-desc">{{ $store->description }}</p>
          @else
            <p class="store-footer-desc">Katalog produk dan penjualan resmi {{ $store->name }}.</p>
          @endif
        </div>

        {{-- Kolom 2: Kontak & Layanan --}}
        <div class="store-footer-col">
          <h4 class="store-footer-heading">Layanan & Kontak</h4>
          <ul class="store-footer-list">
            @if($store->contact_information)
              <li>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                @if($store->cleanPhoneNumber())
                  <a href="{{ $store->whatsappUrl('Halo ' . $store->name . ', saya ingin bertanya mengenai produk di katalog Anda.') }}" target="_blank" rel="noopener" style="color:var(--success);font-weight:600;text-decoration:none">
                    {{ $store->contact_information }} (WhatsApp)
                  </a>
                @else
                  <span>{{ $store->contact_information }}</span>
                @endif
              </li>
            @endif

            @if($store->address)
              <li style="align-items:flex-start">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-top:2px;flex-shrink:0"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>{{ $store->address }}</span>
              </li>
            @endif

            <li>
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
              <span>Order Online 24 Jam via WhatsApp</span>
            </li>
          </ul>
        </div>
      </div>

      {{-- Baris Bawah / Copyright --}}
      <div class="store-footer-bottom">
        <div>&copy; {{ date('Y') }} <strong>{{ $store->name }}</strong>. Hak cipta dilindungi.</div>
        <div class="store-footer-credit">Didukung oleh <a href="{{ route('home') }}">Katalog</a></div>
      </div>
    </div>
  @else
    {{-- Footer Default Platform --}}
    <div class="container default-footer-inner">
      <div>Katalog — platform katalog toko online dalam satu tautan. &copy; {{ date('Y') }}</div>
      <div><a href="{{ route('stores.index') }}">Lihat Semua Toko</a></div>
    </div>
  @endif
</footer>
@endif
<script>
    // User menu dropdown toggle
    function toggleUserDropdown(e) {
        e.stopPropagation();
        var wrap = e.currentTarget.closest('.user-dropdown-wrap');
        if (!wrap) return;
        var wasOpen = wrap.classList.contains('is-open');
        document.querySelectorAll('.user-dropdown-wrap.is-open').forEach(function(el) {
            el.classList.remove('is-open');
            var b = el.querySelector('.user-dropdown-btn');
            if (b) b.setAttribute('aria-expanded', 'false');
        });
        if (!wasOpen) {
            wrap.classList.add('is-open');
            var btn = wrap.querySelector('.user-dropdown-btn');
            if (btn) btn.setAttribute('aria-expanded', 'true');
        }
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.user-dropdown-wrap')) {
            document.querySelectorAll('.user-dropdown-wrap.is-open').forEach(function(el) {
                el.classList.remove('is-open');
                var btn = el.querySelector('.user-dropdown-btn');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            });
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.user-dropdown-wrap.is-open').forEach(function(el) {
                el.classList.remove('is-open');
                var btn = el.querySelector('.user-dropdown-btn');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            });
        }
    });

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
<x-product-chat-modal />
<x-product-buy-modal />
<x-cart-drawer />
<x-product-gallery-modal />
</body>
</html>
