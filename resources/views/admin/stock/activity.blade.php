@extends('layouts.dashboard')
@section('title', 'Aktivitas Toko')
@section('subtitle', $store->name)
@section('content')
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table" id="activity-table" data-api-url="{{ route('api.stock.activity', $store) }}">
<thead><tr><th>Waktu</th><th>Aksi</th><th>Deskripsi</th><th>Oleh</th></tr></thead>
<tbody id="activity-rows">
<tr><td colspan="4"><div class="table-loading"><span class="skeleton" style="width:140px"></span> Memuat aktivitas…</div></td></tr>
</tbody>
</table>
</div></div>
<div id="activity-pagination"></div>
<noscript><div class="alert alert-error" style="margin-top:16px">Daftar aktivitas ini dimuat dengan JavaScript. Aktifkan JavaScript untuk melihat datanya.</div></noscript>
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    var table = document.getElementById('activity-table');
    if (!table || !window.AdminTable) { return; }

    var esc = AdminTable.esc;

    function rowHtml(log) {
        return '<tr>'
            + '<td style="white-space:nowrap">' + esc(log.created_at_formatted || '—') + '</td>'
            + '<td><span class="badge badge-neutral">' + esc(log.action) + '</span></td>'
            + '<td>' + esc(log.description || '—') + '</td>'
            + '<td>' + esc(log.user_name || '—') + '</td>'
            + '</tr>';
    }

    AdminTable.mount({
        tbody: 'activity-rows',
        apiUrl: table.getAttribute('data-api-url'),
        pagination: 'activity-pagination',
        empty: { title: 'Belum ada aktivitas', text: 'Aktivitas penting tokomu akan tercatat di sini.' },
        renderRow: rowHtml
    });
});
</script>
@endsection
