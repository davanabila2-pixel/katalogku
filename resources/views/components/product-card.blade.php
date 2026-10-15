@props(['product'])
<a class="card-photo" href="{{ route('store.product', [$product->store->slug ?? $product->store_id, $product->slug]) }}">
  <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy">
  <div class="card-photo-body">
    <h3>{{ $product->name }}</h3>
    <div class="price">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</div>
    <div class="store-name">{{ $product->store->name ?? '' }}@if($product->stock <= 0) · <span class="badge badge-critical">Habis</span>@elseif($product->stock <= 5) · <span class="badge badge-warning">Sisa {{ $product->stock }}</span>@else · <span class="badge badge-success">Tersedia</span>@endif</div>
  </div>
</a>
