@extends('layouts.dashboard')
@section('title', 'Kategori Lintas Toko')
@section('content')
<div class="toolbar"><form method="GET" style="display:flex;gap:8px"><input class="search-pill" type="search" name="q" value="{{ request('q') }}" placeholder="Cari kategori…">
<select class="select" style="width:180px" name="store_id" onchange="this.form.submit()"><option value="">Semua toko</option>@foreach($stores as $s)<option value="{{ $s->id }}" {{ (string)request('store_id')===(string)$s->id?'selected':'' }}>{{ $s->name }}</option>@endforeach</select></form></div>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table"><thead><tr><th>Nama</th><th>Toko</th><th>Induk</th><th>Sub</th><th>Produk</th><th>Status</th></tr></thead><tbody>
@forelse($categories as $c)<tr><td>{{ $c->name }}<br><span class="category-hierarchy">{{ $c->slug }}</span></td><td>{{ $c->store->name }}</td><td>{{ $c->parent->name ?? '—' }}</td><td>{{ $c->children_count }}</td><td>{{ $c->products_count }}</td><td>{!! $c->is_active ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-neutral">Nonaktif</span>' !!}</td></tr>
@empty<tr><td colspan="6"><div class="empty"><h3>Tidak ada kategori</h3></div></td></tr>@endforelse
</tbody></table></div></div>
<x-paged :items="$categories" />
@endsection
