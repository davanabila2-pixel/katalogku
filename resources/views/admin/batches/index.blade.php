@extends('layouts.dashboard')
@section('title', 'Batch Pembelian')
@section('subtitle', $store->name)
@section('content')
<form method="GET" id="batch-filters" class="toolbar">
  <div class="toolbar-left"></div>
  <div class="toolbar-right">
    <x-search-input placeholder="Cari kode/supplier…" width="250px" />
    <span class="category-hierarchy" id="batch-count"></span>
    <button class="btn btn-primary btn-sm" type="button" id="batch-create">+ Buat Batch</button>
  </div>
</form>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table" id="batch-table" data-api-url="{{ route('api.batches.index', $store) }}" data-options-url="{{ route('api.batches.options', $store) }}" data-store-url="{{ route('api.batches.store', $store) }}">
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
    var optionsUrl = table.getAttribute('data-options-url');
    var storeUrl = table.getAttribute('data-store-url');

    function money(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
    }

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

    var controller = AdminTable.mount({
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

    if (!controller) { return; }

    function openBatchModal() {
        AdminApi.get(optionsUrl).then(function (json) {
            var products = json.data || [];

            function productOptionsHtml(selected) {
                var html = '<option value="">— pilih —</option>';
                products.forEach(function (p) {
                    html += '<option value="' + esc(p.id) + '"' + (String(selected) === String(p.id) ? ' selected' : '') + '>'
                        + esc(p.name) + (p.sku ? ' (' + esc(p.sku) + ')' : '') + ' · stok ' + esc(p.stock) + '</option>';
                });
                return html;
            }

            function itemRowHtml(index) {
                return '<div data-batch-item style="display:grid;grid-template-columns:minmax(0,2.2fr) 84px 148px 40px;gap:10px;align-items:end;margin-top:8px">'
                    + '<div class="field" style="margin:0"><label>Produk</label><select class="select" name="items[' + index + '][product_id]">' + productOptionsHtml('') + '</select></div>'
                    + '<div class="field" style="margin:0"><label>Qty</label><input class="input" type="number" min="1" value="1" name="items[' + index + '][quantity]"></div>'
                    + '<div class="field" style="margin:0"><label>Harga beli/satuan</label><input class="input" type="number" min="0" step="0.01" name="items[' + index + '][unit_purchase_price]"></div>'
                    + '<button type="button" class="btn btn-ghost btn-sm" data-remove-item title="Hapus baris" style="padding:8px 10px;margin-bottom:1px">✕</button>'
                    + '</div>';
            }

            var body = '<div class="form-row">'
                + '<div class="field"><label for="bm-code">Kode batch (unik)</label><input id="bm-code" class="input" type="text" name="batch_code" required value="' + esc(json.suggested_code || '') + '"></div>'
                + '<div class="field"><label for="bm-date">Tanggal pembelian</label><input id="bm-date" class="input" type="date" name="purchase_date" required value="' + esc(json.today || '') + '"></div>'
                + '</div>'
                + '<div class="form-row">'
                + '<div class="field"><label for="bm-supplier">Supplier (opsional)</label><input id="bm-supplier" class="input" type="text" name="supplier_name"></div>'
                + '<div class="field"><label for="bm-cost">Biaya tambahan (Rp, dibagi proporsional)</label><input id="bm-cost" class="input" type="number" name="additional_cost" min="0" step="0.01" value="0"></div>'
                + '</div>'
                + '<div class="field"><label for="bm-notes">Catatan</label><textarea id="bm-notes" class="input" name="notes" rows="2"></textarea></div>'
                + '<div class="modal-subcard"><p class="modal-subcard-title">Item pembelian (produk milik toko ini)</p>'
                + '<div data-batch-items>' + itemRowHtml(0) + '</div>'
                + '<div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:12px;flex-wrap:wrap">'
                + '<button type="button" class="btn btn-secondary btn-sm" data-add-item>+ Tambah item</button>'
                + '<span class="modal-inline-stat" data-batch-total>Total: <strong>Rp 0</strong></span>'
                + '</div></div>';

            return AdminUI.formModal({
                title: 'Buat batch pembelian',
                width: 'xl',
                submitText: 'Simpan batch',
                body: body,
                onMount: function (form) {
                    var itemsBox = form.querySelector('[data-batch-items]');
                    var totalBox = form.querySelector('[data-batch-total]');
                    var nextIndex = 1;

                    function recalc() {
                        var total = parseFloat(form.elements['additional_cost'].value) || 0;
                        itemsBox.querySelectorAll('[data-batch-item]').forEach(function (row) {
                            var qty = parseFloat(row.querySelector('input[name$="[quantity]"]').value) || 0;
                            var price = parseFloat(row.querySelector('input[name$="[unit_purchase_price]"]').value) || 0;
                            total += qty * price;
                        });
                        totalBox.innerHTML = 'Total: <strong>' + money(total) + '</strong>';
                    }

                    form.querySelector('[data-add-item]').addEventListener('click', function () {
                        itemsBox.insertAdjacentHTML('beforeend', itemRowHtml(nextIndex));
                        nextIndex++;
                        recalc();
                    });

                    itemsBox.addEventListener('click', function (event) {
                        var removeBtn = event.target.closest('[data-remove-item]');
                        if (!removeBtn) { return; }
                        if (itemsBox.querySelectorAll('[data-batch-item]').length <= 1) {
                            AdminUI.toast('Minimal satu item pembelian.', 'error');
                            return;
                        }
                        removeBtn.closest('[data-batch-item]').remove();
                        recalc();
                    });

                    form.addEventListener('input', recalc);
                    recalc();
                },
                submit: function (values, ui) {
                    var firstSelect = ui.form.querySelector('[data-batch-item] [name$="[product_id]"]');
                    if (firstSelect && !firstSelect.value) {
                        return Promise.reject(new Error('Pilih minimal satu produk untuk batch ini.'));
                    }

                    return AdminApi.request(storeUrl, { method: 'POST', body: ui.formData() }).then(function (json) {
                        AdminUI.toast(json.message, 'success');
                        controller.reload(1);
                    });
                }
            });
        }).catch(function (error) {
            AdminUI.toast(error.message, 'error');
        });
    }

    document.getElementById('batch-create').addEventListener('click', openBatchModal);
});
</script>
@endsection
