@extends('layouts.app')
@section('content')
<h2 class="section-title">Semua toko</h2>
<p class="section-sub">Setiap toko punya katalog publik sendiri.</p>
<form method="GET" class="filter-bar"><input class="search-pill" type="search" name="q" value="{{ request('q') }}" placeholder="Cari toko…"><button class="btn btn-primary btn-sm" type="submit">Cari</button></form>
<x-paged :items="$stores" empty="Tidak ada toko yang cocok.">
<div class="grid grid-4">
  @foreach($stores as $store)
  <a class="card-photo" href="{{ $store->url() }}">
    <img src="{{ $store->logo_path ? asset('storage/'.$store->logo_path) : asset('images/product-fallback.svg') }}" alt="{{ $store->name }}" loading="lazy">
    <div class="card-photo-body"><h3>{{ $store->name }}</h3><div class="store-name">{{ Str::limit($store->description, 80) }}</div></div>
  </a>
  @endforeach
</div>
</x-paged>
@endsection
