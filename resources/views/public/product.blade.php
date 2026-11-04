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
    @if($product->category)
      @if($product->category->parent)
        <li class="breadcrumb-separator" aria-hidden="true">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li class="breadcrumb-item">
          <a href="{{ route('store.category', [$store->slug, $product->category->parent->slug]) }}" class="breadcrumb-link">{{ $product->category->parent->name }}</a>
        </li>
      @endif
      <li class="breadcrumb-separator" aria-hidden="true">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
      </li>
      <li class="breadcrumb-item">
        <a href="{{ route('store.category', [$store->slug, $product->category->slug]) }}" class="breadcrumb-link">{{ $product->category->name }}</a>
      </li>
    @endif
    <li class="breadcrumb-separator" aria-hidden="true">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
    </li>
    <li class="breadcrumb-item active" aria-current="page">
      <span class="breadcrumb-current">{{ $product->name }}</span>
    </li>
  </ol>
</nav>
@php
  $galleryUrls = $product->galleryUrls();
@endphp
<style>
.pdp-top-header {
  margin-top: 16px;
  margin-bottom: 22px;
}
.btn-pdp-back {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 8px 16px;
  border-radius: var(--r-full);
  background: #f1f5f9;
  color: #334155;
  text-decoration: none;
  font-size: 13.5px;
  font-weight: 600;
  border: 1px solid #cbd5e1;
  transition: all .15s ease;
}
.btn-pdp-back:hover {
  background: #e2e8f0;
  color: #0f172a;
  border-color: #94a3b8;
  transform: translateX(-2px);
}
.pdp-product-title {
  font-size: 28px;
  font-weight: 800;
  color: var(--ink-deep);
  margin: 12px 0 0 0;
  line-height: 1.25;
  letter-spacing: -0.02em;
  word-break: break-word;
}
@media (max-width: 767px) {
  .pdp-top-header {
    margin-top: 12px;
    margin-bottom: 18px;
  }
  .pdp-product-title {
    font-size: 22px;
    line-height: 1.3;
  }
}
.pdp-actions-container .btn-buy-now {
  background: var(--primary, #0066ff);
  color: #fff;
  border: none;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(0, 102, 255, 0.28);
  transition: all .15s ease;
}
.pdp-actions-container .btn-buy-now:hover {
  filter: brightness(0.92);
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(0, 102, 255, 0.35);
}
.pdp-actions-container .btn-cart-pdp {
  background: #f8fafc;
  color: var(--ink-deep);
  border: 1px solid #cbd5e1;
  cursor: pointer;
  transition: all .15s ease;
}
.pdp-actions-container .btn-cart-pdp:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
}
</style>

{{-- Header PDP: Tombol Kembali & Nama Produk di Atas Gambar --}}
<div class="pdp-top-header">
  <div>
    <a href="{{ $store->url() }}" class="btn-pdp-back">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
      <span>Kembali ke Katalog</span>
    </a>
  </div>
  <h1 class="pdp-product-title">
    {{ $product->name }}
  </h1>
</div>

