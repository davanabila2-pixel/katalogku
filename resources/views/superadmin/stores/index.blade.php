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
  <button class="btn btn-primary btn-sm" type="button" id="store-create">+ Buat Toko</button>
</div>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table" id="store-table" data-api-url="{{ route('api.superadmin.stores.index') }}" data-store-url="{{ route('api.superadmin.stores.store') }}">
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
    var storeUrl = table.getAttribute('data-store-url');

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
            + '<td style="white-space:nowrap"><a href="' + esc(s.show_url) + '">Kelola</a> · '
            + '<button type="button" data-action="edit" data-form-data-url="' + esc(s.form_data_url) + '" data-name="' + esc(s.name) + '" title="Edit toko" style="background:none;border:none;padding:0;color:var(--primary);cursor:pointer;font-size:14px">Edit</button></td>'
            + '</tr>';
    }

    function autoSlug(value) {
        return String(value || '').toLowerCase().normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .substring(0, 100);
    }

    function openStoreModal(store, adminEmail) {
        var isEdit = !!store;

        AdminUI.formModal({
            title: isEdit ? 'Edit toko — ' + store.name : 'Buat toko',
            width: 'lg',
            submitText: isEdit ? 'Simpan perubahan' : 'Simpan toko',
            fields: [
                { name: 'name', label: 'Nama toko', required: true, value: isEdit ? store.name : '', placeholder: 'cth: Kemeja Vintage Official' },
                { name: 'slug', label: 'Slug URL (opsional)', value: isEdit ? store.slug : '', help: 'Kosongkan agar dibuat otomatis dari nama. Mengubah slug mengalihkan tautan lama (301).' },
                { name: 'status', label: 'Status', type: 'select', options: [{ value: 'active', label: 'Aktif' }, { value: 'inactive', label: 'Nonaktif' }], value: isEdit ? store.status : 'active' },
                { name: 'description', label: 'Deskripsi', type: 'textarea', value: isEdit ? store.description : '' },
                { name: 'contact_information', label: 'Kontak', value: isEdit ? store.contact_information : '' },
                { name: 'address', label: 'Alamat', type: 'textarea', value: isEdit ? store.address : '' },
                { name: 'logo', label: 'Logo (opsional)', type: 'file', help: (isEdit ? 'Biarkan kosong bila tidak ingin mengganti logo. ' : 'Opsional. ') + 'Diunggah ke Google Drive toko.' },
                { name: 'admin_email', label: 'Email admin utama (opsional)', value: adminEmail || '', placeholder: 'cth: admin@tokomu.com', help: 'Menunjuk (atau membuat) akun admin utama toko. Superadmin tidak dijadikan admin toko.' },
                { name: 'admin_name', label: 'Nama admin (jika akun baru)', value: '' }
            ],
            onMount: function (form) {
                var nameEl = form.elements['name'];
                var slugEl = form.elements['slug'];
                if (!nameEl || !slugEl) { return; }

                var touched = false;
                nameEl.addEventListener('input', function () {
                    if (!touched) { slugEl.value = autoSlug(nameEl.value); }
                });
                slugEl.addEventListener('input', function () { touched = true; });
            },
            submit: function (values, ui) {
                var payload = ui.formData();
                var url = storeUrl;

                if (isEdit) {
                    payload.append('_method', 'PUT');
                    url = store.update_url;
                }

                return AdminApi.request(url, { method: 'POST', body: payload }).then(function (json) {
                    AdminUI.toast(json.message, 'success');
                    controller.reload(isEdit ? undefined : 1);
                });
            }
        });
    }

    var controller = AdminTable.mount({
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

    if (!controller) { return; }

    document.getElementById('store-create').addEventListener('click', function () {
        openStoreModal(null, '');
    });

    controller.tbody.addEventListener('click', function (event) {
        var editBtn = event.target.closest('[data-action="edit"]');
        if (!editBtn) { return; }

        AdminApi.get(editBtn.getAttribute('data-form-data-url')).then(function (json) {
            openStoreModal(json.store, json.admin_email || '');
        }).catch(function (error) {
            AdminUI.toast(error.message, 'error');
        });
    });
});
</script>
@endsection
