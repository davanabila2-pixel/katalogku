@extends('layouts.app')
@section('content')
<nav class="breadcrumb" aria-label="Navigasi Halaman">
  <ol class="breadcrumb-list">
    <li class="breadcrumb-item">
      <a href="{{ $store->url() }}" class="breadcrumb-link" title="Katalog {{ $store->name }}">
        <svg class="breadcrumb-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/></svg>
        <span>{{ $store->name }}</span>
      </a>
    </li>
    <li class="breadcrumb-separator" aria-hidden="true">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
    </li>
    <li class="breadcrumb-item active" aria-current="page">
      <span class="breadcrumb-current">Profile Saya</span>
    </li>
  </ol>
</nav>

<div class="user-account-container">
  <div class="user-account-header">
    <div class="user-account-avatar-wrap">
      <img src="{{ $profile->avatarUrl() }}" alt="{{ $profile->name }}" class="user-account-avatar" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
      <div class="user-account-avatar-fallback" style="display:none;">{{ $profile->initial() }}</div>
    </div>
    <div class="user-account-heading">
      <div style="display: inline-flex; align-items: center; gap: 6px; margin-bottom: 4px;">
        <span style="font-size: 12px; font-weight: 700; color: #0284c7; background: #e0f2fe; padding: 2px 8px; border-radius: var(--r-full);">Pelanggan {{ $store->name }}</span>
      </div>
      <h1 class="user-account-title">Profile Saya di {{ $store->name }}</h1>
      <p class="user-account-subtitle">Kelola nama, nomor kontak WhatsApp, dan alamat pengiriman khusus untuk transaksi di toko ini.</p>
    </div>
  </div>

  <div class="user-account-card">
    <form method="POST" action="{{ route('store.profile.update', $store->slug) }}">
      @csrf
      @method('PUT')

      <div class="field">
        <label class="label" for="name">Nama Penerima / Pelanggan di Toko Ini</label>
        <input class="input" type="text" id="name" name="name" value="{{ old('name', $profile->name) }}" required autocomplete="name" placeholder="Nama Anda untuk pesanan toko ini">
      </div>

      <div class="field">
        <label class="label" for="phone">Nomor WhatsApp / Telepon</label>
        <input class="input" type="tel" id="phone" name="phone" value="{{ old('phone', $profile->phone) }}" autocomplete="tel" placeholder="08xxxxxxxxxx (untuk konfirmasi pesanan)">
      </div>

      <div class="field">
        <label class="label" for="address">Alamat Pengiriman Utama</label>
        <textarea class="textarea" id="address" name="address" rows="3" placeholder="Alamat lengkap tujuan pengiriman barang dari toko ini">{{ old('address', $profile->address) }}</textarea>
      </div>

      <div class="field">
        <label class="label" for="notes">Catatan Tambahan untuk Penjual (Opsional)</label>
        <input class="input" type="text" id="notes" name="notes" value="{{ old('notes', $profile->notes) }}" placeholder="Contoh: Titipkan di pos satpam / Rumah pagar hitam">
      </div>

      <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; margin: 20px 0; font-size: 13px; color: #64748b; line-height: 1.5;">
        ℹ️ <strong>Catatan:</strong> Informasi profil ini disimpan khusus untuk toko <strong>{{ $store->name }}</strong>. Transaksi Anda di toko lain akan menggunakan data profil tersendiri dari toko terkait.
      </div>

      <div style="display:flex;gap:12px;align-items:center;margin-top:24px;">
        <button type="submit" class="btn btn-primary">Simpan Profil Toko</button>
        <a href="{{ $store->url() }}" class="btn btn-ghost">Kembali ke Katalog</a>
      </div>
    </form>
  </div>
</div>
@endsection

