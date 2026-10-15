@extends('layouts.app')
@section('content')
<div class="card form-card" style="margin:48px auto">
  <h1 style="font-size:28px;margin:0 0 8px">Daftar akun admin</h1>
  <p style="color:var(--steel)">Akun baru berperan admin. Superadmin akan menautkan Anda ke toko.</p>
  <form method="POST" action="{{ route('register') }}">@csrf
    <div class="field"><label for="name">Nama</label><input id="name" class="input" type="text" name="name" value="{{ old('name') }}" required></div>
    <div class="field"><label for="email">Email</label><input id="email" class="input" type="email" name="email" value="{{ old('email') }}" required></div>
    <div class="field"><label for="password">Password (min 8 karakter)</label><input id="password" class="input" type="password" name="password" required></div>
    <div class="field"><label for="password_confirmation">Konfirmasi password</label><input id="password_confirmation" class="input" type="password" name="password_confirmation" required></div>
    <button class="btn btn-buy" type="submit" style="width:100%">Daftar</button>
  </form>
  <p style="margin-top:16px;font-size:14px">atau</p>
  <a class="btn btn-secondary" href="{{ route('auth.google.redirect') }}" style="width:100%">Daftar dengan Google</a>
</div>
@endsection
