@extends('layouts.dashboard')
@section('title', ($category->exists ? 'Edit' : 'Tambah').' Kategori')
@section('subtitle', $store->name)
@section('content')
<div class="panel form-card">
<form method="POST" action="{{ $category->exists ? route('admin.categories.update', [$store, $category]) : route('admin.categories.store', $store) }}" enctype="multipart/form-data">
@csrf @if($category->exists)@method('PUT')@endif
  <div class="field"><label>Nama</label><input class="input" type="text" name="name" value="{{ old('name', $category->name) }}" required></div>
  <div class="field"><label>Slug (opsional — otomatis dari nama)</label><input class="input" type="text" name="slug" value="{{ old('slug', $category->slug) }}"></div>
  <div class="field"><label>Kategori Induk / Tingkat Hirarki</label>
    <select class="select" name="parent_id"><option value="">— Kategori Utama (Tingkat Teratas / Induk) —</option>
    @foreach($parents as $p)<option value="{{ $p->id }}" {{ (string)old('parent_id', $category->parent_id) === (string)$p->id ? 'selected' : '' }}>Subkategori di bawah: {{ $p->name }}</option>@endforeach</select>
    <span class="help" style="font-size:12px;color:#64748b;margin-top:4px;display:block">Pilih "Kategori Utama" jika ini kelompok produk besar. Pilih kategori induk jika ini adalah cabang/subkategori.</span>
  </div>
  <div class="field"><label>Deskripsi</label><textarea class="textarea" name="description">{{ old('description', $category->description) }}</textarea></div>
  <div class="form-row">
    <div class="field"><label>Urutan tampil</label><input class="input" type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0"></div>
    <div class="field"><label>&nbsp;</label><label style="font-weight:400"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}> Aktif (tampil di katalog)</label></div>
  </div>
  <div class="field"><label>Gambar (opsional)</label><input type="file" name="image" accept="image/*"></div>
  <button class="btn btn-buy" type="submit">Simpan</button>
  <a class="btn btn-ghost" href="{{ route('admin.categories.index', $store) }}">Batal</a>
</form>
</div>
@endsection
