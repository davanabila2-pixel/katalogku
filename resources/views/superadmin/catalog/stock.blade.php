@extends('layouts.dashboard')
@section('title', 'Ringkasan Stok')
@section('nav')
<a href="{{ route('superadmin.dashboard') }}">Dashboard</a>
<a href="{{ route('superadmin.stores.index') }}">Toko</a>
<a href="{{ route('superadmin.batches') }}">Batch</a>
<a class="active" href="{{ route('superadmin.stock') }}">Stok</a>
<a href="{{ route('superadmin.audit') }}">Audit Log</a>
@endsection
@section('content')
<div class="toolbar"><form method="GET" style="display:flex;gap:8px">
<select class="select" style="width:180px" name="store_id" onchange="this.form.submit()"><option value="">Semua toko</option>@foreach($stores as $s)<option value="{{ $s->id }}" {{ (string)request('store_id')===(string)$s->id?'selected':'' }}>{{ $s->name }}</option>@endforeach</select></form></div>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table"><thead><tr><th>Waktu</th><th>Toko</th><th>Produk</th><th>Tipe</th><th>Qty</th><th>Oleh</th></tr></thead><tbody>
@forelse($movements as $m)<tr><td style="white-space:nowrap">{{ $m->created_at->format('d M H:i') }}</td><td>{{ $m->store->name }}</td><td>{{ $m->product->name ?? '#'.$m->product_id }}</td><td><span class="badge badge-neutral">{{ $m->movement_type }}</span></td><td>{{ $m->quantity }}</td><td>{{ $m->creator->name ?? '—' }}</td></tr>
@empty<tr><td colspan="6"><div class="empty"><h3>Tidak ada pergerakan</h3></div></td></tr>@endforelse
</tbody></table></div></div>
<x-paged :items="$movements" />
@endsection