<div class="pdp" style="margin-top:0;">
  <div class="pdp-gallery">
    <div class="pdp-main-image-wrap" onclick="openProductGallery(@js($galleryUrls), currentMainImgIndex, @js($product->name))" title="Klik untuk melihat foto layar penuh">
      <img id="pdpMainImage" src="{{ $galleryUrls[0] }}" alt="{{ $product->name }}" class="pdp-main-img">
      @if($product->isBundle())
        <span class="badge badge-bundle" style="position:absolute;top:16px;left:16px;padding:6px 14px;font-size:13px;box-shadow:0 2px 8px rgba(0,0,0,0.15)">Paket Setelan</span>
      @endif
      <div class="pdp-expand-pill">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
        <span>Lihat Galeri Penuh ({{ count($galleryUrls) }})</span>
      </div>
    </div>

    @if(count($galleryUrls) > 1)
      <div class="pdp-thumbnails-rail">
        @foreach($galleryUrls as $idx => $url)
          <button type="button" class="pdp-thumb-btn {{ $idx === 0 ? 'active' : '' }}" onclick="switchPdpMainImage({{ $idx }}, '{{ $url }}')" aria-label="Foto ke-{{ $idx + 1 }}">
            <img src="{{ $url }}" alt="Thumb {{ $idx + 1 }}">
          </button>
        @endforeach
      </div>
    @endif
  </div>

  <div class="pdp-rail">
    @if(!$product->isBundle() && ($product->is_hidden_from_catalog || ($store->hide_component_products && $product->partOfBundles->count() > 0)))
      <div style="background:#fffbeb;color:#92400e;border:1px solid #fde68a;border-radius:var(--r-lg);padding:8px 12px;font-size:13px;margin-bottom:12px">
        ℹ️ Produk satuan ini disembunyikan dari katalog umum toko dan diprioritaskan sebagai bagian dari paket setelan.
      </div>
    @endif

    {{-- 1. Harga Produk --}}
    <div class="price" style="font-size:26px;font-weight:800;color:var(--primary);display:flex;align-items:baseline;gap:8px;flex-wrap:wrap">
      <span>Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
      @if($product->isBundle() && $product->bundleSavings() > 0)
        <span style="font-size:15px;color:var(--steel);text-decoration:line-through;font-weight:normal">Rp {{ number_format($product->bundleOriginalPrice(), 0, ',', '.') }}</span>
        <span class="badge badge-success" style="font-size:12px">Hemat Rp {{ number_format($product->bundleSavings(), 0, ',', '.') }}</span>
      @endif
    </div>

    {{-- 2. Stok di Bawah Harga --}}
    <div style="margin:8px 0 14px">
      @if($product->stock <= 0)
        <span class="badge badge-critical" style="font-size:12.5px;padding:4px 10px;">Stok {{ $product->isBundle() ? 'paket ' : '' }}habis</span>
      @elseif($product->stock <= 5)
        <span class="badge badge-warning" style="font-size:12.5px;padding:4px 10px;">Sisa {{ $product->stock }} {{ $product->isBundle() ? 'paket' : 'unit' }}</span>
      @else
        <span class="badge badge-success" style="font-size:12.5px;padding:4px 10px;">Stok tersedia ({{ $product->stock }} {{ $product->isBundle() ? 'paket' : 'unit' }})</span>
      @endif

      @if($product->isBundle())
        <div style="font-size:12px;color:var(--steel);margin-top:6px">
          *Stok paket setelan dihitung otomatis dari ketersediaan produk satuannya.
        </div>
      @endif
    </div>

    {{-- Meta Toko, Kategori, SKU --}}
    <p style="color:var(--steel);font-size:13px;margin:0 0 16px;line-height:1.4">
      Toko: <a href="{{ $store->url() }}" style="color:var(--ink-deep);font-weight:600;text-decoration:none">{{ $store->name }}</a>
      @if($product->category) · Kategori: <span style="color:var(--ink-deep)">{{ $product->category->name }}</span>@endif
      @if($product->sku) · SKU: <span style="font-family:monospace">{{ $product->sku }}</span>@endif
    </p>

    {{-- 3. Deskripsi di Bawah Stok --}}
    @if($product->description)
      <div class="pdp-description-box" style="margin:16px 0 20px;padding:16px 0;border-top:1px solid var(--hairline-soft);border-bottom:1px solid var(--hairline-soft)">
        <h3 style="font-size:14px;font-weight:700;color:var(--ink-deep);margin:0 0 8px;text-transform:uppercase;letter-spacing:0.5px">Deskripsi Produk</h3>
        <div style="color:#475569;font-size:14px;line-height:1.65;white-space:pre-line">{{ $product->description }}</div>
      </div>
    @endif

    {{-- 4. Tombol Aksi Rapikan --}}
    <div class="pdp-actions-container" style="margin-top:20px">
      @if($product->stock > 0)
        <div style="display:flex;flex-direction:column;gap:10px">
          {{-- Tombol Utama: Beli Sekarang --}}
          <button type="button" class="btn btn-buy-now" style="width:100%;padding:14px 20px;font-size:15px;font-weight:700;border-radius:var(--r-full);display:flex;align-items:center;justify-content:center;gap:8px" onclick="openProductBuy({
              id: {{ $product->id }},
              name: @js($product->name),
              price: 'Rp {{ number_format($product->selling_price, 0, ',', '.') }}',
              rawPrice: {{ (float) $product->selling_price }},
              stock: {{ (int) $product->stock }},
              imageUrl: @js($product->imageUrl()),
              url: @js($product->url($store)),
              storeSlug: @js($store->slug),
              storeName: @js($store->name),
              storePhone: @js($store->cleanPhoneNumber()),
              storeQrUrl: @js($store->qrImageUrl()),
              storeQrName: @js($store->qr_account_name)
          })">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            <span>Beli Sekarang via QR</span>
          </button>

          {{-- Baris Kedua: + Keranjang dan Tanya Penjual --}}
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <button type="button" class="btn btn-cart-pdp" style="width:100%;padding:11px 16px;font-size:14px;font-weight:700;border-radius:var(--r-full);display:flex;align-items:center;justify-content:center;gap:8px" onclick="addToCart({{ $product->id }})">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
              <span>+ Keranjang</span>
            </button>

            <button type="button" class="btn btn-wa-chat" style="width:100%;padding:11px 16px;font-size:14px;font-weight:700;border-radius:var(--r-full);display:flex;align-items:center;justify-content:center;gap:8px" onclick="openProductChat({
                id: {{ $product->id }},
                name: @js($product->name),
                price: 'Rp {{ number_format($product->selling_price, 0, ',', '.') }}',
                description: @js(Str::limit(strip_tags($product->description ?? ''), 250)),
                imageUrl: @js($product->imageUrl()),
                url: @js($product->url($store)),
                storeName: @js($store->name),
                storePhone: @js($store->cleanPhoneNumber())
            })">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
              <span>Tanya Penjual</span>
            </button>
          </div>
        </div>
      @else
        <div style="display:flex;flex-direction:column;gap:10px">
          <div class="btn-out-of-stock-banner" style="width:100%;padding:13px 18px;border-radius:var(--r-full);display:flex;align-items:center;justify-content:center;gap:8px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-weight:600;font-size:14px">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            <span>Stok Habis — Tidak Dapat Dibeli</span>
          </div>

          <button type="button" class="btn btn-wa-chat" style="width:100%;padding:12px 18px;font-size:14px;font-weight:700;border-radius:var(--r-full);display:flex;align-items:center;justify-content:center;gap:8px" onclick="openProductChat({
              id: {{ $product->id }},
              name: @js($product->name),
              price: 'Rp {{ number_format($product->selling_price, 0, ',', '.') }}',
              description: @js(Str::limit(strip_tags($product->description ?? ''), 250)),
              imageUrl: @js($product->imageUrl()),
              url: @js($product->url($store)),
              storeName: @js($store->name),
              storePhone: @js($store->cleanPhoneNumber())
          })">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
            <span>Tanya Ketersediaan ke Penjual</span>
          </button>
        </div>
      @endif
    </div>

    @if($product->isBundle() && $product->bundleItems->count() > 0)
      <div style="margin-top:24px;padding:16px;background:var(--surface-soft);border:1px solid var(--hairline-soft);border-radius:var(--r-xl)">
        <div style="font-weight:700;font-size:15px;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between">
          <span>Komponen Paket ({{ $product->bundleItems->count() }} Produk)</span>
          <span class="badge badge-bundle">Paket Set</span>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px">
          @foreach($product->bundleItems as $item)
            <div style="display:flex;align-items:center;gap:12px;background:var(--canvas);padding:10px 12px;border-radius:var(--r-lg);border:1px solid var(--hairline-soft)">
              <img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}" style="width:48px;height:48px;object-fit:cover;border-radius:var(--r-lg);flex-shrink:0">
              <div style="flex:1;min-width:0">
                <a href="{{ route('store.product', [$store->slug, $item->slug]) }}" style="font-weight:600;font-size:14px;color:var(--ink-deep);text-decoration:none;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                  {{ $item->name }}
                </a>
                <div style="font-size:12px;color:var(--steel)">
                  {{ $item->pivot->quantity }}x · Rp {{ number_format($item->selling_price, 0, ',', '.') }} / unit
                  · <span style="{{ $item->stock > 0 ? 'color:var(--success)' : 'color:var(--critical)' }}">Stok: {{ $item->stock }}</span>
                </div>
              </div>
              <a href="{{ route('store.product', [$store->slug, $item->slug]) }}" class="btn btn-secondary btn-sm" style="font-size:12px;padding:4px 10px;text-decoration:none">
                Lihat
              </a>
            </div>
          @endforeach
        </div>
      </div>
    @endif

    @if(! $product->isBundle() && $product->partOfBundles->count() > 0)
      <div style="margin-top:24px;padding:16px;background:#f0f7ff;border:1px solid #bfdbfe;border-radius:var(--r-xl)">
        <div style="font-weight:700;font-size:14px;color:#1e40af;margin-bottom:10px">
          Tersedia dalam Paket Setelan Hemat:
        </div>
        <div style="display:flex;flex-direction:column;gap:8px">
          @foreach($product->partOfBundles as $bundle)
            <a href="{{ route('store.product', [$store->slug, $bundle->slug]) }}" style="display:flex;align-items:center;justify-content:space-between;text-decoration:none;background:var(--canvas);padding:10px 12px;border-radius:var(--r-lg);border:1px solid #dbeafe">
              <div style="min-width:0;flex:1;margin-right:8px">
                <strong style="color:var(--ink-deep);font-size:13px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $bundle->name }}</strong>
                <div style="font-size:12px;color:var(--steel)">
                  Rp {{ number_format($bundle->selling_price, 0, ',', '.') }}
                  @if($bundle->bundleSavings() > 0)
                    · <span style="color:var(--success);font-weight:600">Hemat Rp {{ number_format($bundle->bundleSavings(), 0, ',', '.') }}</span>
                  @endif
                </div>
              </div>
              <span class="btn btn-primary btn-sm" style="font-size:12px;padding:4px 10px;white-space:nowrap">Lihat Set</span>
            </a>
          @endforeach
        </div>
      </div>
    @endif
  </div>
</div>
@if($related->count())
<h2 class="section-title">Produk terkait</h2>
<div class="grid grid-4">@foreach($related as $rel)<x-product-card :product="$rel" />@endforeach</div>
@endif

<script>
  let currentMainImgIndex = 0;
  function switchPdpMainImage(index, url) {
    currentMainImgIndex = index;
    const mainImg = document.getElementById('pdpMainImage');
    if (mainImg) mainImg.src = url;
    document.querySelectorAll('.pdp-thumb-btn').forEach(function(btn, i) {
      if (i === index) btn.classList.add('active');
      else btn.classList.remove('active');
    });
  }
</script>
@endsection

