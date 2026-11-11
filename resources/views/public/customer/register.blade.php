@extends('layouts.app')
@section('is-auth-page', true)
@section('hide-appbar-footer', true)
@section('content')

@php
  $slides = [];
  if (isset($showcaseProducts) && $showcaseProducts->isNotEmpty()) {
      foreach ($showcaseProducts as $idx => $prod) {
          $slides[] = [
              'title' => $prod->name,
              'desc' => Str::limit(strip_tags($prod->description ?: ($store->slogan ?: $store->description)), 95),
              'badge' => '⭐ Produk Pilihan',
              'image' => $prod->imageUrl(),
              'tag1' => $prod->category ? $prod->category->name : 'Produk',
              'price' => 'Rp ' . number_format($prod->selling_price, 0, ',', '.'),
              'stock' => $prod->stock > 0 ? 'Stok ' . $prod->stock : 'Habis',
          ];
      }
  } else {
      $slides = [
          [
              'title' => $store->name,
              'desc' => $store->slogan ?: ($store->description ?: 'Katalog produk resmi dan belanja online terpercaya.'),
              'badge' => '🏪 Toko Resmi',
              'image' => $store->logoUrl() ?: asset('favicon.ico'),
              'tag1' => 'Toko Resmi',
              'price' => 'Katalog Aktif',
              'stock' => 'Buka',
          ],
          [
              'title' => 'Pembayaran QRIS & Transfer',
              'desc' => 'Mendukung pembayaran langsung via scan QRIS e-wallet/m-banking serta transfer rekening toko.',
              'badge' => '💳 Pembayaran Cepat',
              'image' => $store->qrImageUrl() ?: ($store->logoUrl() ?: asset('favicon.ico')),
              'tag1' => 'QRIS Toko',
              'price' => 'Praktis',
              'stock' => 'Aman',
          ],
          [
              'title' => 'Transaksi Terhubung WhatsApp',
              'desc' => 'Konfirmasi pesanan real-time langsung ke WhatsApp penjual untuk proses pengiriman lebih cepat.',
              'badge' => '💬 Layanan Ramah',
              'image' => $store->logoUrl() ?: asset('favicon.ico'),
              'tag1' => 'WhatsApp',
              'price' => 'Respon Cepat',
              'stock' => '24 Jam',
          ],
      ];
  }
  $totalSlides = count($slides);
@endphp

