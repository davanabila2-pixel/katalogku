@extends('layouts.app')
@section('content')
<nav class="breadcrumb"><a href="{{ route('home') }}">Beranda</a> › {{ $store->name }}</nav>
<div class="hero" style="padding:48px">
  <h1 style="font-size:48px">{{ $store->name }}</h1>
  @if($store->description)<p>{{ $store->description }}</p>@endif
  @if($store->contact_information)<p style="font-size:14px">Kontak: {{ $store->contact_information }}</p>@endif
</div>

<div class="filter-bar">
  <a class="pill-tab {{ !$activeCategory ? 'active' : '' }}" href="{{ $store->url() }}">Semua</a>
  @foreach($rootCategories as $cat)
    <a class="pill-tab {{ $activeCategory && $activeCategory->id === $cat->id ? 'active' : '' }}" href="{{ route('store.category', [$store->slug, $cat->slug]) }}">{{ $cat->name }}</a>
  @endforeach
</div>

@if($activeCategory && $activeCategory->children->count())
<div class="filter-bar">
  @foreach($activeCategory->children as $sub)
    <a class="pill-tab" href="{{ route('store.category', [$store->slug, $activeCategory->slug, $sub->slug]) }}">{{ $sub->name }}</a>
  @endforeach
</div>
@endif
@if($activeCategory && $activeCategory->parent)
<p class="category-hierarchy">Kategori: <a href="{{ route('store.category', [$store->slug, $activeCategory->parent->slug]) }}">{{ $activeCategory->parent->name }}</a> › {{ $activeCategory->name }}</p>
@endif

<form method="GET" class="filter-bar">
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <input class="input" style="width:130px;height:40px;padding:0 12px;font-size:14px" type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Min Rp" min="0">
    <input class="input" style="width:130px;height:40px;padding:0 12px;font-size:14px" type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Max Rp" min="0">
    <label style="font-size:14px;display:flex;align-items:center;gap:6px;cursor:pointer"><input type="checkbox" name="in_stock" value="1" {{ request('in_stock') ? 'checked' : '' }}> Stok tersedia</label>
    <select class="select" style="width:150px;height:40px;padding:0 12px;font-size:14px" name="sort">
      <option value="">Terbaru</option>
      <option value="price_asc" {{ request('sort')==='price_asc'?'selected':'' }}>Termurah</option>
      <option value="price_desc" {{ request('sort')==='price_desc'?'selected':'' }}>Termahal</option>
      <option value="name" {{ request('sort')==='name'?'selected':'' }}>Nama A–Z</option>
    </select>
  </div>
  <div style="display:flex;gap:10px;align-items:center;margin-left:auto;flex-wrap:wrap">
    <x-search-input placeholder="Cari produk…" width="240px" />
    <button class="btn btn-primary btn-sm" type="submit">Terapkan</button>
  </div>
</form>

<x-paged :items="$products" empty="Tidak ada produk pada filter ini.">
<div class="grid grid-4">
  @foreach($products as $product)<x-product-card :product="$product" />@endforeach
</div>
</x-paged>
@endsection
