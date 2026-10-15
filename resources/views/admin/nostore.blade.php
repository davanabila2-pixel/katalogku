@extends('layouts.app')
@section('content')
<div class="empty" style="margin:48px 0 24px">
  <h3>Akun belum terhubung ke toko</h3>
  <p>Minta Superadmin menautkan akun <strong>{{ auth()->user()->email }}</strong> sebagai admin toko — atau buat tokomu sendiri sekarang.</p>
</div>
<div class="card form-card" style="margin:0 auto 64px">
  <h2 style="font-size:20px;margin:0 0 8px">Buat toko baru</h2>
  <p style="color:var(--steel);font-size:14px">Anda otomatis menjadi admin utama toko ini.</p>
  <form method="POST" action="{{ route('admin.stores.store') }}">@csrf
    <div class="field"><label for="store_name">Nama toko</label><input id="store_name" class="input" type="text" name="store_name" value="{{ old('store_name') }}" required></div>
    <div class="field"><label for="store_description">Deskripsi (opsional)</label><textarea id="store_description" class="textarea" name="store_description">{{ old('store_description') }}</textarea></div>
    <div class="field"><label for="store_contact">Kontak (opsional)</label><input id="store_contact" class="input" type="text" name="store_contact" value="{{ old('store_contact') }}"></div>
    <button class="btn btn-buy" type="submit" style="width:100%">Buat Toko</button>
  </form>
</div>
@endsection
