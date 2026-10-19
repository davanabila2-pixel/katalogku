@extends('layouts.dashboard')
@section('title', 'Kategori Lintas Toko')
@section('content')
<div class="toolbar">
  <form method="GET" id="catalog-category-filters" style="display:flex;gap:8px">
    <input class="search-pill" type="search" name="q" value="{{ request('q') }}" placeholder="Cari kategori…" autocomplete="off">
    <select class="select" style="width:180px" name="store_id"><option value="">Semua toko</option>@foreach($stores as $s)<option value="{{ $s->id }}" {{ (string)request('store_id')===(string)$s->id?'selected':'' }}>{{ $s->name }}</option>@endforeach</select>
  </form>
  <span class="spacer"></span>
  <span class="category-hierarchy" id="catalog-category-count" style="margin-right:8px"></span>
</div>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table" id="catalog-category-table" data-api-url="{{ route('api.superadmin.catalog.categories') }}">
<thead><tr><th>Nama</th><th>Toko</th><th>Induk</th><th>Sub</th><th>Produk</th><th>Status</th></tr></thead>
<tbody id="catalog-category-rows">
<tr><td colspan="6"><div class="table-loading"><span class="skeleton" style="width:140px"></span> Memuat data kategori…</div></td></tr>
</tbody>
</table>
</div></div>
<div id="catalog-category-pagination"></div>
<noscript><div class="alert alert-error" style="margin-top:16px">Daftar kategori ini dimuat dengan JavaScript. Aktifkan JavaScript untuk melihat datanya.</div></noscript>
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    var table = document.getElementById('catalog-category-table');
    if (!table || !window.AdminTable) { return; }

    var esc = AdminTable.esc;

    function rowHtml(c) {
        var badge = c.is_active
            ? '<span class="badge badge-success">Aktif</span>'
            : '<span class="badge badge-neutral">Nonaktif</span>';

        return '<tr>'
            + '<td>' + esc(c.name) + '<br><span class="category-hierarchy">' + esc(c.slug) + '</span></td>'
            + '<td>' + esc(c.store_name || '—') + '</td>'
            + '<td>' + esc(c.parent || '—') + '</td>'
            + '<td>' + esc(c.children_count) + '</td>'
            + '<td>' + esc(c.products_count) + '</td>'
            + '<td>' + badge + '</td>'
            + '</tr>';
    }

    AdminTable.mount({
        tbody: 'catalog-category-rows',
        apiUrl: table.getAttribute('data-api-url'),
        form: 'catalog-category-filters',
        pagination: 'catalog-category-pagination',
        count: 'catalog-category-count',
        countSuffix: 'kategori',
        empty: { title: 'Tidak ada kategori', text: 'Belum ada kategori di semua toko.' },
        noMatch: { title: 'Tidak ada kategori yang cocok', text: 'Coba ubah kata kunci atau filternya.' },
        renderRow: rowHtml
    });
});
</script>
@endsection
