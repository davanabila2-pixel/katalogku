@extends('layouts.app')
@section('content')
<div class="hero">
  <h1>Jelajahi katalog semua toko.</h1>
  <p>Satu aplikasi, satu domain, banyak toko. Pilih toko favoritmu dan temukan produk terbaik.</p>
  <div class="hero-cta">
    <a class="btn btn-primary" href="{{ route('stores.index') }}">Lihat Toko</a>
    <a class="btn btn-secondary" href="#produk">Produk Terbaru</a>
  </div>
</div>

<h2 class="section-title">Toko aktif</h2>
<p class="section-sub">Katalog masing-masing toko berdasarkan slug-nya sendiri.</p>
@if($stores->count())
<div class="grid grid-4">
  @foreach($stores as $store)
  <a class="card-photo" href="{{ $store->url() }}">
    <img src="{{ $store->logo_path ? asset('storage/'.$store->logo_path) : asset('images/product-fallback.svg') }}" alt="{{ $store->name }}" loading="lazy">
    <div class="card-photo-body"><h3>{{ $store->name }}</h3><div class="store-name">{{ $store->products()->where('status','active')->count() }} produk</div></div>
  </a>
  @endforeach
</div>
@else
<div class="empty"><h3>Belum ada toko</h3><p>Toko akan tampil di sini setelah Superadmin menambahkannya.</p></div>
@endif

<h2 class="section-title" id="produk">Produk terbaru</h2>
<p class="section-sub">Pilihan terbaru dari seluruh toko aktif.</p>
@if($products->count())
<div class="grid grid-4">
  @foreach($products as $product)<x-product-card :product="$product" />@endforeach
</div>
@else
<div class="empty"><h3>Belum ada produk</h3><p>Produk aktif akan tampil di sini.</p></div>
@endif
@endsection
