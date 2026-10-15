@extends('layouts.dashboard')
@section('title', 'Aktivitas Toko')
@section('subtitle', $store->name)
@section('nav')
<a href="{{ route('admin.dashboard', $store) }}">Dashboard</a>
<a href="{{ route('admin.stock.history', $store) }}">Riwayat Stok</a>
<a class="active" href="{{ route('admin.activity', $store) }}">Aktivitas</a>
@endsection
@section('content')
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table"><thead><tr><th>Waktu</th><th>Aksi</th><th>Deskripsi</th><th>Oleh</th></tr></thead><tbody>
@forelse($logs as $log)<tr><td style="white-space:nowrap">{{ $log->created_at->format('d M Y H:i') }}</td><td><span class="badge badge-neutral">{{ $log->action }}</span></td><td>{{ $log->description }}</td><td>{{ $log->user->name ?? '—' }}</td></tr>
@empty<tr><td colspan="4"><div class="empty"><h3>Belum ada aktivitas</h3></div></td></tr>@endforelse
</tbody></table></div></div>
<x-paged :items="$logs" />
@endsection
