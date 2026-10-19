@extends('layouts.dashboard')
@section('title', ($store->exists ? 'Edit' : 'Buat').' Toko')
@section('subtitle', $store->exists ? 'ID internal #'.$store->id.' (relasi tidak berubah saat slug diganti)' : 'Slug dibuat otomatis dari nama')
@section('content')
<div class="panel form-card">
<form method="POST" action="{{ $store->exists ? route('superadmin.stores.update', $store) : route('superadmin.stores.store') }}" enctype="multipart/form-data">
@csrf @if($store->exists)@method('PUT')@endif
  <div class="field"><label>Nama toko</label><input id="storename" class="input" type="text" name="name" value="{{ old('name', $store->name) }}" required></div>
  <div class="field"><label>Slug URL</label><input id="storeslug" class="input" type="text" name="slug" value="{{ old('slug', $store->slug) }}" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" placeholder="otomatis dari nama">
  <small class="category-hierarchy">Preview URL: {{ url('/') }}/<span id="slugprev">{{ $store->slug ?? 'slug-toko' }}</span>@if($store->exists) · mengubah slug mengalihkan tautan lama (301)@endif</small></div>
  <div class="field"><label>Deskripsi</label><textarea class="textarea" name="description">{{ old('description', $store->description) }}</textarea></div>
  <div class="field"><label>Kontak</label><input class="input" type="text" name="contact_information" value="{{ old('contact_information', $store->contact_information) }}"></div>
  <div class="field"><label>Alamat</label><textarea class="textarea" name="address">{{ old('address', $store->address) }}</textarea></div>
  <div class="field"><label>Status</label><select class="select" name="status"><option value="active" {{ old('status', $store->status) === 'active' ? 'selected' : '' }}>Aktif</option><option value="inactive" {{ old('status', $store->status) === 'inactive' ? 'selected' : '' }}>Nonaktif</option></select></div>
  <div class="field"><label>Logo</label><input type="file" name="logo" accept="image/*"></div>
  <h3>Admin utama</h3>
  <div class="form-row">
    <div class="field"><label>Email admin</label><input class="input" type="email" name="admin_email" value="{{ old('admin_email', $store->exists ? $store->primaryAdmin()?->email : '') }}"></div>
    <div class="field"><label>Nama admin (jika akun baru)</label><input class="input" type="text" name="admin_name" value="{{ old('admin_name') }}"></div>
  </div>
  <button class="btn btn-buy" type="submit">Simpan</button>
  <a class="btn btn-ghost" href="{{ route('superadmin.stores.index') }}">Batal</a>
</form>
</div>
<script>
const nameEl = document.getElementById('storename'), slugEl = document.getElementById('storeslug'), prev = document.getElementById('slugprev');
function autoSlug(s){ return s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').substring(0,100); }
nameEl.addEventListener('input', () => { if (!slugEl.dataset.touched) { slugEl.value = autoSlug(nameEl.value); prev.textContent = slugEl.value || 'slug-toko'; } });
slugEl.addEventListener('input', () => { slugEl.dataset.touched = '1'; prev.textContent = slugEl.value || 'slug-toko'; });
</script>
@endsection
