<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $seo['title'] ?? config('app.name') }}</title>
  <meta name="description" content="{{ $seo['description'] ?? 'Katalog penjualan multi-toko.' }}">
  @isset($seo['canonical'])<link rel="canonical" href="{{ $seo['canonical'] }}">@endisset
  <meta property="og:title" content="{{ $seo['title'] ?? config('app.name') }}">
  <meta property="og:description" content="{{ $seo['description'] ?? '' }}">
  @isset($seo['image'])<meta property="og:image" content="{{ $seo['image'] }}">@endisset
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="topbar">
  <div class="container topbar-inner">
    <a class="brand" href="{{ route('home') }}">Kata<span>log</span></a>
    <nav class="pillnav">
      <a class="pill-tab" href="{{ route('home') }}">Beranda</a>
      <a class="pill-tab" href="{{ route('stores.index') }}">Toko</a>
    </nav>
    <div class="topbar-right">
      @auth
        @if(auth()->user()->isSuperadmin())
          <a class="btn btn-ghost btn-sm" href="{{ route('superadmin.dashboard') }}">Superadmin</a>
        @elseif(auth()->user()->stores()->exists())
          <a class="btn btn-ghost btn-sm" href="{{ route('admin.dashboard', auth()->user()->stores()->first()) }}">Dashboard</a>
        @endif
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-primary btn-sm" type="submit">Keluar</button></form>
      @else
        <a class="btn btn-ghost btn-sm" href="{{ route('login') }}">Masuk</a>
        <a class="btn btn-primary btn-sm" href="{{ route('register') }}">Daftar</a>
      @endauth
    </div>
  </div>
</header>
<main class="container">
  @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
  @if($errors->any())<div class="alert alert-error"><ul style="margin:0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
  @yield('content')
</main>
<footer class="footer">
  <div class="container">Katalog multi-toko — satu domain, satu database. &copy; {{ date('Y') }}</div>
</footer>
</body>
</html>
