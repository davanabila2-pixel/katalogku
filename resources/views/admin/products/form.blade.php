@extends('layouts.dashboard')
@section('title', ($product->exists ? 'Edit' : 'Tambah').' Produk')
@section('subtitle', $store->name)
@section('nav')
<a href="{{ route('admin.dashboard', $store) }}">Dashboard</a>
<a href="{{ route('admin.profile.edit', $store) }}">Profil Toko</a>
<a href="{{ route('admin.categories.index', $store) }}">Kategori</a>
<a class="active" href="{{ route('admin.products.index', $store) }}">Produk</a>
<a href="{{ route('admin.batches.index', $store) }}">Batch Pembelian</a>
<a href="{{ route('admin.stock.index', $store) }}">Stok</a>
@endsection
@section('content')
@if($product->exists && isset($hpp))
<div class="panel"><h2>Biaya & Margin (internal)</h2>
<p style="font-size:14px">HPP (rata-rata tertimbang): <strong>{{ $hpp !== null ? 'Rp '.number_format($hpp, 0, ',', '.') : '— (belum ada batch)' }}</strong> ·
Estimasi margin: <strong>{{ $margin !== null ? 'Rp '.number_format($margin, 0, ',', '.') : '—' }}</strong> ·
Stok saat ini: <strong>{{ $product->stock }}</strong></p>
<form method="POST" action="{{ route('admin.products.adjust', [$store, $product]) }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:end">@csrf
  <div class="field" style="margin:0"><label>Koreksi (+/-)</label><input class="input" style="width:120px" type="number" name="quantity" required></div>
  <div class="field" style="margin:0;flex:1;min-width:200px"><label>Alasan (wajib, tercatat di audit)</label><input class="input" type="text" name="reason" required minlength="5" placeholder="cth: stok opname selisih 2 pcs"></div>
  <button class="btn btn-secondary btn-sm" type="submit">Koreksi Stok</button>
</form>
</div>
@endif
<div class="panel form-card">
<form method="POST" action="{{ $product->exists ? route('admin.products.update', [$store, $product]) : route('admin.products.store', $store) }}" enctype="multipart/form-data">
@csrf @if($product->exists)@method('PUT')@endif
  <div class="field"><label>Nama produk</label><input class="input" type="text" name="name" value="{{ old('name', $product->name) }}" required></div>
  <div class="form-row">
    <div class="field"><label>Slug (opsional)</label><input class="input" type="text" name="slug" value="{{ old('slug', $product->slug) }}"></div>
    <div class="field"><label>SKU (opsional)</label><input class="input" type="text" name="sku" value="{{ old('sku', $product->sku) }}"></div>
  </div>
  <div class="field"><label>Kategori</label>
    <select class="select" name="category_id"><option value="">— Tanpa kategori —</option>
    @foreach($categories as $c)<option value="{{ $c->id }}" {{ (string)old('category_id', $product->category_id) === (string)$c->id ? 'selected' : '' }}>{{ $c->parent->name ?? '' }}{{ $c->parent ? ' › ' : '' }}{{ $c->name }}{{ $c->is_active ? '' : ' (nonaktif)' }}</option>@endforeach</select></div>
  <div class="field"><label>Deskripsi</label><textarea class="textarea" name="description">{{ old('description', $product->description) }}</textarea></div>
  <div class="form-row">
    <div class="field"><label>Harga jual (Rp)</label><input class="input" type="number" name="selling_price" value="{{ old('selling_price', $product->selling_price) }}" min="0" step="0.01" required></div>
    <div class="field"><label>Status publikasi</label><select class="select" name="status"><option value="draft" {{ old('status', $product->status) === 'draft' ? 'selected' : '' }}>Draft</option><option value="active" {{ old('status', $product->status) === 'active' ? 'selected' : '' }}>Aktif (tampil publik)</option><option value="inactive" {{ old('status', $product->status) === 'inactive' ? 'selected' : '' }}>Nonaktif</option></select></div>
  </div>
  <div class="field"><label>Gambar</label><input type="file" name="image" accept="image/*"></div>
  <button class="btn btn-buy" type="submit">Simpan</button>
  <a class="btn btn-ghost" href="{{ route('admin.products.index', $store) }}">Batal</a>
</form>
</div>
@endsection
