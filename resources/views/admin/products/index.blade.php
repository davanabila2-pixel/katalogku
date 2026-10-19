@extends('layouts.dashboard')
@section('title', 'Produk')
@section('subtitle', $store->name)
@section('content')
<div class="toolbar">
  <form method="GET" id="product-filters" style="display:flex;gap:8px;flex-wrap:wrap">
    <input class="search-pill" type="search" name="q" value="{{ request('q') }}" placeholder="Cari produk/SKU…" autocomplete="off">
    <select class="select" style="width:160px" name="category_id"><option value="">Semua kategori</option>@foreach($categories as $c)<option value="{{ $c->id }}" {{ (string)request('category_id')===(string)$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach</select>
    <select class="select" style="width:130px" name="status"><option value="">Semua status</option><option value="draft" {{ request('status')==='draft'?'selected':'' }}>Draft</option><option value="active" {{ request('status')==='active'?'selected':'' }}>Aktif</option><option value="inactive" {{ request('status')==='inactive'?'selected':'' }}>Nonaktif</option></select>
    <select class="select" style="width:160px" name="visibility"><option value="">Semua visibilitas</option><option value="visible" {{ request('visibility')==='visible'?'selected':'' }}>Tampil di katalog</option><option value="hidden" {{ request('visibility')==='hidden'?'selected':'' }}>Tersembunyi</option></select>
  </form>
  <span class="spacer"></span>
  <span class="category-hierarchy" id="product-count" style="margin-right:8px"></span>
  <a class="btn btn-primary btn-sm" href="{{ route('admin.products.create', $store) }}">+ Tambah Produk</a>
</div>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table" id="product-table" data-api-url="{{ route('api.products.index', $store) }}">
<thead><tr><th>Produk</th><th>Kategori</th><th>Harga Jual</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead>
<tbody id="product-rows">
<tr><td colspan="6"><div class="table-loading"><span class="skeleton" style="width:140px"></span> Memuat data produk…</div></td></tr>
</tbody>
</table>
</div></div>
<div id="product-pagination"></div>
<noscript><div class="alert alert-error" style="margin-top:16px">Daftar produk ini dimuat dengan JavaScript. Aktifkan JavaScript untuk melihat datanya.</div></noscript>
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    var table = document.getElementById('product-table');
    if (!table || !window.AdminTable) { return; }

    var esc = AdminTable.esc;
    var statusBadge = AdminTable.statusBadge;

    function rowHtml(p) {
        var badges = '';
        if (p.is_bundle) {
            badges = ' <span class="badge" style="background:#e0e7ff;color:#3730a3;font-size:11px;font-weight:600;margin-left:4px">Setelan</span>';
        } else if (p.is_hidden) {
            badges = ' <span class="badge" style="background:#fef3c7;color:#92400e;font-size:11px;font-weight:600;margin-left:4px">Tersembunyi</span>';
        }

        var barcodeChip = '';
        if (p.barcode) {
            barcodeChip = '<a href="' + esc(p.barcode_url || p.scan_url) + '" target="_blank" rel="noopener" style="text-decoration:none" title="Scan / Buka Label Barcode">'
                + '<span style="font-family:monospace;background:#f1f5f9;color:#334155;padding:1px 5px;border-radius:4px;border:1px solid #cbd5e1;font-size:11px">🏷️ ' + esc(p.barcode) + '</span></a> · ';
        }

        var stockCell = p.is_bundle
            ? '<strong>' + esc(p.stock) + '</strong> <span style="font-size:11px;color:var(--steel);display:block">Otomatis</span>'
            : esc(p.stock);

        var actions = '<a href="' + esc(p.edit_url) + '">Edit</a>';
        if (p.barcode_url) {
            actions += ' · <a href="' + esc(p.barcode_url) + '" target="_blank" rel="noopener" title="Buka Label Barcode di Google Drive">Barcode</a>';
        }
        if (!p.is_bundle) {
            actions += ' · <button type="button" data-action="adjust" data-url="' + esc(p.adjust_url) + '" data-name="' + esc(p.name) + '" data-stock="' + esc(p.stock) + '" title="Koreksi stok cepat" style="background:none;border:none;padding:0;color:var(--primary);cursor:pointer;font-size:14px">Stok</button>';
        }
        actions += ' · <button type="button" data-action="destroy" data-url="' + esc(p.destroy_url) + '" data-name="' + esc(p.name) + '" title="Hapus / arsipkan produk" style="background:none;border:none;padding:0;color:var(--critical);cursor:pointer;font-size:14px">Arsip</button>';

        return '<tr>'
            + '<td><strong>' + esc(p.name) + '</strong>' + badges
            + '<br><span class="category-hierarchy">' + barcodeChip + esc(p.sku ? 'SKU: ' + p.sku : (p.slug || '')) + '</span></td>'
            + '<td>' + esc(p.category || '—') + '</td>'
            + '<td>' + esc(p.selling_price_formatted) + '</td>'
            + '<td>' + stockCell + '</td>'
            + '<td>' + statusBadge(p.status) + '</td>'
            + '<td style="white-space:nowrap">' + actions + '</td>'
            + '</tr>';
    }

    var controller = AdminTable.mount({
        tbody: 'product-rows',
        apiUrl: table.getAttribute('data-api-url'),
        form: 'product-filters',
        pagination: 'product-pagination',
        count: 'product-count',
        countSuffix: 'produk',
        empty: { title: 'Belum ada produk', text: 'Tambahkan produk pertama untuk tokomu.' },
        noMatch: { title: 'Tidak ada produk yang cocok', text: 'Coba ubah kata kunci atau filternya.' },
        renderRow: rowHtml
    });

    if (!controller) { return; }

    // ---- Aksi baris tanpa reload halaman ----

    function arsipkan(button) {
        var name = button.getAttribute('data-name');

        AdminUI.confirm({
            title: 'Hapus / arsipkan produk?',
            message: name + ' akan dihapus. Kalau produk sudah punya riwayat stok, produk hanya diarsipkan (dinonaktifkan).',
            confirmText: 'Ya, lanjutkan',
            danger: true
        }).then(function (ok) {
            if (!ok) { return; }

            AdminApi.request(button.getAttribute('data-url'), { method: 'DELETE' }).then(function (json) {
                AdminUI.toast(json.message, 'success');
                controller.reload();
            }).catch(function (error) {
                AdminUI.toast(error.message, 'error');
            });
        });
    }

    function koreksiStok(button) {
        var name = button.getAttribute('data-name');
        var stock = button.getAttribute('data-stock');

        AdminUI.formModal({
            title: 'Koreksi stok',
            message: name + ' — stok saat ini: ' + stock + '.',
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
    }

    controller.tbody.addEventListener('click', function (event) {
        var destroyBtn = event.target.closest('[data-action="destroy"]');
        if (destroyBtn) { arsipkan(destroyBtn); return; }

        var adjustBtn = event.target.closest('[data-action="adjust"]');
        if (adjustBtn) { koreksiStok(adjustBtn); }
    });
});
</script>
@endsection
