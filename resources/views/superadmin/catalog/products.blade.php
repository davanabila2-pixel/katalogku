@extends('layouts.dashboard')
@section('title', 'Produk Lintas Toko')
@section('content')
<form method="GET" id="catalog-product-filters" class="toolbar">
  <div class="toolbar-left">
    <select class="select" style="width:180px" name="store_id"><option value="">Semua toko</option>@foreach($stores as $s)<option value="{{ $s->id }}" {{ (string)request('store_id')===(string)$s->id?'selected':'' }}>{{ $s->name }}</option>@endforeach</select>
  </div>
  <div class="toolbar-right">
    <x-search-input placeholder="Cari produk…" width="250px" />
    <span class="category-hierarchy" id="catalog-product-count"></span>
  </div>
</form>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table" id="catalog-product-table" data-api-url="{{ route('api.superadmin.catalog.products') }}">
<thead><tr><th>Nama</th><th>Toko</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Status</th></tr></thead>
<tbody id="catalog-product-rows">
<tr><td colspan="6"><div class="table-loading"><span class="skeleton" style="width:140px"></span> Memuat data produk…</div></td></tr>
</tbody>
</table>
</div></div>
<div id="catalog-product-pagination"></div>
<noscript><div class="alert alert-error" style="margin-top:16px">Daftar produk ini dimuat dengan JavaScript. Aktifkan JavaScript untuk melihat datanya.</div></noscript>
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    var table = document.getElementById('catalog-product-table');
    if (!table || !window.AdminTable) { return; }

    var esc = AdminTable.esc;

    function rowHtml(p) {
        var badge = p.status === 'active'
            ? '<span class="badge badge-success">' + esc(p.status) + '</span>'
            : '<span class="badge badge-neutral">' + esc(p.status) + '</span>';

        return '<tr>'
            + '<td>' + esc(p.name) + '</td>'
            + '<td>' + esc(p.store_name || '—') + '</td>'
            + '<td>' + esc(p.category || '—') + '</td>'
            + '<td>' + esc(p.selling_price_formatted) + '</td>'
            + '<td>' + esc(p.stock) + '</td>'
            + '<td>' + badge + '</td>'
            + '</tr>';
    }

    AdminTable.mount({
        tbody: 'catalog-product-rows',
        apiUrl: table.getAttribute('data-api-url'),
        form: 'catalog-product-filters',
        pagination: 'catalog-product-pagination',
        count: 'catalog-product-count',
        countSuffix: 'produk',
        empty: { title: 'Tidak ada produk', text: 'Belum ada produk di semua toko.' },
        noMatch: { title: 'Tidak ada produk yang cocok', text: 'Coba ubah kata kunci atau filternya.' },
        renderRow: rowHtml
    });
});
</script>
@endsection
