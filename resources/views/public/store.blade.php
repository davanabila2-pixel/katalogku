@extends('layouts.app')
@section('content')
<nav class="breadcrumb"><a href="{{ route('home') }}">Beranda</a> › {{ $store->name }}</nav>
<div class="hero" style="padding:48px">
  <h1 style="font-size:48px">{{ $store->name }}</h1>
  @if($store->description)<p>{{ $store->description }}</p>@endif
  @if($store->contact_information)<p style="font-size:14px">Kontak: {{ $store->contact_information }}</p>@endif
</div>

{{-- Slider Kategori Utama --}}
<div class="category-slider-container">
  <div class="category-slider-track">
    <a class="pill-tab {{ !$activeCategory ? 'active' : '' }}" href="{{ $store->url() }}">Semua</a>
    @foreach($rootCategories as $cat)
      <a class="pill-tab {{ $activeCategory && ($activeCategory->id === $cat->id || $activeCategory->parent_id === $cat->id) ? 'active' : '' }}" href="{{ route('store.category', [$store->slug, $cat->slug]) }}">{{ $cat->name }}</a>
    @endforeach
  </div>
</div>

{{-- Slider Subkategori --}}
@php
  $currentSubcategories = collect();
  $parentCategorySlug = '';
  if ($activeCategory) {
    if ($activeCategory->children->count() > 0) {
      $currentSubcategories = $activeCategory->children;
      $parentCategorySlug = $activeCategory->slug;
    } elseif ($activeCategory->parent && $activeCategory->parent->children->count() > 0) {
      $currentSubcategories = $activeCategory->parent->children;
      $parentCategorySlug = $activeCategory->parent->slug;
    }
  }
@endphp

@if($currentSubcategories->count() > 0)
<div class="category-slider-container subcategory-slider-container">
  <div class="category-slider-track subcategory-slider-track">
    @foreach($currentSubcategories as $sub)
      <a class="pill-tab {{ $activeCategory->id === $sub->id ? 'active' : '' }}" href="{{ route('store.category', [$store->slug, $parentCategorySlug, $sub->slug]) }}">{{ $sub->name }}</a>
    @endforeach
  </div>
</div>
@endif

@if($activeCategory && $activeCategory->parent)
<p class="category-hierarchy">Kategori: <a href="{{ route('store.category', [$store->slug, $activeCategory->parent->slug]) }}">{{ $activeCategory->parent->name }}</a> › {{ $activeCategory->name }}</p>
@endif

<form method="GET" class="store-filter-form">
  {{-- Baris 1: Pencarian & Tombol Terapkan Bersatu Rapi --}}
  <div class="store-search-action-row">
    <div class="store-search-input-wrap">
      <x-search-input placeholder="Cari produk di toko ini…" />
    </div>
    <button class="btn btn-primary btn-filter-submit" type="submit">
      <span>Terapkan</span>
    </button>
  </div>

  {{-- Baris 2: Filter Harga, Urutan, dan Ketersediaan Stok --}}
  <div class="store-filter-options-row">
    <div class="filter-price-inputs">
      <input class="input filter-input-price" type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Min Rp" min="0">
      <span class="filter-price-dash">–</span>
      <input class="input filter-input-price" type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Max Rp" min="0">
    </div>

    <div class="filter-secondary-inputs">
      <select class="select filter-select-sort" name="sort">
        <option value="">Terbaru</option>
        <option value="price_asc" {{ request('sort')==='price_asc'?'selected':'' }}>Termurah</option>
        <option value="price_desc" {{ request('sort')==='price_desc'?'selected':'' }}>Termahal</option>
        <option value="name" {{ request('sort')==='name'?'selected':'' }}>Nama A–Z</option>
      </select>

      <label class="filter-stock-toggle">
        <input type="checkbox" name="in_stock" value="1" {{ request('in_stock') ? 'checked' : '' }}>
        <span>Stok tersedia</span>
      </label>

      @if(request('q') || request('min_price') || request('max_price') || request('in_stock') || request('sort'))
        <a href="{{ $activeCategory ? route('store.category', [$store->slug, $activeCategory->slug]) : $store->url() }}" class="filter-reset-link" title="Hapus filter">
          ✕ Reset
        </a>
      @endif
    </div>
  </div>
</form>

<x-paged :items="$products" empty="Tidak ada produk pada filter ini.">
<div class="grid grid-4">
  @foreach($products as $product)<x-product-card :product="$product" />@endforeach
</div>
</x-paged>
@endsection
