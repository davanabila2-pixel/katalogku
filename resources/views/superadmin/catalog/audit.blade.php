@extends('layouts.dashboard')
@section('title', 'Audit Log')
@section('subtitle', 'Perubahan penting tercatat di sini (tanpa password/token)')
@section('content')
<form method="GET" id="catalog-audit-filters" class="toolbar">
  <div class="toolbar-left">
    <select class="select" style="width:180px" name="store_id"><option value="">Semua toko</option>@foreach($stores as $s)<option value="{{ $s->id }}" {{ (string)request('store_id')===(string)$s->id?'selected':'' }}>{{ $s->name }}</option>@endforeach</select>
  </div>
  <div class="toolbar-right">
    <x-search-input name="action" placeholder="Filter aksi…" width="250px" />
    <span class="category-hierarchy" id="catalog-audit-count"></span>
  </div>
</form>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table" id="catalog-audit-table" data-api-url="{{ route('api.superadmin.catalog.audit') }}">
<thead><tr><th>Waktu</th><th>Aksi</th><th>Deskripsi</th><th>Toko</th><th>Oleh</th></tr></thead>
<tbody id="catalog-audit-rows">
<tr><td colspan="5"><div class="table-loading"><span class="skeleton" style="width:140px"></span> Memuat log audit…</div></td></tr>
</tbody>
</table>
</div></div>
<div id="catalog-audit-pagination"></div>
<noscript><div class="alert alert-error" style="margin-top:16px">Log audit ini dimuat dengan JavaScript. Aktifkan JavaScript untuk melihat datanya.</div></noscript>
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    var table = document.getElementById('catalog-audit-table');
    if (!table || !window.AdminTable) { return; }

    var esc = AdminTable.esc;

    function rowHtml(log) {
        return '<tr>'
            + '<td style="white-space:nowrap">' + esc(log.created_at_formatted || '—') + '</td>'
            + '<td><span class="badge badge-neutral">' + esc(log.action) + '</span></td>'
            + '<td>' + esc(log.description || '—') + '</td>'
            + '<td>' + esc(log.store_name || '—') + '</td>'
            + '<td>' + esc(log.user_name || '—') + '</td>'
            + '</tr>';
    }

    AdminTable.mount({
        tbody: 'catalog-audit-rows',
        apiUrl: table.getAttribute('data-api-url'),
        form: 'catalog-audit-filters',
        pagination: 'catalog-audit-pagination',
        count: 'catalog-audit-count',
        countSuffix: 'log',
        empty: { title: 'Belum ada log', text: 'Audit log akan terisi seiring aktivitas di semua toko.' },
        noMatch: { title: 'Tidak ada log yang cocok', text: 'Coba ubah filter aksi atau toko.' },
        renderRow: rowHtml
    });
});
</script>
@endsection
