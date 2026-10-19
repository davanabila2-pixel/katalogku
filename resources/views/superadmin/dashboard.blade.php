@extends('layouts.dashboard')
@section('title', 'Superadmin')
@section('subtitle', 'Statistik nyata seluruh aplikasi')
@section('nav')
<a class="active" href="{{ route('superadmin.dashboard') }}">Dashboard</a>
<a href="{{ route('superadmin.stores.index') }}">Toko</a>
<a href="{{ route('superadmin.users.index') }}">Pengguna</a>
<a href="{{ route('superadmin.categories') }}">Kategori</a>
<a href="{{ route('superadmin.products') }}">Produk</a>
<a href="{{ route('superadmin.batches') }}">Batch</a>
<a href="{{ route('superadmin.stock') }}">Stok</a>
<a href="{{ route('superadmin.audit') }}">Audit Log</a>
@endsection
@section('content')
<div class="stat-grid">
  <div class="stat-card"><div class="num">{{ $stats['stores'] }}</div><div class="lbl">Toko ({{ $stats['stores_active'] }} aktif)</div></div>
  <div class="stat-card"><div class="num">{{ $stats['users'] }}</div><div class="lbl">Pengguna</div></div>
  <div class="stat-card"><div class="num">{{ $stats['products'] }}</div><div class="lbl">Produk</div></div>
  <div class="stat-card"><div class="num">{{ $stats['categories'] }}</div><div class="lbl">Kategori</div></div>
  <div class="stat-card"><div class="num">{{ $stats['batches'] }}</div><div class="lbl">Batch</div></div>
</div>
<div class="panel" style="margin-bottom:20px">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
    <div>
      <h2 style="margin:0 0 4px;font-size:16px">Penyimpanan Media (Google Drive)</h2>
      <p style="margin:0;font-size:14px;color:var(--steel)">
        @if($driveStatus['configured'])
          <span class="badge badge-success">Google Drive Aktif</span> Gambar otomatis tersimpan ke cloud (Folder ID: <code>{{ $driveStatus['folder_id'] }}</code>).
        @else
          <span class="badge badge-neutral">Fallback Lokal Aktif</span> Gambar disimpan di server lokal (<code>public/storage</code>).
          @if(!$driveStatus['has_token'])
            · Token Drive belum terhubung.
          @elseif(!$driveStatus['folder_id'])
            · Masukkan <code>GOOGLE_DRIVE_ROOT_FOLDER_ID</code> di <code>.env</code> untuk mengaktifkan.
          @endif
        @endif
      </p>
    </div>
    @if(!$driveStatus['has_token'] && $driveStatus['has_client'])
      <div>
        <a href="{{ route('superadmin.drive.connect') }}" class="btn btn-primary btn-sm">Hubungkan Google Drive</a>
      </div>
    @endif
  </div>
</div>
<div class="grid" style="grid-template-columns:1fr 1fr">
  <div class="panel"><h2>Toko terbaru</h2>
    @forelse($latestStores as $s)<p style="font-size:14px"><a href="{{ route('superadmin.stores.show', $s) }}">{{ $s->name }}</a> · {{ $s->products_count }} produk · <span class="badge {{ $s->status==='active'?'badge-success':'badge-neutral' }}">{{ $s->status }}</span></p>@empty<p style="color:var(--steel)">Belum ada toko.</p>@endforelse
  </div>
  <div class="panel"><h2>Aktivitas terbaru</h2>
    @forelse($activity as $log)<p style="font-size:14px">{{ $log->created_at->format('d M H:i') }} — {{ $log->description }} <span class="category-hierarchy">({{ $log->user->name ?? 'sistem' }}@if($log->store) · {{ $log->store->name }}@endif)</span></p>@empty<p style="color:var(--steel)">Belum ada aktivitas.</p>@endforelse
  </div>
</div>
@endsection