<div class="store-auth-page-bg">
  <div class="store-auth-card is-register">
    {{-- Left: Form Column --}}
    <div class="store-auth-left">
      <div>
        {{-- Store Brand Header --}}
        <div class="store-auth-brand-row">
          @if($store->logoUrl())
            <img src="{{ $store->logoUrl() }}" alt="{{ $store->name }}" class="store-auth-logo-img">
          @else
            <div class="store-auth-logo-fallback">
              {{ mb_substr($store->name, 0, 1) }}
            </div>
          @endif
          <div class="store-auth-brand-info">
            <div class="store-auth-brand-name-wrap">
              <span class="store-auth-brand-name">{{ $store->name }}</span>
              <span class="store-auth-brand-badge">RESMI</span>
            </div>
            <span class="store-auth-brand-sub">{{ $store->slogan ?: 'Katalog & Belanja Toko' }}</span>
          </div>
        </div>

        {{-- Titles --}}
        <h1 class="store-auth-title">Daftar Akun Pelanggan</h1>
        <p class="store-auth-subtitle">Buat akun untuk belanja, lacak pesanan, dan bertransaksi di <strong>{{ $store->name }}</strong>:</p>

        @if (session('status') || session('info'))
          <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 11px 15px; margin-bottom: 16px; font-size: 13px; color: #1e40af; display: flex; align-items: center; gap: 9px; line-height: 1.45;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            <span>{{ session('status') ?? session('info') }}</span>
          </div>
        @endif

        @if ($errors->any())
          <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 10px 14px; margin-bottom: 16px; font-size: 13px; color: #dc2626;">
            {{ $errors->first() }}
          </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('store.register.submit', $store->slug) }}" class="store-auth-form">
          @csrf

          <div class="store-auth-field-grid">
            <div class="store-auth-field">
              <label class="store-auth-label" for="regName">Nama Lengkap</label>
              <div class="store-auth-input-wrap">
                <input class="store-auth-input" type="text" id="regName" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Nama lengkap Anda">
              </div>
            </div>

            <div class="store-auth-field">
              <label class="store-auth-label" for="regPhone">No. WhatsApp / Telepon</label>
              <div class="store-auth-input-wrap">
                <input class="store-auth-input" type="tel" id="regPhone" name="phone" value="{{ old('phone') }}" required autocomplete="tel" placeholder="08xxxxxxxxxx">
              </div>
            </div>
          </div>

          <div class="store-auth-field">
            <label class="store-auth-label" for="regEmail">Alamat Email</label>
            <div class="store-auth-input-wrap">
              <input class="store-auth-input" type="email" id="regEmail" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="nama@email.com">
            </div>
          </div>

          <div class="store-auth-field">
            <label class="store-auth-label" for="regAddress">Alamat Pengiriman Utama (Opsional)</label>
            <div class="store-auth-input-wrap">
              <input class="store-auth-input" type="text" id="regAddress" name="address" value="{{ old('address') }}" placeholder="Jalan, No. Rumah, RT/RW, Kota...">
            </div>
          </div>

          <div class="store-auth-field-grid">
            <div class="store-auth-field">
              <label class="store-auth-label" for="regPassword">Kata Sandi</label>
              <div class="store-auth-input-wrap">
                <input class="store-auth-input store-auth-input-pass" type="password" id="regPassword" name="password" required autocomplete="new-password" placeholder="Min. 8 karakter">
                <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('regPassword', this)" title="Lihat kata sandi">
                  <svg class="eye-open" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg class="eye-closed" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                </button>
              </div>
            </div>

            <div class="store-auth-field">
              <label class="store-auth-label" for="regPasswordConfirm">Ulangi Sandi</label>
              <div class="store-auth-input-wrap">
                <input class="store-auth-input store-auth-input-pass" type="password" id="regPasswordConfirm" name="password_confirmation" required autocomplete="new-password" placeholder="Ketik ulang sandi">
                <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('regPasswordConfirm', this)" title="Lihat kata sandi">
                  <svg class="eye-open" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg class="eye-closed" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                </button>
              </div>
            </div>
          </div>

          <button type="submit" class="store-auth-submit-btn" style="margin-top: 10px;">
            <span>Daftar Akun Pelanggan</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </button>
        </form>

        {{-- Divider & Google OAuth --}}
        <div class="store-auth-divider">
          <span>atau daftar otomatis</span>
        </div>

        <a href="{{ route('store.login.google', ['storeSlug' => $store->slug, 'return_url' => request('return_url')]) }}" class="store-auth-google-btn">
          <svg class="google-icon" width="19" height="19" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17Z"/>
            <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.33 24 12 24Z"/>
            <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.18 0 9.97 0 12s.45 3.82 1.25 5.42l4.03-3.15Z"/>
            <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98Z"/>
          </svg>
          <span>Daftar Cepat dengan Google</span>
        </a>

        <div class="store-auth-nav-links">
          <p class="store-auth-switch-link">
            Sudah memiliki akun di toko ini?
            <a href="{{ route('store.login', ['storeSlug' => $store->slug, 'return_url' => request('return_url')]) }}">Masuk di sini</a>
          </p>
          <a href="{{ $store->url() }}" class="store-auth-return-link">
            ← Kembali ke Katalog {{ $store->name }}
          </a>
        </div>
      </div>

      {{-- Trust Badges --}}
      <div class="store-auth-trust-footer">
        <span style="display: inline-flex; align-items: center; gap: 5px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          256-Bit SSL Enkripsi
        </span>
        <span>{{ $store->name }} Verified</span>
      </div>
    </div>

    {{-- Right: Showcase & Carousel Column --}}
    <div class="store-auth-right">
      {{-- Top Info Row --}}
      <div class="showcase-top-row">
        <div class="showcase-pill-badge" id="showcaseBadgePill">
          <span>✨</span>
          <span id="showcaseBadgeText">{{ $slides[0]['badge'] }}</span>
        </div>
        <div class="showcase-counter" id="showcaseCounterText">
          Katalog 01 / {{ sprintf('%02d', $totalSlides) }}
        </div>
      </div>

      {{-- Center Showcase Slider --}}
      <div class="showcase-slider-wrapper">
        <button type="button" class="showcase-nav-arrow prev" onclick="moveAuthSlide(-1)" aria-label="Slide sebelumnya">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <button type="button" class="showcase-nav-arrow next" onclick="moveAuthSlide(1)" aria-label="Slide selanjutnya">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </button>

        <div class="showcase-card">
          <div class="showcase-thumb-frame">
            <img id="showcaseCardImg" src="{{ $slides[0]['image'] }}" alt="{{ $slides[0]['title'] }}" class="showcase-thumb-img">
          </div>
          <div class="showcase-tags-row">
            <span class="showcase-card-tag" id="showcaseTag1">{{ $slides[0]['tag1'] }}</span>
            <span class="showcase-card-tag price" id="showcaseTagPrice">{{ $slides[0]['price'] }}</span>
            <span class="showcase-card-tag stock" id="showcaseTagStock">{{ $slides[0]['stock'] }}</span>
          </div>
        </div>
      </div>

      {{-- Headline & Description --}}
      <div class="showcase-content-info">
        <h3 class="showcase-headline" id="showcaseHeadline">{{ $slides[0]['title'] }}</h3>
        <p class="showcase-description" id="showcaseDesc">{{ $slides[0]['desc'] }}</p>

        {{-- Dots --}}
        <div class="showcase-dots-row" id="showcaseDotsRow">
          @for($i = 0; $i < $totalSlides; $i++)
            <button type="button" class="showcase-dot {{ $i === 0 ? 'active' : '' }}" onclick="goToAuthSlide({{ $i }})" aria-label="Ke slide {{ $i + 1 }}"></button>
          @endfor
        </div>
      </div>
    </div>
  </div>

  {{-- Outer Copyright Footer --}}
  <div class="store-auth-outer-footer">
    © {{ date('Y') }} {{ $store->name }} • Didukung oleh Katalog
  </div>
