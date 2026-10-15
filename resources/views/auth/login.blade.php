@extends('layouts.app')
@section('content')
<div class="card form-card" style="margin:48px auto">
  <h1 style="font-size:28px;margin:0 0 8px">Masuk</h1>
  <p style="color:var(--steel)">Kelola toko Anda atau pantau seluruh aplikasi.</p>
  <form method="POST" action="{{ route('login') }}">@csrf
    <div class="field"><label for="email">Email</label><input id="email" class="input" type="email" name="email" value="{{ old('email') }}" required autofocus></div>
    <div class="field"><label for="password">Password</label><input id="password" class="input" type="password" name="password" required></div>
    <button class="btn btn-buy" type="submit" style="width:100%">Masuk</button>
  </form>
  <p style="margin-top:16px;font-size:14px">atau</p>
  <a class="btn btn-secondary" href="{{ route('auth.google.redirect') }}" style="width:100%">Masuk dengan Google</a>
  <p style="font-size:14px;margin-top:16px">Belum punya akun? <a href="{{ route('register') }}">Daftar</a></p>
</div>
@endsection
