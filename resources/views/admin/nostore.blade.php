@extends('layouts.app')
@section('content')
<div class="empty" style="margin:64px 0">
  <h3>Akun belum terhubung ke toko</h3>
  <p>Minta Superadmin menautkan akun <strong>{{ auth()->user()->email }}</strong> sebagai admin toko.</p>
</div>
@endsection
