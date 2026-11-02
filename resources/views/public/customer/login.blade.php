@extends('layouts.app')
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
  <div class="store-auth-card">
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
        <h1 class="store-auth-title">Masuk ke Akun Toko 🛍️</h1>
        <p class="store-auth-subtitle">Silakan masukkan email & kata sandi akun pelanggan Anda:</p>

        @if ($errors->any())
          <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 10px 14px; margin-bottom: 16px; font-size: 13px; color: #dc2626;">
            {{ $errors->first() }}
          </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('store.login.submit', $store->slug) }}" class="store-auth-form">
          @csrf

          <div class="store-auth-field">
            <label class="store-auth-label" for="loginEmail">Alamat Email / ID Pelanggan</label>
            <div class="store-auth-input-wrap">
              <input class="store-auth-input" type="email" id="loginEmail" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="nama@email.com">
            </div>
          </div>

          <div class="store-auth-field">
            <label class="store-auth-label" for="loginPassword">Kata Sandi</label>
            <div class="store-auth-input-wrap">
              <input class="store-auth-input store-auth-input-pass" type="password" id="loginPassword" name="password" required autocomplete="current-password" placeholder="••••••••">
              <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('loginPassword', this)" title="Lihat kata sandi">
                <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
              </button>
            </div>
          </div>

          <div class="store-auth-check-row">
            <label style="display: inline-flex; align-items: center; gap: 7px; cursor: pointer; font-size: 13px;">
              <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }} style="accent-color: #e11d48; width: 15px; height: 15px; border-radius: 4px;">
              <span>Ingat sesi</span>
            </label>
            @if($store->cleanPhoneNumber())
              <a href="{{ $store->whatsappUrl('Halo ' . $store->name . ', saya membutuhkan bantuan untuk reset/lupa kata sandi akun pelanggan saya.') }}" target="_blank" rel="noopener" class="store-auth-forgot-link">
                Lupa password?
              </a>
            @else
              <span class="store-auth-forgot-link" style="color: #94a3b8; cursor: default;">Lupa password?</span>
            @endif
          </div>

          <button type="submit" class="store-auth-submit-btn">
            <span>Masuk ke Akun Toko</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </button>
        </form>

        <div class="store-auth-nav-links">
          <p class="store-auth-switch-link">
            Belum punya akun di toko ini?
            <a href="{{ route('store.register', $store->slug) }}">Daftar sekarang</a>
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
