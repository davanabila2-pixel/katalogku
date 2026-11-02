@extends('layouts.app')
@section('content')
<nav class="breadcrumb" aria-label="Navigasi Halaman">
  <ol class="breadcrumb-list">
    <li class="breadcrumb-item">
      <a href="{{ $store->url() }}" class="breadcrumb-link" title="Katalog {{ $store->name }}">
        <svg class="breadcrumb-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/></svg>
        <span>{{ $store->name }}</span>
      </a>
    </li>
    <li class="breadcrumb-separator" aria-hidden="true">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
    </li>
    <li class="breadcrumb-item active" aria-current="page">
      <span class="breadcrumb-current">Produk Terjual & Ulasan</span>
    </li>
  </ol>
</nav>

<div class="sold-page-header">
  <div class="sold-page-header-content">
    <a href="{{ $store->url() }}" class="btn-back-to-store">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
      <span>Kembali ke Katalog Toko</span>
    </a>
    <h1 class="sold-page-title">Produk Terjual & Ulasan Pembeli</h1>
    <p class="sold-page-subtitle">Daftar produk yang telah berhasil terjual beserta ulasan asli dari pembeli di <strong>{{ $store->name }}</strong>.</p>
  </div>
  <div class="sold-page-stats-pill">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
    <span><strong>{{ number_format($soldOrders->total(), 0, ',', '.') }}</strong> Pesanan Selesai</span>
  </div>
</div>

@if($soldOrders->isEmpty())
  <div class="sold-page-empty">
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
    <h3>Belum Ada Produk Terjual</h3>
    <p>Toko ini belum memiliki catatan pesanan yang selesai dan diulas oleh pembeli.</p>
    <a href="{{ $store->url() }}" class="btn btn-primary" style="margin-top:16px;border-radius:var(--r-full);padding:10px 24px">
      Jelajahi Produk Toko
    </a>
  </div>
@else
  <div class="sold-page-grid">
    @foreach($soldOrders as $order)
      @php
        $productTargetUrl = $order->product ? route('store.product', [$store->slug, $order->product->slug]) : null;
      @endphp
      <div class="sold-page-card">
        <div class="sold-card-product-row">
          @if($productTargetUrl)
            <a href="{{ $productTargetUrl }}" class="sold-card-thumb-link" title="Lihat detail produk">
              <img src="{{ $order->displayProductImageUrl() }}" alt="{{ $order->product_name }}" class="sold-card-thumb" loading="lazy">
            </a>
          @else
            <img src="{{ $order->displayProductImageUrl() }}" alt="{{ $order->product_name }}" class="sold-card-thumb" loading="lazy">
          @endif

          <div class="sold-card-product-info">
            <h3 class="sold-card-product-name">
              @if($productTargetUrl)
                <a href="{{ $productTargetUrl }}" style="color:inherit;text-decoration:none">{{ $order->product_name }}</a>
              @else
                {{ $order->product_name }}
              @endif
            </h3>
            <div class="sold-card-price-row">
              <span class="sold-card-price">Rp {{ number_format($order->product_price, 0, ',', '.') }}</span>
              <span class="sold-card-qty">· Terjual {{ $order->quantity }}x</span>
              <span class="badge badge-success" style="font-size:11px;padding:2px 8px">Selesai</span>
            </div>
          </div>
        </div>

        @if($order->isReviewed())
          <div class="sold-card-review-section">
            <div class="sold-card-review-header">
              <div class="sold-card-buyer-info">
                <span class="sold-buyer-avatar">{{ strtoupper(substr($order->buyer_name, 0, 1)) }}</span>
                <div>
                  <strong class="sold-buyer-name">{{ $order->buyerMaskedName() }}</strong>
                  <span class="sold-verified-badge">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="#0d8050"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                    Pembeli Terverifikasi
                  </span>
                </div>
              </div>

              <div class="sold-card-stars" title="Rating: {{ $order->rating ?? 5 }}/5">
                @for($s = 1; $s <= 5; $s++)
                  @if($s <= ($order->rating ?? 5))
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="#f59e0b"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                  @else
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="#e2e8f0"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                  @endif
                @endfor
              </div>
            </div>

            @if($order->buyer_review)
              <p class="sold-card-review-text">“{{ $order->buyer_review }}”</p>
            @endif

            @if($order->reviewed_at)
              <div class="sold-card-review-date">{{ $order->reviewed_at->translatedFormat('d F Y') }}</div>
            @endif
          </div>
        @endif
      </div>
    @endforeach
  </div>

  <div style="margin-top:28px">
    <x-pagination :paginator="$soldOrders" countSuffix="pesanan terjual" />
  </div>
@endif
@endsection

