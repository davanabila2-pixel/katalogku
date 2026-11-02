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
      <span class="breadcrumb-current">Pesanan Saya</span>
    </li>
  </ol>
</nav>

<div class="user-account-container">
  <div class="user-account-header">
    <div class="user-account-heading">
      <div style="display: inline-flex; align-items: center; gap: 6px; margin-bottom: 4px;">
        <span style="font-size: 12px; font-weight: 700; color: #0284c7; background: #e0f2fe; padding: 2px 8px; border-radius: var(--r-full);">Pesanan Khusus {{ $store->name }}</span>
      </div>
      <h1 class="user-account-title">Pesanan Saya di {{ $store->name }}</h1>
      <p class="user-account-subtitle">Daftar riwayat belanja dan status pesanan Anda khusus di toko ini.</p>
    </div>
  </div>

  @if($orders->isEmpty())
    <div class="user-account-card user-orders-empty">
      <div class="user-empty-icon">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
      </div>
      <h3 style="font-size:18px;margin:12px 0 6px;color:#0f172a;">Belum Ada Pesanan di {{ $store->name }}</h3>
      <p style="color:#64748b;font-size:14px;margin:0 0 18px;">Anda belum melakukan pemesanan produk di toko <strong>{{ $store->name }}</strong>.</p>
      <a href="{{ $store->url() }}" class="btn btn-primary">Mulai Belanja di {{ $store->name }}</a>
    </div>
  @else
    <div class="user-orders-list">
      @foreach($orders as $ord)
        @php
          $statusBadges = [
              'pending' => ['label' => 'Menunggu Konfirmasi', 'class' => 'status-badge-pending'],
              'processing' => ['label' => 'Diproses', 'class' => 'status-badge-processing'],
              'shipped' => ['label' => 'Dikirim', 'class' => 'status-badge-shipped'],
              'completed' => ['label' => 'Selesai', 'class' => 'status-badge-completed'],
              'cancelled' => ['label' => 'Dibatalkan', 'class' => 'status-badge-cancelled'],
          ];
          $badge = $statusBadges[$ord->status] ?? ['label' => ucfirst($ord->status), 'class' => 'status-badge-pending'];
          $product = $ord->product;
          $productUrl = $product ? route('store.product', [$store->slug, $product->slug]) : null;
          $productImg = $ord->displayProductImageUrl();
        @endphp
        <div class="user-order-card">
          <div class="user-order-head">
            <div class="user-order-head-left">
              <span class="user-order-code">{{ $ord->order_code }}</span>
              <span class="user-order-date">{{ $ord->created_at->format('d M Y, H:i') }}</span>
            </div>
            <span class="user-order-status {{ $badge['class'] }}">{{ $badge['label'] }}</span>
          </div>

          <div class="user-order-body">
            @if($productUrl)
              <a href="{{ $productUrl }}" class="user-order-thumb-wrap">
                <img src="{{ $productImg }}" alt="{{ $ord->product_name }}" class="user-order-thumb" loading="lazy">
              </a>
            @else
              <div class="user-order-thumb-wrap">
                <img src="{{ $productImg }}" alt="{{ $ord->product_name }}" class="user-order-thumb" loading="lazy">
              </div>
            @endif

            <div class="user-order-details">
              <h3 class="user-order-product-name">
                @if($productUrl)
                  <a href="{{ $productUrl }}">{{ $ord->product_name }}</a>
                @else
                  {{ $ord->product_name }}
                @endif
              </h3>
              <p class="user-order-qty">{{ $ord->quantity }} barang x Rp {{ number_format($ord->product_price, 0, ',', '.') }}</p>
              @if($ord->buyer_notes)
                <p class="user-order-notes">Catatan: "{{ $ord->buyer_notes }}"</p>
              @endif
              <p style="font-size:12.5px;color:#64748b;margin:4px 0 0;">
                Penerima: <strong>{{ $ord->buyer_name }}</strong> ({{ $ord->buyer_phone }})<br>
                Alamat: {{ $ord->buyer_address }}
              </p>
            </div>

            <div class="user-order-pricing">
              <span class="user-order-total-label">Total Pesanan</span>
              <span class="user-order-total-val">Rp {{ number_format($ord->total_price, 0, ',', '.') }}</span>
              @if($store->contact_information)
                @php
                  $cleanPhone = preg_replace('/[^0-9]/', '', $store->contact_information);
                  if (str_starts_with($cleanPhone, '0')) {
                      $cleanPhone = '62' . substr($cleanPhone, 1);
                  }
                @endphp
                @if(strlen($cleanPhone) >= 9)
                  <a href="https://wa.me/{{ $cleanPhone }}?text=Halo%20{{ urlencode($store->name) }},%20saya%20ingin%20menanyakan%20status%20pesanan%20{{ urlencode($ord->order_code) }}" target="_blank" rel="noopener" class="user-order-wa-btn">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <span>Hubungi Toko</span>
                  </a>
                @endif
              @endif
            </div>
          </div>
        </div>
      @endforeach
    </div>

    @if($orders->hasPages())
      <div style="margin-top:24px;">
        <x-pagination :paginator="$orders" countSuffix="pesanan" />
      </div>
    @endif
  @endif
</div>
@endsection

