@extends('layouts.dashboard')
@section('title', 'Kategori')
@section('subtitle', $store->name)
@section('nav')
<a href="{{ route('admin.dashboard', $store) }}">Dashboard</a>
<a href="{{ route('admin.profile.edit', $store) }}">Profil Toko</a>
<a class="active" href="{{ route('admin.categories.index', $store) }}">Kategori</a>
<a href="{{ route('admin.products.index', $store) }}">Produk</a>
<a href="{{ route('admin.batches.index', $store) }}">Batch Pembelian</a>
<a href="{{ route('admin.stock.index', $store) }}">Stok</a>
<a href="{{ route('admin.stock.history', $store) }}">Riwayat Stok</a>
<a href="{{ route('admin.activity', $store) }}">Aktivitas</a>
@endsection
@section('content')
<div class="toolbar">
  <form method="GET" style="display:flex;gap:8px"><input class="search-pill" type="search" name="q" value="{{ request('q') }}" placeholder="Cari kategori…">
  <select class="select" style="width:150px" name="status" onchange="this.form.submit()"><option value="">Semua status</option><option value="active" {{ request('status')==='active'?'selected':'' }}>Aktif</option><option value="inactive" {{ request('status')==='inactive'?'selected':'' }}>Nonaktif</option></select></form>
  <span class="spacer"></span>
  <a class="btn btn-primary btn-sm" href="{{ route('admin.categories.create', $store) }}">+ Tambah Kategori</a>
</div>
<div class="panel" style="padding:0">
<div class="table-wrap" style="border:none">
<table class="table">
<thead><tr><th>Nama</th><th>Induk</th><th>Sub</th><th>Produk</th><th>Urutan</th><th>Status</th><th>Aksi</th></tr></thead>
<tbody>
@forelse($categories as $cat)
<tr>
  <td><strong>{{ $cat->name }}</strong><br><span class="category-hierarchy">{{ $cat->slug }}</span></td>
  <td>{{ $cat->parent->name ?? '—' }}</td>
  <td>{{ $cat->children_count }}</td>
  <td>{{ $cat->products_count }}</td>
  <td>{{ $cat->sort_order }}</td>
  <td>{!! $cat->is_active ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-neutral">Nonaktif</span>' !!}</td>
  <td style="white-space:nowrap"><a href="{{ route('admin.categories.edit', [$store, $cat]) }}">Edit</a> ·
    <form method="POST" action="{{ route('admin.categories.destroy', [$store, $cat]) }}" style="display:inline" onsubmit="return confirm('Hapus kategori ini?')">@csrf @method('DELETE')<button type="submit" style="background:none;border:none;color:var(--critical);cursor:pointer;font-size:14px">Hapus</button></form></td>
</tr>
@empty
<tr><td colspan="7"><div class="empty"><h3>Belum ada kategori</h3><p>Buat kategori pertama untuk tokomu.</p></div></td></tr>
@endforelse
</tbody>
</table>
</div>
</div>
<x-paged :items="$categories" />
@endsection
