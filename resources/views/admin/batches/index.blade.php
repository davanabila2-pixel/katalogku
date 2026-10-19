@extends('layouts.dashboard')
@section('title', 'Batch Pembelian')
@section('subtitle', $store->name)
@section('content')
<div class="toolbar">
  <form method="GET" id="batch-filters" style="display:flex;gap:8px">
    <input class="search-pill" type="search" name="q" value="{{ request('q') }}" placeholder="Cari kode/supplier…" autocomplete="off">
  </form>
  <span class="spacer"></span>
  <span class="category-hierarchy" id="batch-count" style="margin-right:8px"></span>
  <a class="btn btn-primary btn-sm" href="{{ route('admin.batches.create', $store) }}">+ Buat Batch</a>
</div>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table" id="batch-table" data-api-url="{{ route('api.batches.index', $store) }}">
<thead><tr><th>Kode</th><th>Tanggal</th><th>Supplier</th><th>Item</th><th>Status</th><th>Aksi</th></tr></thead>
<tbody id="batch-rows">
<tr><td colspan="6"><div class="table-loading"><span class="skeleton" style="width:140px"></span> Memuat data batch…</div></td></tr>
</tbody>
</table>
</div></div>
<div id="batch-pagination"></div>
<noscript><div class="alert alert-error" style="margin-top:16px">Daftar batch ini dimuat dengan JavaScript. Aktifkan JavaScript untuk melihat datanya.</div></noscript>
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    var table = document.getElementById('batch-table');
    if (!table || !window.AdminTable) { return; }

    var esc = AdminTable.esc;

    function rowHtml(b) {
        var badge = b.status === 'active'
            ? '<span class="badge badge-success">' + esc(b.status) + '</span>'
            : '<span class="badge badge-neutral">' + esc(b.status) + '</span>';

        return '<tr>'
            + '<td><strong>' + esc(b.batch_code) + '</strong></td>'
            + '<td>' + esc(b.purchase_date_formatted || '—') + '</td>'
            + '<td>' + esc(b.supplier_name || '—') + '</td>'
            + '<td>' + esc(b.items_count) + '</td>'
            + '<td>' + badge + '</td>'
            + '<td><a href="' + esc(b.show_url) + '">Detail</a></td>'
            + '</tr>';
    }

    AdminTable.mount({
        tbody: 'batch-rows',
        apiUrl: table.getAttribute('data-api-url'),
        form: 'batch-filters',
        pagination: 'batch-pagination',
        count: 'batch-count',
        countSuffix: 'batch',
        empty: { title: 'Belum ada batch', text: 'Catat pembelian barang pertama di sini.' },
        noMatch: { title: 'Tidak ada batch yang cocok', text: 'Coba ubah kata kunci pencarian.' },
        renderRow: rowHtml
    });
});
</script>
@endsection
