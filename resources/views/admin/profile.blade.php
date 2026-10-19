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
  <div class="field" style="margin-bottom:20px;padding:14px;background:var(--surface-soft);border-radius:var(--r-lg)">
    <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;margin:0">
      <input type="checkbox" name="hide_component_products" value="1" {{ old('hide_component_products', $store->hide_component_products) ? 'checked' : '' }} style="margin-top:3px;width:16px;height:16px">
      <div>
        <strong>Sembunyikan otomatis produk satuan komponen setelan</strong>
        <div style="font-size:13px;color:var(--steel);margin-top:2px">
          Semua produk tunggal yang telah digabungkan ke dalam paket setelan akan otomatis disembunyikan dari etalase katalog publik toko.
        </div>
      </div>
    </label>
  </div>
  <button class="btn btn-buy" type="submit">Simpan</button>
</form>
</div>
<script>document.querySelector('input[name=slug]').addEventListener('input', e => document.getElementById('slugprev').textContent = e.target.value || '{{ $store->slug }}');</script>
@endsection
