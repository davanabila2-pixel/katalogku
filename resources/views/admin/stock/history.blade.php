@extends('layouts.dashboard')
@section('title', 'Riwayat Stok')
@section('subtitle', $store->name.' — setiap perubahan tercatat dan dapat diaudit')
@section('content')
<div class="toolbar">
  <form method="GET" id="history-filters" style="display:flex;gap:8px">
    <select class="select" style="width:200px" name="product_id"><option value="">Semua produk</option>@foreach($products as $p)<option value="{{ $p->id }}" {{ (string)request('product_id')===(string)$p->id?'selected':'' }}>{{ $p->name }}</option>@endforeach</select>
    <select class="select" style="width:150px" name="type"><option value="">Semua tipe</option><option value="in" {{ request('type')==='in'?'selected':'' }}>Masuk</option><option value="out" {{ request('type')==='out'?'selected':'' }}>Keluar</option><option value="adjustment" {{ request('type')==='adjustment'?'selected':'' }}>Koreksi</option><option value="cancel" {{ request('type')==='cancel'?'selected':'' }}>Batal</option></select>
  </form>
  <span class="spacer"></span>
  <span class="category-hierarchy" id="history-count" style="margin-right:8px"></span>
</div>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table" id="history-table" data-api-url="{{ route('api.stock.history', $store) }}">
<thead><tr><th>Waktu</th><th>Produk</th><th>Tipe</th><th>Qty</th><th>Catatan</th><th>Oleh</th></tr></thead>
<tbody id="history-rows">
<tr><td colspan="6"><div class="table-loading"><span class="skeleton" style="width:140px"></span> Memuat riwayat stok…</div></td></tr>
</tbody>
</table>
</div></div>
<div id="history-pagination"></div>
<noscript><div class="alert alert-error" style="margin-top:16px">Riwayat stok ini dimuat dengan JavaScript. Aktifkan JavaScript untuk melihat datanya.</div></noscript>
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    var table = document.getElementById('history-table');
    if (!table || !window.AdminTable) { return; }

    var esc = AdminTable.esc;

    function rowHtml(m) {
        var quantity = (m.quantity > 0 ? '+' : '') + m.quantity;

        return '<tr>'
            + '<td style="white-space:nowrap">' + esc(m.created_at_formatted || '—') + '</td>'
            + '<td>' + esc(m.product_name || ('#' + m.product_id)) + '</td>'
            + '<td><span class="badge badge-neutral">' + esc(m.movement_type) + '</span></td>'
            + '<td>' + esc(quantity) + '</td>'
            + '<td>' + esc(m.notes || '—') + '</td>'
            + '<td>' + esc(m.creator_name || '—') + '</td>'
            + '</tr>';
    }

    AdminTable.mount({
        tbody: 'history-rows',
        apiUrl: table.getAttribute('data-api-url'),
        form: 'history-filters',
        pagination: 'history-pagination',
        count: 'history-count',
        countSuffix: 'mutasi',
        empty: { title: 'Belum ada pergerakan stok', text: 'Mutasi akan tercatat otomatis saat ada penjualan, batch, atau koreksi.' },
        noMatch: { title: 'Tidak ada mutasi yang cocok', text: 'Coba ubah filter produk atau tipe.' },
        renderRow: rowHtml
    });
});
</script>
@endsection
