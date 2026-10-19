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
  <select class="select" style="width:130px" name="status" onchange="this.form.submit()"><option value="">Semua status</option><option value="draft" {{ request('status')==='draft'?'selected':'' }}>Draft</option><option value="active" {{ request('status')==='active'?'selected':'' }}>Aktif</option><option value="inactive" {{ request('status')==='inactive'?'selected':'' }}>Nonaktif</option></select>
  <select class="select" style="width:160px" name="visibility" onchange="this.form.submit()"><option value="">Semua visibilitas</option><option value="visible" {{ request('visibility')==='visible'?'selected':'' }}>Tampil di katalog</option><option value="hidden" {{ request('visibility')==='hidden'?'selected':'' }}>Tersembunyi</option></select></form>
  <span class="spacer"></span>
  <a class="btn btn-primary btn-sm" href="{{ route('admin.products.create', $store) }}">+ Tambah Produk</a>
</div>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table">
<thead><tr><th>Produk</th><th>Kategori</th><th>Harga Jual</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead>
<tbody>
@forelse($products as $p)
<tr>
  <td>
    <strong>{{ $p->name }}</strong>
    @if($p->isBundle())
      <span class="badge" style="background:#e0e7ff;color:#3730a3;font-size:11px;font-weight:600;margin-left:4px">Setelan</span>
    @elseif($p->is_hidden_from_catalog)
      <span class="badge" style="background:#fef3c7;color:#92400e;font-size:11px;font-weight:600;margin-left:4px">Tersembunyi</span>
    @endif
    <br><span class="category-hierarchy">
      @if($p->barcode)
        <a href="{{ $p->barcodeUrl() ?? $p->scanUrl() }}" target="_blank" style="text-decoration:none" title="Scan / Buka Label Barcode">
          <span style="font-family:monospace;background:#f1f5f9;color:#334155;padding:1px 5px;border-radius:4px;border:1px solid #cbd5e1;font-size:11px">🏷️ {{ $p->barcode }}</span>
        </a> ·
      @endif
      {{ $p->sku ? 'SKU: '.$p->sku : $p->slug }}
    </span>
  </td>
  <td>{{ $p->category->name ?? '—' }}</td>
  <td>Rp {{ number_format($p->selling_price, 0, ',', '.') }}</td>
  <td>
    @if($p->isBundle())
      <strong>{{ $p->stock }}</strong> <span style="font-size:11px;color:var(--steel);display:block">Otomatis</span>
    @else
      {{ $p->stock }}
    @endif
  </td>
  <td><span class="badge {{ $p->status==='active'?'badge-success':($p->status==='draft'?'badge-warning':'badge-neutral') }}">{{ $p->status }}</span></td>
  <td style="white-space:nowrap">
    <a href="{{ route('admin.products.edit', [$store, $p]) }}">Edit</a>
    @if($p->barcodeUrl())
      · <a href="{{ $p->barcodeUrl() }}" target="_blank" title="Buka Label Barcode di Google Drive">Barcode</a>
    @endif
    · <form method="POST" action="{{ route('admin.products.destroy', [$store, $p]) }}" style="display:inline" onsubmit="return confirm('Arsipkan/hapus produk ini?')">@csrf @method('DELETE')<button type="submit" style="background:none;border:none;color:var(--critical);cursor:pointer;font-size:14px">Arsip</button></form>
  </td>
</tr>
@empty
<tr><td colspan="6"><div class="empty"><h3>Belum ada produk</h3><p>Tambahkan produk pertama untuk tokomu.</p></div></td></tr>
@endforelse
</tbody>
</table>
</div></div>
<x-paged :items="$products" />
@endsection
