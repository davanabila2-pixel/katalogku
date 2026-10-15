@extends('layouts.dashboard')
@section('title', 'Profil Toko')
@section('subtitle', $store->name.' — katalog: '.$store->url())
@section('nav')
<a href="{{ route('admin.dashboard', $store) }}">Dashboard</a>
<a class="active" href="{{ route('admin.profile.edit', $store) }}">Profil Toko</a>
<a href="{{ route('admin.categories.index', $store) }}">Kategori</a>
<a href="{{ route('admin.products.index', $store) }}">Produk</a>
<a href="{{ route('admin.batches.index', $store) }}">Batch Pembelian</a>
<a href="{{ route('admin.stock.index', $store) }}">Stok</a>
<a href="{{ route('admin.stock.history', $store) }}">Riwayat Stok</a>
<a href="{{ route('admin.activity', $store) }}">Aktivitas</a>
@endsection
@section('content')
<div class="panel form-card">
<form method="POST" action="{{ route('admin.profile.update', $store) }}" enctype="multipart/form-data">@csrf @method('PUT')
  <div class="field"><label>Nama toko</label><input class="input" type="text" name="name" value="{{ old('name', $store->name) }}" required></div>
  <div class="field"><label>Slug URL (kosongkan = otomatis; hati-hati, tautan lama dialihkan 301)</label><input class="input" type="text" name="slug" value="{{ old('slug', $store->slug) }}" pattern="[a-z0-9]+(?:-[a-z0-9]+)*"><small class="category-hierarchy">Preview: {{ url('/') }}/<span id="slugprev">{{ $store->slug }}</span></small></div>
  <div class="field"><label>Deskripsi</label><textarea class="textarea" name="description">{{ old('description', $store->description) }}</textarea></div>
  <div class="field"><label>Kontak</label><input class="input" type="text" name="contact_information" value="{{ old('contact_information', $store->contact_information) }}"></div>
  <div class="field"><label>Alamat</label><textarea class="textarea" name="address">{{ old('address', $store->address) }}</textarea></div>
  <div class="field"><label>Logo</label><input type="file" name="logo" accept="image/*"></div>
  <button class="btn btn-buy" type="submit">Simpan</button>
</form>
</div>
<script>document.querySelector('input[name=slug]').addEventListener('input', e => document.getElementById('slugprev').textContent = e.target.value || '{{ $store->slug }}');</script>
@endsection
