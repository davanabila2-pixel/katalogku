@extends('layouts.app')
@section('content')
<nav class="breadcrumb"><a href="{{ route('home') }}">Beranda</a> › {{ $store->name }}</nav>
@php
  $hasBanner = $store->hasBanner() && $store->bannerUrl();
  $soldCount = isset($soldOrders) ? $soldOrders->count() : 0;
@endphp
<div class="hero store-hero {{ $hasBanner ? 'has-store-banner' : '' }}" style="@if($hasBanner) background-image: linear-gradient(135deg, rgba(15, 23, 42, 0.88) 0%, rgba(15, 23, 42, 0.62) 55%, rgba(15, 23, 42, 0.38) 100%), url('{!! $store->bannerUrl() !!}'); background-size: cover; background-position: center; @endif">
  <div class="store-hero-content">
    <div class="store-hero-text">
      <h1 class="store-hero-title">{{ $store->name }}</h1>
      @if($store->slogan)<p class="store-hero-slogan">{{ $store->slogan }}</p>@endif
      @if($store->description)<p class="store-hero-desc">{{ $store->description }}</p>@endif
      @if($store->contact_information)
        <p class="store-hero-contact">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline-block;vertical-align:-2px;margin-right:4px"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          Kontak: {{ $store->contact_information }}
        </p>
      @endif

      {{-- Tombol Aksi Hero Banner --}}
      <div class="store-hero-actions">
        <a href="#katalog-produk" class="btn-hero-action btn-hero-explore" onclick="scrollToProducts(event)">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
          <span>Lihat Selengkapnya</span>
        </a>
        <a href="{{ route('store.sold', $store->slug) }}" class="btn-hero-action btn-hero-sold">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
          <span>Produk Terjual ({{ $soldCount }})</span>
        </a>
      </div>
    </div>
  </div>
</div>

<div id="katalog-produk">
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
</div>

<script>
function scrollToProducts(e) {
  if (e) e.preventDefault();
  var el = document.getElementById('katalog-produk');
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}
</script>
@endsection
