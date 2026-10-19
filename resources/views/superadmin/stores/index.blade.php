@extends('layouts.dashboard')
@section('title', 'Toko')
@section('subtitle', 'Kelola seluruh toko lintas aplikasi')
@section('content')
<div class="toolbar">
  <form method="GET" id="store-filters" style="display:flex;gap:8px">
    <input class="search-pill" type="search" name="q" value="{{ request('q') }}" placeholder="Cari toko…" autocomplete="off">
    <select class="select" style="width:140px" name="status"><option value="">Semua</option><option value="active" {{ request('status')==='active'?'selected':'' }}>Aktif</option><option value="inactive" {{ request('status')==='inactive'?'selected':'' }}>Nonaktif</option></select>
  </form>
  <span class="spacer"></span>
  <span class="category-hierarchy" id="store-count" style="margin-right:8px"></span>
  <a class="btn btn-primary btn-sm" href="{{ route('superadmin.stores.create') }}">+ Buat Toko</a>
</div>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table" id="store-table" data-api-url="{{ route('api.superadmin.stores.index') }}">
<thead><tr><th>Nama</th><th>Slug / URL</th><th>Produk</th><th>Kategori</th><th>Batch</th><th>Status</th><th>Aksi</th></tr></thead>
<tbody id="store-rows">
<tr><td colspan="7"><div class="table-loading"><span class="skeleton" style="width:140px"></span> Memuat data toko…</div></td></tr>
</tbody>
</table>
</div></div>
<div id="store-pagination"></div>
<noscript><div class="alert alert-error" style="margin-top:16px">Daftar toko ini dimuat dengan JavaScript. Aktifkan JavaScript untuk melihat datanya.</div></noscript>
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    var table = document.getElementById('store-table');
    if (!table || !window.AdminTable) { return; }

    var esc = AdminTable.esc;

    function rowHtml(s) {
        var badge = s.status === 'active'
            ? '<span class="badge badge-success">' + esc(s.status) + '</span>'
            : '<span class="badge badge-neutral">' + esc(s.status) + '</span>';

        return '<tr>'
            + '<td><strong>' + esc(s.name) + '</strong></td>'
            + '<td><a href="' + esc(s.url) + '" target="_blank" rel="noopener">/' + esc(s.slug) + '</a></td>'
            + '<td>' + esc(s.products_count) + '</td>'
            + '<td>' + esc(s.categories_count) + '</td>'
            + '<td>' + esc(s.purchase_batches_count) + '</td>'
            + '<td>' + badge + '</td>'
            + '<td><a href="' + esc(s.show_url) + '">Kelola</a></td>'
            + '</tr>';
    }

    AdminTable.mount({
        tbody: 'store-rows',
        apiUrl: table.getAttribute('data-api-url'),
        form: 'store-filters',
        pagination: 'store-pagination',
        count: 'store-count',
        countSuffix: 'toko',
        empty: { title: 'Belum ada toko', text: 'Buat toko pertama lewat tombol di atas.' },
        noMatch: { title: 'Tidak ada toko yang cocok', text: 'Coba ubah kata kunci atau filternya.' },
        renderRow: rowHtml
    });
});
</script>
@endsection
