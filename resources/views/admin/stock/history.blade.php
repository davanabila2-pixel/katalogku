@extends('layouts.dashboard')
@section('title', 'Riwayat Stok')
@section('subtitle', $store->name.' — setiap perubahan tercatat dan dapat diaudit')
@section('nav')
<a href="{{ route('admin.dashboard', $store) }}">Dashboard</a>
<a href="{{ route('admin.batches.index', $store) }}">Batch Pembelian</a>
<a href="{{ route('admin.stock.index', $store) }}">Stok</a>
<a class="active" href="{{ route('admin.stock.history', $store) }}">Riwayat Stok</a>
<a href="{{ route('admin.activity', $store) }}">Aktivitas</a>
@endsection
@section('content')
<div class="toolbar">
  <form method="GET" style="display:flex;gap:8px">
  <select class="select" style="width:200px" name="product_id" onchange="this.form.submit()"><option value="">Semua produk</option>@foreach($products as $p)<option value="{{ $p->id }}" {{ (string)request('product_id')===(string)$p->id?'selected':'' }}>{{ $p->name }}</option>@endforeach</select>
  <select class="select" style="width:150px" name="type" onchange="this.form.submit()"><option value="">Semua tipe</option><option value="in" {{ request('type')==='in'?'selected':'' }}>Masuk</option><option value="out" {{ request('type')==='out'?'selected':'' }}>Keluar</option><option value="adjustment" {{ request('type')==='adjustment'?'selected':'' }}>Koreksi</option><option value="cancel" {{ request('type')==='cancel'?'selected':'' }}>Batal</option></select></form>
</div>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table"><thead><tr><th>Waktu</th><th>Produk</th><th>Tipe</th><th>Qty</th><th>Catatan</th><th>Oleh</th></tr></thead><tbody>
@forelse($movements as $m)<tr><td style="white-space:nowrap">{{ $m->created_at->format('d M Y H:i') }}</td><td>{{ $m->product->name ?? '#'.$m->product_id }}</td><td><span class="badge badge-neutral">{{ $m->movement_type }}</span></td><td>{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</td><td>{{ $m->notes ?? '—' }}</td><td>{{ $m->creator->name ?? '—' }}</td></tr>
@empty<tr><td colspan="6"><div class="empty"><h3>Belum ada pergerakan stok</h3></div></td></tr>@endforelse
</tbody></table></div></div>
<x-paged :items="$movements" />
@endsection
