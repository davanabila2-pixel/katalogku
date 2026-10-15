@extends('layouts.dashboard')
@section('title', 'Audit Log')
@section('subtitle', 'Perubahan penting tercatat di sini (tanpa password/token)')
@section('nav')
<a href="{{ route('superadmin.dashboard') }}">Dashboard</a>
<a href="{{ route('superadmin.stores.index') }}">Toko</a>
<a href="{{ route('superadmin.stock') }}">Stok</a>
<a class="active" href="{{ route('superadmin.audit') }}">Audit Log</a>
@endsection
@section('content')
<div class="toolbar"><form method="GET" style="display:flex;gap:8px"><input class="search-pill" type="search" name="action" value="{{ request('action') }}" placeholder="Filter aksi…">
<select class="select" style="width:180px" name="store_id" onchange="this.form.submit()"><option value="">Semua toko</option>@foreach($stores as $s)<option value="{{ $s->id }}" {{ (string)request('store_id')===(string)$s->id?'selected':'' }}>{{ $s->name }}</option>@endforeach</select><button class="btn btn-ghost btn-sm" type="submit">Filter</button></form></div>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table"><thead><tr><th>Waktu</th><th>Aksi</th><th>Deskripsi</th><th>Toko</th><th>Oleh</th></tr></thead><tbody>
@forelse($logs as $log)<tr><td style="white-space:nowrap">{{ $log->created_at->format('d M H:i') }}</td><td><span class="badge badge-neutral">{{ $log->action }}</span></td><td>{{ $log->description }}</td><td>{{ $log->store->name ?? '—' }}</td><td>{{ $log->user->name ?? '—' }}</td></tr>
@empty<tr><td colspan="5"><div class="empty"><h3>Belum ada log</h3></div></td></tr>@endforelse
</tbody></table></div></div>
<x-paged :items="$logs" />
@endsection
