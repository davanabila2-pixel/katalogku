@extends('layouts.dashboard')
@section('title', 'Batch Seluruh Toko')
@section('content')
<div class="toolbar"><form method="GET" style="display:flex;gap:8px">
<select class="select" style="width:180px" name="store_id" onchange="this.form.submit()"><option value="">Semua toko</option>@foreach($stores as $s)<option value="{{ $s->id }}" {{ (string)request('store_id')===(string)$s->id?'selected':'' }}>{{ $s->name }}</option>@endforeach</select></form></div>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table"><thead><tr><th>Kode</th><th>Toko</th><th>Tanggal</th><th>Item</th><th>Oleh</th><th>Status</th></tr></thead><tbody>
@forelse($batches as $b)<tr><td>{{ $b->batch_code }}</td><td>{{ $b->store->name }}</td><td>{{ $b->purchase_date->format('d M Y') }}</td><td>{{ $b->items_count }}</td><td>{{ $b->creator->name ?? '—' }}</td><td><span class="badge {{ $b->status==='active'?'badge-success':'badge-neutral' }}">{{ $b->status }}</span></td></tr>
@empty<tr><td colspan="6"><div class="empty"><h3>Tidak ada batch</h3></div></td></tr>@endforelse
</tbody></table></div></div>
<x-paged :items="$batches" />
@endsection
