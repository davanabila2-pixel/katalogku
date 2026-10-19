@extends('layouts.dashboard')
@section('title', $store->name)
@section('subtitle', $store->url())
@section('content')
<div class="stat-grid">
  <div class="stat-card"><div class="num">{{ $stats['products'] }}</div><div class="lbl">Produk</div></div>
  <div class="stat-card"><div class="num">{{ $stats['categories'] }}</div><div class="lbl">Kategori</div></div>
  <div class="stat-card"><div class="num">{{ $stats['batches'] }}</div><div class="lbl">Batch</div></div>
  <div class="stat-card"><div class="num">{{ $stats['stock'] }}</div><div class="lbl">Stok</div></div>
</div>
<div class="panel">
  <p>Status: <span class="badge {{ $store->status==='active'?'badge-success':'badge-neutral' }}">{{ $store->status }}</span> · Katalog: <a href="{{ $store->url() }}" target="_blank">{{ $store->url() }}</a></p>
  <p style="font-size:14px">{{ $store->description }}</p>
  <a class="btn btn-primary btn-sm" href="{{ route('superadmin.stores.edit', $store) }}">Edit Toko</a>
  <a class="btn btn-ghost btn-sm" href="{{ route('admin.dashboard', $store) }}">Buka sebagai Admin</a>
  <form method="POST" action="{{ route('superadmin.stores.destroy', $store) }}" style="display:inline" onsubmit="return confirm('Arsipkan toko ini?')">@csrf @method('DELETE')<button class="btn btn-secondary btn-sm" type="submit">Arsipkan</button></form>
</div>
<div class="grid" style="grid-template-columns:1fr 1fr">
  <div class="panel"><h2>Admin toko</h2>
    @forelse($store->users as $u)<p style="font-size:14px">{{ $u->name }} ({{ $u->email }}) @if($u->pivot->is_primary)<span class="badge badge-success">utama</span>@endif</p>@empty<p style="color:var(--steel)">Belum ada admin.</p>@endforelse
  </div>
  <div class="panel"><h2>Riwayat slug</h2>
    @forelse($store->slugHistories as $h)<p style="font-size:14px">/{{ $h->old_slug }} → /{{ $h->new_slug }} <span class="category-hierarchy">{{ $h->created_at->format('d M Y') }}</span></p>@empty<p style="color:var(--steel)">Slug belum pernah berubah.</p>@endforelse
  </div>
</div>
@endsection
