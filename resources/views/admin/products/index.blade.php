@extends('layouts.dashboard')
@section('title', 'Produk')
@section('subtitle', $store->name)
@section('content')
<form method="GET" id="product-filters" class="toolbar">
  <div class="toolbar-left">
    <select class="select" style="width:160px" name="category_id"><option value="">Semua kategori</option>@foreach($categories as $c)<option value="{{ $c->id }}" {{ (string)request('category_id')===(string)$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach</select>
    <select class="select" style="width:130px" name="status"><option value="">Semua status</option><option value="draft" {{ request('status')==='draft'?'selected':'' }}>Draft</option><option value="active" {{ request('status')==='active'?'selected':'' }}>Aktif</option><option value="inactive" {{ request('status')==='inactive'?'selected':'' }}>Nonaktif</option></select>
    <select class="select" style="width:160px" name="visibility"><option value="">Semua visibilitas</option><option value="visible" {{ request('visibility')==='visible'?'selected':'' }}>Tampil di katalog</option><option value="hidden" {{ request('visibility')==='hidden'?'selected':'' }}>Tersembunyi</option></select>
  </div>
  <div class="toolbar-right">
    <x-search-input placeholder="Cari produk/SKU…" width="250px" />
    <span class="category-hierarchy" id="product-count"></span>
    <button class="btn btn-primary btn-sm" type="button" id="product-create">+ Tambah Produk</button>
  </div>
</form>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table" id="product-table" data-api-url="{{ route('api.products.index', $store) }}" data-form-data-url="{{ route('api.products.form_data', $store) }}" data-store-url="{{ route('api.products.store', $store) }}">
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
    var formDataUrl = table.getAttribute('data-form-data-url');
    var storeUrl = table.getAttribute('data-store-url');

    function money(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
    }

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

        var actions = '<button type="button" data-action="edit" data-form-data-url="' + esc(p.form_data_url) + '" data-name="' + esc(p.name) + '" title="Edit produk" style="background:none;border:none;padding:0;color:var(--primary);cursor:pointer;font-size:14px">Edit</button>';
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

    // ---- Modal tambah/edit produk tanpa reload halaman ----

    function bundleSelected(form) {
        var radio = form.querySelector('input[name="is_bundle"]:checked');
        return !!radio && radio.value === '1';
    }

    function openProductForm(dataUrl, title) {
        AdminApi.get(dataUrl).then(function (data) {
            var product = data.product || null;
            var isEdit = !!product;
            var categories = data.categories || [];
            var components = data.components || [];
            var isBundle = product ? !!product.is_bundle : false;
            var bundleMap = {};
            (data.bundle_items || []).forEach(function (item) { bundleMap[item.product_id] = item; });

            /* Info biaya, stok & barcode (hanya saat edit) */
            var infoHtml = '';
            if (isEdit) {
                var barcode = data.barcode || {};
                var hppText = product.hpp === null || product.hpp === undefined ? '— (belum ada batch)' : money(product.hpp);
                var marginText = product.margin === null || product.margin === undefined ? '—' : money(product.margin);

                infoHtml = '<div class="modal-subcard">'
                    + '<div class="modal-inline-stat">'
                    + (product.is_bundle ? '<span class="badge" style="background:#0064e0;color:#fff;margin-right:6px">Setelan</span>' : '')
                    + 'HPP: <strong>' + hppText + '</strong> · Estimasi margin: <strong>' + marginText + '</strong> · Stok: <strong>' + esc(product.stock) + (product.is_bundle ? ' paket (otomatis)' : '') + '</strong>'
                    + '<br><span data-barcode-code>Kode barcode: <strong style="font-family:monospace">' + esc(barcode.code || '—') + '</strong></span>'
                    + '</div>'
                    + '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">'
                    + (barcode.label_url ? '<a class="btn btn-secondary btn-sm" href="' + esc(barcode.label_url) + '" target="_blank" rel="noopener">Unduh / cetak label</a>' : '')
                    + (barcode.scan_url ? '<a class="btn btn-secondary btn-sm" href="' + esc(barcode.scan_url) + '" target="_blank" rel="noopener">Uji scan</a>' : '')
                    + (barcode.regenerate_url ? '<button type="button" class="btn btn-ghost btn-sm" data-barcode-regen="' + esc(barcode.regenerate_url) + '">' + (barcode.label_url ? 'Generate ulang barcode' : 'Generate barcode') + '</button>' : '')
                    + '</div></div>';
            }

            /* Tipe produk */
            var typesHtml = '<div class="field"><label>Tipe produk</label>'
                + '<div style="display:flex;gap:18px;flex-wrap:wrap;padding:10px 14px;background:var(--surface-soft);border-radius:var(--r-lg)">'
                + '<label class="modal-check"><input type="radio" name="is_bundle" value="0"' + (isBundle ? '' : ' checked') + '> <span>Produk satuan</span></label>'
                + '<label class="modal-check"><input type="radio" name="is_bundle" value="1"' + (isBundle ? ' checked' : '') + '> <span><strong>Setelan / paket</strong> — gabungan beberapa produk</span></label>'
                + '</div></div>';

            /* Visibilitas produk satuan */
            var visHtml = '<div class="field" data-single-visibility' + (isBundle ? ' style="display:none"' : '') + '>'
                + '<label class="modal-check"><input type="checkbox" name="is_hidden_from_catalog" value="1"' + (product && product.is_hidden_from_catalog ? ' checked' : '') + '> <span>Sembunyikan produk satuan ini dari katalog publik</span></label>'
                + '<small class="modal-help">Pakai bila produk ini hanya dijual sebagai bagian dari paket setelan.</small>'
                + '</div>';

            /* Editor komponen paket setelan */
            var bundleHtml = '<div class="modal-subcard" data-bundle-section' + (isBundle ? '' : ' style="display:none"') + '>'
                + '<p class="modal-subcard-title">Komponen isi paket (minimal 2 produk)</p>'
                + '<small class="modal-help">Stok paket dihitung otomatis dari stok terendah produk komponen.</small>';

            if (components.length) {
                bundleHtml += '<div class="modal-bundle-scroll" style="margin-top:8px">';
                components.forEach(function (comp) {
                    var item = bundleMap[comp.id];
                    bundleHtml += '<div class="modal-bundle-row" data-price="' + esc(comp.selling_price) + '" data-stock="' + esc(comp.stock) + '">'
                        + '<input type="checkbox" class="pm-bundle-check" name="bundle_items[' + esc(comp.id) + '][product_id]" value="' + esc(comp.id) + '"' + (item ? ' checked' : '') + '>'
                        + '<label class="modal-bundle-name">' + esc(comp.name)
                        + ' <span style="color:var(--steel)">· ' + money(comp.selling_price) + ' · stok ' + esc(comp.stock) + '</span></label>'
                        + '<input type="number" class="input pm-bundle-qty" name="bundle_items[' + esc(comp.id) + '][quantity]" min="1" value="' + (item ? esc(item.quantity) : 1) + '"' + (item ? '' : ' disabled') + '>'
                        + '</div>';
                });
                bundleHtml += '</div><div class="modal-help" data-bundle-calc style="margin-top:10px"></div>';
            } else {
                bundleHtml += '<div class="alert alert-error" style="margin:8px 0 0;font-size:13px">Belum ada produk satuan di toko ini. Buat produk satuan terlebih dahulu sebelum membuat setelan.</div>';
            }
            bundleHtml += '</div>';

            /* Isian utama */
            var categoryOptionsHtml = '<option value="">— Tanpa kategori —</option>';
            categories.forEach(function (c) {
                categoryOptionsHtml += '<option value="' + esc(c.id) + '"'
                    + (product && String(product.category_id) === String(c.id) ? ' selected' : '') + '>'
                    + esc(c.name) + '</option>';
            });

            var statusOptionsHtml = '';
            ['draft', 'active', 'inactive'].forEach(function (status) {
                var current = product ? product.status : 'draft';
                statusOptionsHtml += '<option value="' + status + '"' + (current === status ? ' selected' : '') + '>'
                    + ({ draft: 'Draft', active: 'Aktif (tampil publik)', inactive: 'Nonaktif' })[status] + '</option>';
            });

            var formHtml = infoHtml + typesHtml + visHtml + bundleHtml
                + '<div class="field"><label for="pm-name">Nama produk</label>'
                + '<input id="pm-name" class="input" type="text" name="name" value="' + esc(product ? product.name : '') + '" required placeholder="cth: Setelan Casual Vintage (Kaos + Celana)"></div>'
                + '<div class="form-row">'
                + '<div class="field"><label for="pm-slug">Slug (opsional)</label><input id="pm-slug" class="input" type="text" name="slug" value="' + esc(product ? product.slug : '') + '"></div>'
                + '<div class="field"><label for="pm-sku">SKU (opsional)</label><input id="pm-sku" class="input" type="text" name="sku" value="' + esc(product ? product.sku : '') + '"></div>'
                + '</div>'
                + '<div class="field"><label for="pm-category">Kategori</label><select id="pm-category" class="select" name="category_id">' + categoryOptionsHtml + '</select></div>'
                + '<div class="field"><label for="pm-description">Deskripsi</label><textarea id="pm-description" class="input" name="description" rows="3">' + esc(product ? product.description : '') + '</textarea></div>'
                + '<div class="form-row">'
                + '<div class="field"><label for="pm-price">Harga jual (Rp)</label>'
                + '<input id="pm-price" class="input" type="number" name="selling_price" min="0" step="0.01" value="' + esc(product ? product.selling_price : '') + '" required>'
                + '<small class="modal-help" data-price-hint></small></div>'
                + '<div class="field"><label for="pm-status">Status publikasi</label><select id="pm-status" class="select" name="status">' + statusOptionsHtml + '</select></div>'
                + '</div>'
                + '<div class="field"><label for="pm-image">Gambar foto produk</label><input id="pm-image" class="input" type="file" name="image" accept="image/*">'
                + '<small class="modal-help">' + (isEdit ? 'Biarkan kosong bila tidak ingin mengganti gambar. ' : 'Opsional. ') + 'Gambar diunggah ke Google Drive toko.</small></div>';

            return AdminUI.formModal({
                title: title,
                width: 'xl',
                submitText: isEdit ? 'Simpan perubahan' : 'Simpan produk',
                body: formHtml,
                onMount: function (form) {
                    var bundleSection = form.querySelector('[data-bundle-section]');
                    var singleVisibility = form.querySelector('[data-single-visibility]');
                    var calcBox = form.querySelector('[data-bundle-calc]');
                    var priceHint = form.querySelector('[data-price-hint]');

                    function recalc() {
                        if (!bundleSelected(form)) {
                            if (priceHint) { priceHint.textContent = ''; }
                            return;
                        }

                        var total = 0;
                        var minSets = null;
                        var checkedCount = 0;

                        form.querySelectorAll('.modal-bundle-row').forEach(function (row) {
                            var check = row.querySelector('.pm-bundle-check');
                            if (!check || !check.checked) { return; }

                            checkedCount++;
                            var price = parseFloat(row.getAttribute('data-price')) || 0;
                            var stock = parseInt(row.getAttribute('data-stock'), 10) || 0;
                            var qtyInput = row.querySelector('.pm-bundle-qty');
                            var qty = parseInt(qtyInput ? qtyInput.value : 1, 10) || 1;

                            total += price * qty;
                            var sets = Math.floor(stock / qty);
                            minSets = minSets === null ? sets : Math.min(minSets, sets);
                        });

                        if (calcBox) {
                            calcBox.innerHTML = checkedCount < 2
                                ? '⚠️ Pilih minimal 2 produk agar setelan dapat dibentuk.'
                                : '✅ Total harga normal bila beli terpisah: <strong>' + money(total) + '</strong> · Estimasi stok paket: <strong>' + Math.max(minSets || 0, 0) + ' paket</strong>';
                        }
                        if (priceHint) {
                            priceHint.textContent = checkedCount >= 2 ? '💡 Total harga normal bila beli terpisah: ' + money(total) : '';
                        }
                    }

                    function syncBundleState() {
                        var bundle = bundleSelected(form);
                        if (bundleSection) {
                            bundleSection.style.display = bundle ? '' : 'none';
                            bundleSection.querySelectorAll('input').forEach(function (input) {
                                if (input.classList.contains('pm-bundle-qty')) { return; }
                                input.disabled = !bundle;
                            });
                            bundleSection.querySelectorAll('.pm-bundle-check').forEach(function (check) {
                                var qty = check.closest('.modal-bundle-row').querySelector('.pm-bundle-qty');
                                if (qty) { qty.disabled = !bundle || !check.checked; }
                            });
                        }
                        if (singleVisibility) { singleVisibility.style.display = bundle ? 'none' : ''; }
                        recalc();
                    }

                    form.querySelectorAll('input[name="is_bundle"]').forEach(function (radio) {
                        radio.addEventListener('change', syncBundleState);
                    });

                    if (bundleSection) {
                        bundleSection.addEventListener('change', function (event) {
                            var check = event.target.closest('.pm-bundle-check');
                            if (check) {
                                var qty = check.closest('.modal-bundle-row').querySelector('.pm-bundle-qty');
                                if (qty) { qty.disabled = !bundleSelected(form) || !check.checked; }
                            }
                            recalc();
                        });
                        bundleSection.addEventListener('input', recalc);
                    }

                    var regenBtn = form.querySelector('[data-barcode-regen]');
                    if (regenBtn) {
                        regenBtn.addEventListener('click', function () {
                            AdminUI.confirm({
                                title: 'Generate ulang barcode?',
                                message: 'Label barcode baru akan dibuat dan diunggah ke Google Drive toko.',
                                confirmText: 'Ya, generate'
                            }).then(function (ok) {
                                if (!ok) { return; }
                                regenBtn.disabled = true;
                                AdminApi.request(regenBtn.getAttribute('data-barcode-regen'), { method: 'POST' }).then(function (json) {
                                    AdminUI.toast(json.message, 'success');
                                    var codeEl = form.querySelector('[data-barcode-code]');
                                    if (codeEl) {
                                        codeEl.innerHTML = 'Kode barcode: <strong style="font-family:monospace">' + esc(json.barcode.code || '—') + '</strong>';
                                    }
                                }).catch(function (error) {
                                    AdminUI.toast(error.message, 'error');
                                }).then(function () {
                                    regenBtn.disabled = false;
                                });
                            });
                        });
                    }

                    syncBundleState();
                },
                submit: function (values, ui) {
                    if (bundleSelected(ui.form) && ui.form.querySelectorAll('.pm-bundle-check:checked').length < 2) {
                        return Promise.reject(new Error('Paket setelan wajib memiliki minimal 2 produk komponen.'));
                    }

                    var payload = ui.formData();
                    if (isEdit) { payload.append('_method', 'PUT'); }

                    var url = isEdit ? product.update_url : storeUrl;

                    return AdminApi.request(url, { method: 'POST', body: payload }).then(function (json) {
                        var message = json.message + (json.barcode_notice ? ' ' + json.barcode_notice : '');
                        AdminUI.toast(message, 'success');
                        controller.reload(isEdit ? undefined : 1);
                    });
                }
            });
        }).catch(function (error) {
            AdminUI.toast(error.message, 'error');
        });
    }

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

    document.getElementById('product-create').addEventListener('click', function () {
        openProductForm(formDataUrl, 'Tambah produk');
    });

    controller.tbody.addEventListener('click', function (event) {
        var editBtn = event.target.closest('[data-action="edit"]');
        if (editBtn) {
            openProductForm(editBtn.getAttribute('data-form-data-url'), 'Edit produk — ' + editBtn.getAttribute('data-name'));
            return;
        }

        var destroyBtn = event.target.closest('[data-action="destroy"]');
        if (destroyBtn) { arsipkan(destroyBtn); return; }

        var adjustBtn = event.target.closest('[data-action="adjust"]');
        if (adjustBtn) { koreksiStok(adjustBtn); }
    });
});
</script>
@endsection
