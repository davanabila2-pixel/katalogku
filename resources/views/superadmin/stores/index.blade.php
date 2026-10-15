@extends('layouts.dashboard')
@section('title', 'Toko')
@section('subtitle', 'Kelola seluruh toko lintas aplikasi')
@section('nav')
<a href="{{ route('superadmin.dashboard') }}">Dashboard</a>
<a class="active" href="{{ route('superadmin.stores.index') }}">Toko</a>
<a href="{{ route('superadmin.users.index') }}">Pengguna</a>
<a href="{{ route('superadmin.audit') }}">Audit Log</a>
@endsection
@section('content')
<div class="toolbar">
  <form method="GET" style="display:flex;gap:8px"><input class="search-pill" type="search" name="q" value="{{ request('q') }}" placeholder="Cari toko…">
  <select class="select" style="width:140px" name="status" onchange="this.form.submit()"><option value="">Semua</option><option value="active" {{ request('status')==='active'?'selected':'' }}>Aktif</option><option value="inactive" {{ request('status')==='inactive'?'selected':'' }}>Nonaktif</option></select></form>
  <span class="spacer"></span>
  <a class="btn btn-primary btn-sm" href="{{ route('superadmin.stores.create') }}">+ Buat Toko</a>
</div>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table"><thead><tr><th>Nama</th><th>Slug / URL</th><th>Produk</th><th>Kategori</th><th>Batch</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse($stores as $s)<tr>
  <td><strong>{{ $s->name }}</strong></td>
  <td><a href="{{ $s->url() }}" target="_blank">/{{ $s->slug }}</a></td>
  <td>{{ $s->products_count }}</td><td>{{ $s->categories_count }}</td><td>{{ $s->purchase_batches_count }}</td>
  <td><span class="badge {{ $s->status==='active'?'badge-success':'badge-neutral' }}">{{ $s->status }}</span></td>
  <td><a href="{{ route('superadmin.stores.show', $s) }}">Kelola</a></td></tr>
@empty<tr><td colspan="7"><div class="empty"><h3>Belum ada toko</h3></div></td></tr>@endforelse
</tbody></table></div></div>
<x-paged :items="$stores" />
@endsection
