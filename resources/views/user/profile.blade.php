@extends('layouts.app')
@section('content')
<div class="user-account-container">
  <div class="user-account-header">
    <div class="user-account-avatar-wrap">
      <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="user-account-avatar" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
      <div class="user-account-avatar-fallback" style="display:none;">{{ $user->initial() }}</div>
    </div>
    <div class="user-account-heading">
      <h1 class="user-account-title">Profil Saya</h1>
      <p class="user-account-subtitle">Kelola informasi identitas akun Anda di platform.</p>
    </div>
  </div>

  <div class="user-account-card">
    <form method="POST" action="{{ route('user.profile.update') }}">
      @csrf
      @method('PUT')

      <div class="field">
        <label class="label" for="name">Nama Lengkap</label>
        <input class="input" type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name">
      </div>

      <div class="field">
        <label class="label" for="email">Alamat Email</label>
        <input class="input" type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
      </div>

      <div class="field-divider"></div>

      <h3 style="font-size:16px;font-weight:700;margin:0 0 6px;color:#0f172a;">Ganti Kata Sandi</h3>
      <p style="font-size:13px;color:#64748b;margin:0 0 16px;">Biarkan kosong jika Anda tidak ingin mengubah kata sandi.</p>

      <div class="field">
        <label class="label" for="password">Kata Sandi Baru</label>
        <input class="input" type="password" id="password" name="password" autocomplete="new-password" placeholder="Minimal 8 karakter">
      </div>

      <div class="field">
        <label class="label" for="password_confirmation">Konfirmasi Kata Sandi Baru</label>
        <input class="input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" placeholder="Ulangi kata sandi baru">
      </div>

      <div style="display:flex;gap:12px;align-items:center;margin-top:24px;">
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        <a href="{{ route('home') }}" class="btn btn-ghost">Kembali</a>
      </div>
    </form>
  </div>
</div>
@endsection
