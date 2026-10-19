@props(['product'])
<a class="card-photo" href="{{ route('store.product', [$product->store->slug ?? $product->store_id, $product->slug]) }}">
  <div style="position:relative">
    <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy">
    @if($product->isBundle())
      <span class="badge badge-bundle" style="position:absolute;top:12px;left:12px;box-shadow:0 2px 6px rgba(0,0,0,0.12)">Paket Set</span>
    @endif
  </div>
  <div class="card-photo-body">
    <h3 style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:42px">{{ $product->name }}</h3>
    <div class="price" style="display:flex;align-items:baseline;gap:6px;flex-wrap:wrap">
      <span>Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
      @if($product->isBundle() && $product->bundleSavings() > 0)
        <span style="font-size:12px;color:var(--steel);text-decoration:line-through;font-weight:normal">Rp {{ number_format($product->bundleOriginalPrice(), 0, ',', '.') }}</span>
        <span style="font-size:11px;color:var(--success);font-weight:700">Hemat Rp {{ number_format($product->bundleSavings(), 0, ',', '.') }}</span>
      @endif
    </div>
    <div class="store-name" style="margin-top:6px">{{ $product->store->name ?? '' }}@if($product->stock <= 0) · <span class="badge badge-critical">Habis</span>@elseif($product->stock <= 5) · <span class="badge badge-warning">Sisa {{ $product->stock }}</span>@else · <span class="badge badge-success">Tersedia</span>@endif</div>
  </div>
</a>
