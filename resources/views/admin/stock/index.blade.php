@extends('layouts.dashboard')
@section('title', 'Stok')
@section('subtitle', $store->name)
@section('content')
<form method="GET" id="stock-filters" class="toolbar">
  <div class="toolbar-left">
    <select class="select" style="width:150px" name="filter"><option value="">Semua</option><option value="low" {{ request('filter')==='low'?'selected':'' }}>Rendah (≤5)</option><option value="empty" {{ request('filter')==='empty'?'selected':'' }}>Habis</option></select>
  </div>
  <div class="toolbar-right">
    <x-search-input placeholder="Cari produk…" width="250px" />
    <span class="category-hierarchy" id="stock-count"></span>
  </div>
</form>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table" id="stock-table" data-api-url="{{ route('api.stock.index', $store) }}">
<thead><tr><th>Produk</th><th>Kategori</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead>
<tbody id="stock-rows">
<tr><td colspan="5"><div class="table-loading"><span class="skeleton" style="width:140px"></span> Memuat data stok…</div></td></tr>
</tbody>
</table>
</div></div>
<div id="stock-pagination"></div>
<noscript><div class="alert alert-error" style="margin-top:16px">Daftar stok ini dimuat dengan JavaScript. Aktifkan JavaScript untuk melihat datanya.</div></noscript>
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    var table = document.getElementById('stock-table');
    if (!table || !window.AdminTable) { return; }

    var esc = AdminTable.esc;

    function rowHtml(p) {
        var badge = p.stock <= 0
            ? '<span class="badge badge-critical">Habis</span>'
            : (p.stock <= 5 ? '<span class="badge badge-warning">Rendah</span>' : '<span class="badge badge-success">Aman</span>');

        var bundleNote = p.is_bundle
            ? ' <span class="badge" style="background:#e0e7ff;color:#3730a3;font-size:11px;font-weight:600;margin-left:4px">Setelan</span>'
            : '';

        var action = p.is_bundle
            ? '<span class="category-hierarchy">Otomatis</span>'
            : '<button type="button" data-action="adjust" data-url="' + esc(p.adjust_url) + '" data-name="' + esc(p.name) + '" data-stock="' + esc(p.stock) + '" title="Koreksi stok cepat" style="background:none;border:none;padding:0;color:var(--primary);cursor:pointer;font-size:14px">Koreksi</button>';

        return '<tr>'
            + '<td><strong>' + esc(p.name) + '</strong>' + bundleNote
            + (p.sku ? '<br><span class="category-hierarchy">SKU: ' + esc(p.sku) + '</span>' : '') + '</td>'
            + '<td>' + esc(p.category || '—') + '</td>'
            + '<td><strong>' + esc(p.stock) + '</strong></td>'
            + '<td>' + badge + '</td>'
            + '<td style="white-space:nowrap">' + action + '</td>'
            + '</tr>';
    }

    var controller = AdminTable.mount({
        tbody: 'stock-rows',
        apiUrl: table.getAttribute('data-api-url'),
        form: 'stock-filters',
        pagination: 'stock-pagination',
        count: 'stock-count',
        countSuffix: 'produk',
        empty: { title: 'Belum ada produk', text: 'Tambahkan produk dulu untuk melihat ringkasan stok.' },
        noMatch: { title: 'Tidak ada stok yang cocok', text: 'Coba ubah kata kunci atau filternya.' },
        renderRow: rowHtml
    });

    if (!controller) { return; }

    // Koreksi stok cepat tanpa reload halaman.
    controller.tbody.addEventListener('click', function (event) {
        var button = event.target.closest('[data-action="adjust"]');
        if (!button) { return; }

        AdminUI.formModal({
            title: 'Koreksi stok',
            message: button.getAttribute('data-name') + ' — stok saat ini: ' + button.getAttribute('data-stock') + '.',
            submitText: 'Koreksi stok',
            fields: [
                { name: 'quantity', label: 'Selisih stok', type: 'number', required: true, placeholder: 'cth: -2', help: 'Positif menambah, negatif mengurangi. Tidak boleh 0.' },
                { name: 'reason', label: 'Alasan koreksi', type: 'textarea', required: true, placeholder: 'cth: koreksi hasil stok opname mingguan' }
            ]
        }).then(function (values) {
            if (!values) { return; }

            AdminApi.request(button.getAttribute('data-url'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ quantity: parseInt(values.quantity, 10), reason: values.reason })
            }).then(function (json) {
                AdminUI.toast(json.message + ' Stok baru: ' + json.stock + '.', 'success');
                controller.reload();
            }).catch(function (error) {
                AdminUI.toast(error.message, 'error');
            });
        });
    });
});
</script>
@endsection
