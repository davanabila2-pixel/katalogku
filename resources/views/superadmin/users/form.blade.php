@extends('layouts.dashboard')
@section('title', 'Kelola '.$user->name)
@section('subtitle', $user->email)
@section('nav')
<a href="{{ route('superadmin.dashboard') }}">Dashboard</a>
<a class="active" href="{{ route('superadmin.users.index') }}">Pengguna</a>
@endsection
@section('content')
<div class="panel form-card">
<form method="POST" action="{{ route('superadmin.users.update', $user) }}">@csrf @method('PUT')
  <div class="field"><label>Nama</label><input class="input" type="text" name="name" value="{{ old('name', $user->name) }}" required></div>
  <div class="form-row">
    <div class="field"><label>Role</label><select class="select" name="role"><option value="admin" {{ $user->role==='admin'?'selected':'' }}>Admin</option><option value="superadmin" {{ $user->role==='superadmin'?'selected':'' }}>Superadmin</option></select></div>
    <div class="field"><label>&nbsp;</label><label style="font-weight:400"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}> Aktif</label></div>
  </div>
  <div class="field"><label>Toko yang dikelola (admin)</label>
    @foreach($stores as $s)<label style="display:block;font-weight:400;font-size:14px"><input type="checkbox" name="stores[]" value="{{ $s->id }}" {{ in_array($s->id, old('stores', $user->stores->pluck('id')->toArray())) ? 'checked' : '' }}> {{ $s->name }}</label>@endforeach</div>
  <div class="field"><label>Admin utama di toko (ID)</label><input class="input" type="number" name="primary_store" value="{{ old('primary_store') }}" placeholder="opsional"></div>
  <button class="btn btn-buy" type="submit">Simpan</button>
</form>
</div>
@endsection
