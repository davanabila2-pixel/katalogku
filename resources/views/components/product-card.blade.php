@props(['product'])
@php
  $storeObj = $product->store;
  $storeSlug = $storeObj->slug ?? $product->store_id;
  $storePhone = $storeObj ? $storeObj->cleanPhoneNumber() : null;
  $productUrl = route('store.product', [$storeSlug, $product->slug]);
@endphp
<a class="card-photo" href="{{ $productUrl }}">
  <div style="position:relative">
    <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy">
    @if($product->isBundle())
      <span class="badge badge-bundle" style="position:absolute;top:8px;left:8px;box-shadow:0 2px 6px rgba(0,0,0,0.12)">Paket Set</span>
    @endif
  </div>
  <div class="card-photo-body">
    <h3 style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">{{ $product->name }}</h3>
    <div class="price" style="display:flex;align-items:baseline;gap:6px;flex-wrap:wrap">
      <span>Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
      @if($product->isBundle() && $product->bundleSavings() > 0)
        <span style="font-size:11px;color:var(--steel);text-decoration:line-through;font-weight:normal">Rp {{ number_format($product->bundleOriginalPrice(), 0, ',', '.') }}</span>
        <span style="font-size:11px;color:var(--success);font-weight:700">Hemat Rp {{ number_format($product->bundleSavings(), 0, ',', '.') }}</span>
      @endif
    </div>
    <div class="card-footer-action">
      <div class="card-stock-status">
        @if($product->stock <= 0)
          <span class="badge badge-critical">Habis</span>
        @elseif($product->stock <= 5)
          <span class="badge badge-warning">Sisa {{ $product->stock }}</span>
        @else
          <span class="badge badge-success">Tersedia</span>
        @endif
      </div>
      <div class="card-btn-group">
        <button type="button" class="btn-card-ask-wa" onclick="event.preventDefault(); event.stopPropagation(); openProductChat({
            id: {{ $product->id }},
            name: @js($product->name),
            price: 'Rp {{ number_format($product->selling_price, 0, ',', '.') }}',
            rawPrice: {{ (float) $product->selling_price }},
            description: @js(Str::limit(strip_tags($product->description ?? ''), 150)),
            imageUrl: @js($product->imageUrl()),
            url: @js($productUrl),
            storeName: @js($storeObj->name ?? ''),
            storePhone: @js($storePhone)
        })" title="Tanya ketersediaan produk via WhatsApp">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
          <span>Tanya</span>
        </button>
        @if($product->stock > 0)
          <button type="button" class="btn-card-buy" onclick="event.preventDefault(); event.stopPropagation(); openProductBuy({
              id: {{ $product->id }},
              name: @js($product->name),
              price: 'Rp {{ number_format($product->selling_price, 0, ',', '.') }}',
              rawPrice: {{ (float) $product->selling_price }},
              stock: {{ (int) $product->stock }},
              imageUrl: @js($product->imageUrl()),
              url: @js($productUrl),
              storeName: @js($storeObj->name ?? ''),
              storePhone: @js($storePhone),
              storeQrUrl: @js($storeObj ? $storeObj->qrImageUrl() : null),
              storeQrName: @js($storeObj->qr_account_name ?? '')
          })" title="Beli sekarang via QR Toko">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            <span>Beli</span>
          </button>
        @endif
      </div>
    </div>
  </div>
</a>
