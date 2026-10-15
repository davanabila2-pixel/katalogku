@extends('layouts.app')
@section('content')
<nav class="breadcrumb"><a href="{{ route('home') }}">Beranda</a> › <a href="{{ $store->url() }}">{{ $store->name }}</a> › {{ $product->name }}</nav>
<div class="pdp">
  <div class="pdp-gallery"><img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}"></div>
  <div class="pdp-rail">
    <h1 style="font-size:28px;margin:0 0 8px">{{ $product->name }}</h1>
    <div class="price" style="font-size:24px;color:var(--primary)">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</div>
    <p style="color:var(--steel);font-size:14px">Toko: <a href="{{ $store->url() }}">{{ $store->name }}</a>@if($product->category) · Kategori: {{ $product->category->name }}@endif @if($product->sku)· SKU: {{ $product->sku }}@endif</p>
    <p>
      @if($product->stock <= 0)<span class="badge badge-critical">Stok habis</span>
      @elseif($product->stock <= 5)<span class="badge badge-warning">Sisa {{ $product->stock }}</span>
      @else<span class="badge badge-success">Stok tersedia</span>@endif
    </p>
    <a class="btn btn-buy" href="{{ $store->url() }}">← Kembali ke katalog</a>
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
@endsection
