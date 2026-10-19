@extends('layouts.dashboard')
@section('title', 'Pengguna')
@section('subtitle', 'Kelola akun sesuai kewenangan (role tidak bisa dinaikkan sendiri)')
@section('content')
<div class="toolbar"><form method="GET" style="display:flex;gap:8px"><input class="search-pill" type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama/email…">
<select class="select" style="width:140px" name="role" onchange="this.form.submit()"><option value="">Semua role</option><option value="superadmin" {{ request('role')==='superadmin'?'selected':'' }}>Superadmin</option><option value="admin" {{ request('role')==='admin'?'selected':'' }}>Admin</option></select></form></div>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table"><thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Status</th><th>Toko</th><th>Aksi</th></tr></thead><tbody>
@forelse($users as $u)<tr>
  <td>{{ $u->name }}</td><td>{{ $u->email }}</td><td><span class="badge {{ $u->role==='superadmin'?'badge-warning':'badge-neutral' }}">{{ $u->role }}</span></td>
  <td>{!! $u->is_active ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-critical">Nonaktif</span>' !!}</td>
  <td>{{ $u->stores->pluck('name')->join(', ') ?: '—' }}</td>
  <td><a href="{{ route('superadmin.users.edit', $u) }}">Kelola</a></td></tr>
@empty<tr><td colspan="6"><div class="empty"><h3>Tidak ada pengguna</h3></div></td></tr>@endforelse
</tbody></table></div></div>
<x-paged :items="$users" />
@endsection
