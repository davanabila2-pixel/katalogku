@extends('layouts.dashboard')
@section('title', 'Produk Lintas Toko')
@section('nav')
<a href="{{ route('superadmin.dashboard') }}">Dashboard</a>
<a href="{{ route('superadmin.stores.index') }}">Toko</a>
<a href="{{ route('superadmin.categories') }}">Kategori</a>
<a class="active" href="{{ route('superadmin.products') }}">Produk</a>
<a href="{{ route('superadmin.batches') }}">Batch</a>
<a href="{{ route('superadmin.stock') }}">Stok</a>
<a href="{{ route('superadmin.audit') }}">Audit Log</a>
@endsection
@section('content')
<div class="toolbar"><form method="GET" style="display:flex;gap:8px"><input class="search-pill" type="search" name="q" value="{{ request('q') }}" placeholder="Cari produk…">
<select class="select" style="width:180px" name="store_id" onchange="this.form.submit()"><option value="">Semua toko</option>@foreach($stores as $s)<option value="{{ $s->id }}" {{ (string)request('store_id')===(string)$s->id?'selected':'' }}>{{ $s->name }}</option>@endforeach</select></form></div>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table"><thead><tr><th>Nama</th><th>Toko</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Status</th></tr></thead><tbody>
@forelse($products as $p)<tr><td>{{ $p->name }}</td><td>{{ $p->store->name }}</td><td>{{ $p->category->name ?? '—' }}</td><td>Rp {{ number_format($p->selling_price, 0, ',', '.') }}</td><td>{{ $p->stock }}</td><td><span class="badge {{ $p->status==='active'?'badge-success':'badge-neutral' }}">{{ $p->status }}</span></td></tr>
@empty<tr><td colspan="6"><div class="empty"><h3>Tidak ada produk</h3></div></td></tr>@endforelse
</tbody></table></div></div>
<x-paged :items="$products" />
@endsection
