@extends('layouts.dashboard')
@section('title', 'Stok')
@section('subtitle', $store->name)
@section('content')
<div class="toolbar">
  <form method="GET" style="display:flex;gap:8px"><input class="search-pill" type="search" name="q" value="{{ request('q') }}" placeholder="Cari produk…">
  <select class="select" style="width:150px" name="filter" onchange="this.form.submit()"><option value="">Semua</option><option value="low" {{ request('filter')==='low'?'selected':'' }}>Rendah (≤5)</option><option value="empty" {{ request('filter')==='empty'?'selected':'' }}>Habis</option></select></form>
</div>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table"><thead><tr><th>Produk</th><th>Kategori</th><th>Stok</th><th>Status</th></tr></thead><tbody>
@forelse($products as $p)<tr><td>{{ $p->name }}</td><td>{{ $p->category->name ?? '—' }}</td><td><strong>{{ $p->stock }}</strong></td>
<td>@if($p->stock <= 0)<span class="badge badge-critical">Habis</span>@elseif($p->stock <= 5)<span class="badge badge-warning">Rendah</span>@else<span class="badge badge-success">Aman</span>@endif</td></tr>
@empty<tr><td colspan="4"><div class="empty"><h3>Tidak ada produk</h3></div></td></tr>@endforelse
</tbody></table></div></div>
<x-paged :items="$products" />
@endsection
