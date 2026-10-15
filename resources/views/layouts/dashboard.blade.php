<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="dash">
  <aside class="sidebar">
    <a class="brand" href="{{ route('home') }}">Kata<span style="color:#0091ff">log</span></a>
    @yield('nav')
    <form method="POST" action="{{ route('logout') }}" style="margin-top:24px">@csrf<button class="btn btn-ghost btn-sm" style="color:#fff;border-color:rgba(255,255,255,.3)" type="submit">Keluar</button></form>
  </aside>
  <div class="dash-main">
    <h1 style="margin:0 0 4px;font-size:28px">@yield('title', 'Dashboard')</h1>
    <p style="color:var(--steel);margin:0 0 8px">@yield('subtitle', '')</p>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-error"><ul style="margin:0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    @yield('content')
  </div>
</div>
</body>
</html>
