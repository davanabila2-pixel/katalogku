@extends('layouts.app')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;margin:24px 0">
  <div>
    <h2 class="section-title" style="margin:0 0 6px">Semua toko</h2>
    <p class="section-sub" style="margin:0">Setiap toko punya katalog publik sendiri.</p>
  </div>
  <x-search-form placeholder="Cari toko…" button-text="Cari" width="300px" />
</div>
<x-paged :items="$stores" countSuffix="toko" empty="Tidak ada toko yang cocok.">
<div class="grid grid-4">
  @foreach($stores as $store)
  <a class="card-photo" href="{{ $store->url() }}">
    <img src="{{ $store->logoUrl() ?? asset('images/product-fallback.svg') }}" alt="{{ $store->name }}" loading="lazy">
    <div class="card-photo-body"><h3>{{ $store->name }}</h3><div class="store-name">{{ Str::limit($store->description, 80) }}</div></div>
  </a>
  @endforeach
</div>
</x-paged>
@endsection
