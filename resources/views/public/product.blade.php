@extends('layouts.app')
@section('content')
<nav class="breadcrumb"><a href="{{ route('home') }}">Beranda</a> › <a href="{{ $store->url() }}">{{ $store->name }}</a> › {{ $product->name }}</nav>
@php
  $galleryUrls = $product->galleryUrls();
@endphp
<div class="pdp">
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
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px">
      <h1 style="font-size:28px;margin:0 0 8px">{{ $product->name }}</h1>
    </div>

    @if(!$product->isBundle() && ($product->is_hidden_from_catalog || ($store->hide_component_products && $product->partOfBundles->count() > 0)))
      <div style="background:#fffbeb;color:#92400e;border:1px solid #fde68a;border-radius:var(--r-lg);padding:8px 12px;font-size:13px;margin-bottom:12px">
        ℹ️ Produk satuan ini disembunyikan dari katalog umum toko dan diprioritaskan sebagai bagian dari paket setelan.
      </div>
    @endif

    <div class="price" style="font-size:24px;color:var(--primary);display:flex;align-items:baseline;gap:8px;flex-wrap:wrap">
      <span>Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
      @if($product->isBundle() && $product->bundleSavings() > 0)
        <span style="font-size:15px;color:var(--steel);text-decoration:line-through;font-weight:normal">Rp {{ number_format($product->bundleOriginalPrice(), 0, ',', '.') }}</span>
        <span class="badge badge-success" style="font-size:12px">Hemat Rp {{ number_format($product->bundleSavings(), 0, ',', '.') }}</span>
      @endif
    </div>

    <p style="color:var(--steel);font-size:14px;margin:8px 0 16px">Toko: <a href="{{ $store->url() }}">{{ $store->name }}</a>@if($product->category) · Kategori: {{ $product->category->name }}@endif @if($product->sku) · SKU: {{ $product->sku }}@endif</p>

    <div style="margin-bottom:16px">
      @if($product->stock <= 0)
        <span class="badge badge-critical">Stok {{ $product->isBundle() ? 'paket ' : '' }}habis</span>
      @elseif($product->stock <= 5)
        <span class="badge badge-warning">Sisa {{ $product->stock }} {{ $product->isBundle() ? 'paket' : 'unit' }}</span>
      @else
        <span class="badge badge-success">Stok tersedia ({{ $product->stock }} {{ $product->isBundle() ? 'paket' : 'unit' }})</span>
      @endif

      @if($product->isBundle())
        <div style="font-size:12px;color:var(--steel);margin-top:6px">
          *Stok paket setelan dihitung otomatis dari ketersediaan produk satuannya.
        </div>
      @endif
    </div>

    <div class="pdp-action-group">
      @if($product->stock > 0)
        <button type="button" class="btn btn-buy-now" onclick="openProductBuy({
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
        <button type="button" class="btn btn-cart-pdp" onclick="addToCart({{ $product->id }})">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
          <span>+ Keranjang</span>
        </button>
      @else
        <div class="btn-out-of-stock-banner">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
          <span>Stok Habis — Tidak Dapat Dibeli / Dibayar</span>
        </div>
      @endif
      <button type="button" class="btn btn-wa-chat" onclick="openProductChat({
          id: {{ $product->id }},
          name: @js($product->name),
          price: 'Rp {{ number_format($product->selling_price, 0, ',', '.') }}',
          description: @js(Str::limit(strip_tags($product->description ?? ''), 250)),
          imageUrl: @js($product->imageUrl()),
          url: @js($product->url($store)),
          storeName: @js($store->name),
          storePhone: @js($store->cleanPhoneNumber())
      })">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
        <span>Tanya Penjual</span>
      </button>
      <a class="btn btn-secondary" href="{{ $store->url() }}">← Kembali</a>
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

    @if($product->description)
    <h3 style="margin-top:24px">Deskripsi</h3>
    <p>{{ $product->description }}</p>
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

