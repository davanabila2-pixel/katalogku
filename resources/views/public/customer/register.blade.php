@extends('layouts.app')
@section('content')
<div class="user-account-container" style="max-width: 480px;">
  <div style="text-align: center; margin-bottom: 24px;">
    @if($store->logoUrl())
      <img src="{{ $store->logoUrl() }}" alt="{{ $store->name }}" style="width: 56px; height: 56px; border-radius: 14px; object-fit: cover; border: 1px solid #e2e8f0; margin-bottom: 12px;">
    @else
      <div style="width: 56px; height: 56px; border-radius: 14px; background: #09090b; color: #fff; font-size: 24px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
        {{ mb_substr($store->name, 0, 1) }}
      </div>
    @endif
    <h1 class="user-account-title" style="font-size: 22px;">Daftar Akun Pelanggan</h1>
    <p class="user-account-subtitle">Buat akun pelanggan khusus untuk belanja dan lacak pesanan di <strong>{{ $store->name }}</strong>.</p>
  </div>

  <div class="user-account-card">
    <form method="POST" action="{{ route('store.register.submit', $store->slug) }}">
      @csrf

      <div class="field">
        <label class="label" for="name">Nama Lengkap</label>
        <input class="input" type="text" id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Nama Anda di toko ini">
      </div>

      <div class="field">
        <label class="label" for="email">Alamat Email</label>
        <input class="input" type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="nama@email.com">
      </div>

      <div class="field">
        <label class="label" for="phone">Nomor WhatsApp / Telepon</label>
        <input class="input" type="tel" id="phone" name="phone" value="{{ old('phone') }}" required autocomplete="tel" placeholder="08xxxxxxxxxx">
      </div>

      <div class="field">
        <label class="label" for="address">Alamat Pengiriman (Opsional)</label>
        <textarea class="textarea" id="address" name="address" rows="2" placeholder="Alamat pengiriman untuk pesanan Anda di toko ini">{{ old('address') }}</textarea>
      </div>

      <div class="field">
        <label class="label" for="password">Kata Sandi</label>
        <input class="input" type="password" id="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter">
      </div>

      <div class="field">
        <label class="label" for="password_confirmation">Konfirmasi Kata Sandi</label>
        <input class="input" type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" placeholder="Ulangi kata sandi">
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 18px; padding: 12px;">
        Daftar Pelanggan {{ $store->name }}
      </button>

      <div style="text-align: center; margin-top: 18px; font-size: 13.5px; color: #64748b;">
        Sudah memiliki akun?<br>
        <a href="{{ route('store.login', $store->slug) }}" style="color: #09090b; font-weight: 700; text-decoration: underline;">
          Masuk ke Akun {{ $store->name }}
        </a>
      </div>
    </form>
  </div>

  <div style="text-align: center; margin-top: 18px;">
    <a href="{{ $store->url() }}" style="font-size: 13px; color: #64748b; text-decoration: none;">
      ← Kembali ke Katalog {{ $store->name }}
    </a>
  </div>
</div>
@endsection

