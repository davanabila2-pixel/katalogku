@extends('layouts.dashboard')
@section('title', 'Ringkasan Stok')
@section('content')
<div class="toolbar">
  <form method="GET" id="catalog-stock-filters" style="display:flex;gap:8px">
    <select class="select" style="width:180px" name="store_id"><option value="">Semua toko</option>@foreach($stores as $s)<option value="{{ $s->id }}" {{ (string)request('store_id')===(string)$s->id?'selected':'' }}>{{ $s->name }}</option>@endforeach</select>
  </form>
  <span class="spacer"></span>
  <span class="category-hierarchy" id="catalog-stock-count" style="margin-right:8px"></span>
</div>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table" id="catalog-stock-table" data-api-url="{{ route('api.superadmin.catalog.stock') }}">
<thead><tr><th>Waktu</th><th>Toko</th><th>Produk</th><th>Tipe</th><th>Qty</th><th>Oleh</th></tr></thead>
<tbody id="catalog-stock-rows">
<tr><td colspan="6"><div class="table-loading"><span class="skeleton" style="width:140px"></span> Memuat pergerakan stok…</div></td></tr>
</tbody>
</table>
</div></div>
<div id="catalog-stock-pagination"></div>
<noscript><div class="alert alert-error" style="margin-top:16px">Data pergerakan stok ini dimuat dengan JavaScript. Aktifkan JavaScript untuk melihat datanya.</div></noscript>
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    var table = document.getElementById('catalog-stock-table');
    if (!table || !window.AdminTable) { return; }

    var esc = AdminTable.esc;

    function rowHtml(m) {
        return '<tr>'
            + '<td style="white-space:nowrap">' + esc(m.created_at_formatted || '—') + '</td>'
            + '<td>' + esc(m.store_name || '—') + '</td>'
            + '<td>' + esc(m.product_name || ('#' + m.product_id)) + '</td>'
            + '<td><span class="badge badge-neutral">' + esc(m.movement_type) + '</span></td>'
            + '<td>' + esc(m.quantity) + '</td>'
            + '<td>' + esc(m.creator_name || '—') + '</td>'
            + '</tr>';
    }

    AdminTable.mount({
        tbody: 'catalog-stock-rows',
        apiUrl: table.getAttribute('data-api-url'),
        form: 'catalog-stock-filters',
        pagination: 'catalog-stock-pagination',
        count: 'catalog-stock-count',
        countSuffix: 'mutasi',
        empty: { title: 'Tidak ada pergerakan', text: 'Belum ada mutasi stok di semua toko.' },
        noMatch: { title: 'Tidak ada pergerakan di toko ini' },
        renderRow: rowHtml
    });
});
</script>
@endsection
