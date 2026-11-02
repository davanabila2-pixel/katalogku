@extends('layouts.app')
@section('content')
<div class="user-account-container" style="max-width: 440px;">
  <div style="text-align: center; margin-bottom: 24px;">
    @if($store->logoUrl())
      <img src="{{ $store->logoUrl() }}" alt="{{ $store->name }}" style="width: 56px; height: 56px; border-radius: 14px; object-fit: cover; border: 1px solid #e2e8f0; margin-bottom: 12px;">
    @else
      <div style="width: 56px; height: 56px; border-radius: 14px; background: #09090b; color: #fff; font-size: 24px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
        {{ mb_substr($store->name, 0, 1) }}
      </div>
    @endif
    <h1 class="user-account-title" style="font-size: 22px;">Masuk ke {{ $store->name }}</h1>
    <p class="user-account-subtitle">Masuk dengan akun pelanggan Anda untuk melihat riwayat pesanan dan bertransaksi.</p>
  </div>

  <div class="user-account-card">
    <form method="POST" action="{{ route('store.login.submit', $store->slug) }}">
      @csrf

      <div class="field">
        <label class="label" for="email">Alamat Email</label>
        <input class="input" type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="nama@email.com">
      </div>

      <div class="field">
        <label class="label" for="password">Kata Sandi</label>
        <input class="input" type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
      </div>

      <div class="field" style="display: flex; align-items: center; justify-content: space-between; margin-top: 10px;">
        <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 13.5px; color: #475569; cursor: pointer;">
          <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
          <span>Ingat saya</span>
        </label>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 18px; padding: 12px;">
        Masuk ke Toko
      </button>

      <div style="text-align: center; margin-top: 18px; font-size: 13.5px; color: #64748b;">
        Belum punya akun pelanggan di toko ini?<br>
        <a href="{{ route('store.register', $store->slug) }}" style="color: #09090b; font-weight: 700; text-decoration: underline;">
          Daftar Akun Pelanggan {{ $store->name }}
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

