@extends('layouts.dashboard')
@section('title', $store->name)
@section('subtitle', 'Ringkasan toko Anda')
@section('nav')
<a class="active" href="{{ route('admin.dashboard', $store) }}">Dashboard</a>
<a href="{{ route('admin.profile.edit', $store) }}">Profil Toko</a>
<a href="{{ route('admin.categories.index', $store) }}">Kategori</a>
<a href="{{ route('admin.products.index', $store) }}">Produk</a>
<a href="{{ route('admin.batches.index', $store) }}">Batch Pembelian</a>
<a href="{{ route('admin.stock.index', $store) }}">Stok</a>
<a href="{{ route('admin.stock.history', $store) }}">Riwayat Stok</a>
<a href="{{ route('admin.activity', $store) }}">Aktivitas</a>
<a href="{{ $store->url() }}" target="_blank">Lihat Katalog ↗</a>
@endsection
@section('content')
<div class="stat-grid">
  <div class="stat-card"><div class="num">{{ $totalProducts }}</div><div class="lbl">Produk</div></div>
  <div class="stat-card"><div class="num">{{ $totalCategories + $totalSubcategories }}</div><div class="lbl">Kategori ({{ $totalSubcategories }} sub)</div></div>
  <div class="stat-card"><div class="num">{{ $totalBatches }}</div><div class="lbl">Batch pembelian</div></div>
  <div class="stat-card"><div class="num">{{ $totalStock }}</div><div class="lbl">Total stok</div></div>
</div>
<div class="grid" style="grid-template-columns:1fr 1fr">
  <div class="panel"><h2>Stok rendah / habis</h2>
    @forelse($lowStock as $p)<p style="font-size:14px">{{ $p->name }} — <strong>{{ $p->stock }}</strong></p>@empty<p style="color:var(--steel)">Semua stok aman.</p>@endforelse
  </div>
  <div class="panel"><h2>Batch terbaru</h2>
    @forelse($latestBatches as $b)<p style="font-size:14px">{{ $b->batch_code }} · {{ $b->purchase_date->format('d M Y') }}</p>@empty<p style="color:var(--steel)">Belum ada batch.</p>@endforelse
  </div>
</div>
<div class="panel"><h2>Aktivitas terbaru</h2>
  @forelse($activity as $log)<p style="font-size:14px">{{ $log->created_at->format('d M H:i') }} — {{ $log->description }} <span class="category-hierarchy">({{ $log->user->name ?? 'sistem' }})</span></p>@empty<p style="color:var(--steel)">Belum ada aktivitas.</p>@endforelse
</div>
@endsection
