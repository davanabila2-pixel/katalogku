@extends('layouts.dashboard')
@section('title', 'Batch Pembelian')
@section('subtitle', $store->name)
@section('content')
<div class="toolbar">
  <form method="GET" style="display:flex;gap:8px"><input class="search-pill" type="search" name="q" value="{{ request('q') }}" placeholder="Cari kode/supplier…"></form>
  <span class="spacer"></span>
  <a class="btn btn-primary btn-sm" href="{{ route('admin.batches.create', $store) }}">+ Buat Batch</a>
</div>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table">
<thead><tr><th>Kode</th><th>Tanggal</th><th>Supplier</th><th>Item</th><th>Status</th><th>Aksi</th></tr></thead>
<tbody>
@forelse($batches as $b)
<tr><td><strong>{{ $b->batch_code }}</strong></td><td>{{ $b->purchase_date->format('d M Y') }}</td><td>{{ $b->supplier_name ?? '—' }}</td><td>{{ $b->items_count }}</td>
<td><span class="badge {{ $b->status==='active'?'badge-success':'badge-neutral' }}">{{ $b->status }}</span></td>
<td><a href="{{ route('admin.batches.show', [$store, $b]) }}">Detail</a></td></tr>
@empty<tr><td colspan="6"><div class="empty"><h3>Belum ada batch</h3><p>Catat pembelian barang pertama di sini.</p></div></td></tr>
@endforelse
</tbody>
</table>
</div></div>
<x-paged :items="$batches" />
@endsection
