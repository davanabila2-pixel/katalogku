@extends('layouts.dashboard')
@section('title', 'Produk')
@section('subtitle', $store->name)
@section('nav')
<a href="{{ route('admin.dashboard', $store) }}">Dashboard</a>
<a href="{{ route('admin.profile.edit', $store) }}">Profil Toko</a>
<a href="{{ route('admin.categories.index', $store) }}">Kategori</a>
<a class="active" href="{{ route('admin.products.index', $store) }}">Produk</a>
<a href="{{ route('admin.batches.index', $store) }}">Batch Pembelian</a>
<a href="{{ route('admin.stock.index', $store) }}">Stok</a>
<a href="{{ route('admin.stock.history', $store) }}">Riwayat Stok</a>
<a href="{{ route('admin.activity', $store) }}">Aktivitas</a>
@endsection
@section('content')
<div class="toolbar">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap"><input class="search-pill" type="search" name="q" value="{{ request('q') }}" placeholder="Cari produk/SKU…">
  <select class="select" style="width:160px" name="category_id" onchange="this.form.submit()"><option value="">Semua kategori</option>@foreach($categories as $c)<option value="{{ $c->id }}" {{ (string)request('category_id')===(string)$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach</select>
  <select class="select" style="width:130px" name="status" onchange="this.form.submit()"><option value="">Semua status</option><option value="draft" {{ request('status')==='draft'?'selected':'' }}>Draft</option><option value="active" {{ request('status')==='active'?'selected':'' }}>Aktif</option><option value="inactive" {{ request('status')==='inactive'?'selected':'' }}>Nonaktif</option></select></form>
  <span class="spacer"></span>
  <a class="btn btn-primary btn-sm" href="{{ route('admin.products.create', $store) }}">+ Tambah Produk</a>
</div>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table">
<thead><tr><th>Produk</th><th>Kategori</th><th>Harga Jual</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead>
<tbody>
@forelse($products as $p)
<tr>
  <td><strong>{{ $p->name }}</strong><br><span class="category-hierarchy">{{ $p->sku ?? $p->slug }}</span></td>
  <td>{{ $p->category->name ?? '—' }}</td>
  <td>Rp {{ number_format($p->selling_price, 0, ',', '.') }}</td>
  <td>{{ $p->stock }}</td>
  <td><span class="badge {{ $p->status==='active'?'badge-success':($p->status==='draft'?'badge-warning':'badge-neutral') }}">{{ $p->status }}</span></td>
  <td style="white-space:nowrap"><a href="{{ route('admin.products.edit', [$store, $p]) }}">Edit</a> ·
    <form method="POST" action="{{ route('admin.products.destroy', [$store, $p]) }}" style="display:inline" onsubmit="return confirm('Arsipkan/hapus produk ini?')">@csrf @method('DELETE')<button type="submit" style="background:none;border:none;color:var(--critical);cursor:pointer;font-size:14px">Arsip</button></form></td>
</tr>
@empty
<tr><td colspan="6"><div class="empty"><h3>Belum ada produk</h3><p>Tambahkan produk pertama untuk tokomu.</p></div></td></tr>
@endforelse
</tbody>
</table>
</div></div>
<x-paged :items="$products" />
@endsection
