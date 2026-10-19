@extends('layouts.dashboard')
@section('title', 'Batch Seluruh Toko')
@section('content')
<div class="toolbar">
  <form method="GET" id="catalog-batch-filters" style="display:flex;gap:8px">
    <select class="select" style="width:180px" name="store_id"><option value="">Semua toko</option>@foreach($stores as $s)<option value="{{ $s->id }}" {{ (string)request('store_id')===(string)$s->id?'selected':'' }}>{{ $s->name }}</option>@endforeach</select>
  </form>
  <span class="spacer"></span>
  <span class="category-hierarchy" id="catalog-batch-count" style="margin-right:8px"></span>
</div>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table" id="catalog-batch-table" data-api-url="{{ route('api.superadmin.catalog.batches') }}">
<thead><tr><th>Kode</th><th>Toko</th><th>Tanggal</th><th>Item</th><th>Oleh</th><th>Status</th></tr></thead>
<tbody id="catalog-batch-rows">
<tr><td colspan="6"><div class="table-loading"><span class="skeleton" style="width:140px"></span> Memuat data batch…</div></td></tr>
</tbody>
</table>
</div></div>
<div id="catalog-batch-pagination"></div>
<noscript><div class="alert alert-error" style="margin-top:16px">Daftar batch ini dimuat dengan JavaScript. Aktifkan JavaScript untuk melihat datanya.</div></noscript>
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    var table = document.getElementById('catalog-batch-table');
    if (!table || !window.AdminTable) { return; }

    var esc = AdminTable.esc;

    function rowHtml(b) {
        var badge = b.status === 'active'
            ? '<span class="badge badge-success">' + esc(b.status) + '</span>'
            : '<span class="badge badge-neutral">' + esc(b.status) + '</span>';

        return '<tr>'
            + '<td>' + esc(b.batch_code) + '</td>'
            + '<td>' + esc(b.store_name || '—') + '</td>'
            + '<td>' + esc(b.purchase_date_formatted || '—') + '</td>'
            + '<td>' + esc(b.items_count) + '</td>'
            + '<td>' + esc(b.creator_name || '—') + '</td>'
            + '<td>' + badge + '</td>'
            + '</tr>';
    }

    AdminTable.mount({
        tbody: 'catalog-batch-rows',
        apiUrl: table.getAttribute('data-api-url'),
        form: 'catalog-batch-filters',
        pagination: 'catalog-batch-pagination',
        count: 'catalog-batch-count',
        countSuffix: 'batch',
        empty: { title: 'Tidak ada batch', text: 'Belum ada batch pembelian di semua toko.' },
        noMatch: { title: 'Tidak ada batch di toko ini' },
        renderRow: rowHtml
    });
});
</script>
@endsection