</div>

<script>
  const authSlides = @js($slides);
  let currentAuthIndex = 0;
  let authSlideTimer = null;

  function updateAuthSlideUI() {
    if (!authSlides || authSlides.length === 0) return;
    const item = authSlides[currentAuthIndex];

    const img = document.getElementById('showcaseCardImg');
    const badgeText = document.getElementById('showcaseBadgeText');
    const counterText = document.getElementById('showcaseCounterText');
    const tag1 = document.getElementById('showcaseTag1');
    const tagPrice = document.getElementById('showcaseTagPrice');
    const tagStock = document.getElementById('showcaseTagStock');
    const headline = document.getElementById('showcaseHeadline');
    const desc = document.getElementById('showcaseDesc');

    if (img) img.src = item.image;
    if (badgeText) badgeText.textContent = item.badge;
    if (counterText) {
      const cur = (currentAuthIndex + 1).toString().padStart(2, '0');
      const tot = authSlides.length.toString().padStart(2, '0');
      counterText.textContent = 'Katalog ' + cur + ' / ' + tot;
    }
    if (tag1) tag1.textContent = item.tag1;
    if (tagPrice) tagPrice.textContent = item.price;
    if (tagStock) tagStock.textContent = item.stock;
    if (headline) headline.textContent = item.title;
    if (desc) desc.textContent = item.desc;

    // Dots
    const dots = document.querySelectorAll('.showcase-dot');
    dots.forEach((dot, idx) => {
      if (idx === currentAuthIndex) {
        dot.classList.add('active');
      } else {
        dot.classList.remove('active');
      }
    });
  }

  function moveAuthSlide(step) {
    if (!authSlides || authSlides.length === 0) return;
    currentAuthIndex = (currentAuthIndex + step + authSlides.length) % authSlides.length;
    updateAuthSlideUI();
    restartAuthSlideTimer();
  }

  function goToAuthSlide(idx) {
    if (!authSlides || idx < 0 || idx >= authSlides.length) return;
    currentAuthIndex = idx;
    updateAuthSlideUI();
    restartAuthSlideTimer();
  }

  function restartAuthSlideTimer() {
    if (authSlideTimer) clearInterval(authSlideTimer);
    authSlideTimer = setInterval(() => {
      moveAuthSlide(1);
    }, 5000);
  }

  function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';

    const openIcon = btn.querySelector('.eye-open');
    const closedIcon = btn.querySelector('.eye-closed');
    if (openIcon && closedIcon) {
      openIcon.style.display = isPass ? 'none' : 'block';
      closedIcon.style.display = isPass ? 'block' : 'none';
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    restartAuthSlideTimer();
  });
</script>
@endsection
