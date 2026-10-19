@extends('layouts.dashboard')
@section('title', 'Kategori')
@section('subtitle', $store->name)
@section('content')
<div class="toolbar">
  <form method="GET" id="category-filters" style="display:flex;gap:8px">
    <input class="search-pill" type="search" name="q" value="{{ request('q') }}" placeholder="Cari kategori…" autocomplete="off">
    <select class="select" style="width:150px" name="status"><option value="">Semua status</option><option value="active" {{ request('status')==='active'?'selected':'' }}>Aktif</option><option value="inactive" {{ request('status')==='inactive'?'selected':'' }}>Nonaktif</option></select>
  </form>
  <span class="spacer"></span>
  <span class="category-hierarchy" id="category-count" style="margin-right:8px"></span>
  <button class="btn btn-primary btn-sm" type="button" id="category-create">+ Tambah Kategori</button>
</div>
<div class="panel" style="padding:0">
<div class="table-wrap" style="border:none">
<table class="table" id="category-table" data-api-url="{{ route('api.categories.index', $store) }}" data-options-url="{{ route('api.categories.options', $store) }}" data-store-url="{{ route('api.categories.store', $store) }}">
<thead><tr><th>Nama</th><th>Induk</th><th>Sub</th><th>Produk</th><th>Urutan</th><th>Status</th><th>Aksi</th></tr></thead>
<tbody id="category-rows">
<tr><td colspan="7"><div class="table-loading"><span class="skeleton" style="width:140px"></span> Memuat data kategori…</div></td></tr>
</tbody>
</table>
</div>
</div>
<div id="category-pagination"></div>
<noscript><div class="alert alert-error" style="margin-top:16px">Daftar kategori ini dimuat dengan JavaScript. Aktifkan JavaScript untuk melihat datanya.</div></noscript>
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    var table = document.getElementById('category-table');
    if (!table || !window.AdminTable) { return; }

    var esc = AdminTable.esc;
    var optionsUrl = table.getAttribute('data-options-url');
    var storeUrl = table.getAttribute('data-store-url');

    function rowHtml(c) {
        var status = c.is_active
            ? '<span class="badge badge-success">Aktif</span>'
            : '<span class="badge badge-neutral">Nonaktif</span>';

        var payload = esc(JSON.stringify({
            id: c.id,
            name: c.name,
            slug: c.slug,
            description: c.description || '',
            parent_id: c.parent_id || '',
            sort_order: c.sort_order,
            is_active: !!c.is_active,
            update_url: c.update_url
        }));

        return '<tr>'
            + '<td><strong>' + esc(c.name) + '</strong><br><span class="category-hierarchy">' + esc(c.slug) + '</span></td>'
            + '<td>' + esc(c.parent || '—') + '</td>'
            + '<td>' + esc(c.children_count) + '</td>'
            + '<td>' + esc(c.products_count) + '</td>'
            + '<td>' + esc(c.sort_order) + '</td>'
            + '<td>' + status + '</td>'
            + '<td style="white-space:nowrap"><button type="button" data-action="edit" data-payload="' + payload + '" title="Edit kategori" style="background:none;border:none;padding:0;color:var(--primary);cursor:pointer;font-size:14px">Edit</button> · '
            + '<button type="button" data-action="destroy" data-url="' + esc(c.destroy_url) + '" data-name="' + esc(c.name) + '" title="Hapus kategori" style="background:none;border:none;padding:0;color:var(--critical);cursor:pointer;font-size:14px">Hapus</button></td>'
            + '</tr>';
    }

    function openCategoryModal(category) {
        var isEdit = !!category;
        var params = isEdit ? { exclude: category.id } : {};

        AdminApi.get(optionsUrl, params).then(function (json) {
            var parentOptions = [{ value: '', label: '— Kategori utama —' }];
            (json.data || []).forEach(function (p) {
                parentOptions.push({ value: p.id, label: p.name });
            });

            return AdminUI.formModal({
                title: isEdit ? 'Edit kategori' : 'Tambah kategori',
                width: 'lg',
                submitText: isEdit ? 'Simpan perubahan' : 'Simpan kategori',
                fields: [
                    { name: 'name', label: 'Nama', required: true, value: isEdit ? category.name : '', placeholder: 'cth: Kaos Polos' },
                    { name: 'slug', label: 'Slug (opsional)', value: isEdit ? category.slug : '', help: 'Kosongkan agar dibuat otomatis dari nama.' },
                    { name: 'parent_id', label: 'Kategori induk (opsional)', type: 'select', options: parentOptions, value: isEdit ? category.parent_id : '' },
                    { name: 'description', label: 'Deskripsi', type: 'textarea', value: isEdit ? category.description : '' },
                    { name: 'sort_order', label: 'Urutan tampil', type: 'number', min: 0, value: isEdit ? category.sort_order : 0 },
                    { name: 'is_active', label: 'Aktif (tampil di katalog)', type: 'checkbox', checked: isEdit ? category.is_active : true, help: 'Kategori nonaktif disembunyikan dari katalog publik.' },
                    { name: 'image', label: 'Gambar (opsional)', type: 'file', help: 'PNG/JPG/WebP maks. 5 MB — diunggah ke Google Drive toko.' }
                ],
                submit: function (values) {
                    var data = new FormData();
                    data.append('name', values.name);
                    if (values.slug) { data.append('slug', values.slug); }
                    if (values.parent_id) { data.append('parent_id', values.parent_id); }
                    if (values.description) { data.append('description', values.description); }
                    data.append('sort_order', values.sort_order === '' ? '0' : values.sort_order);
                    if (values.is_active) { data.append('is_active', '1'); }
                    if (values.image) { data.append('image', values.image); }

                    var url = isEdit ? category.update_url : storeUrl;
                    if (isEdit) { data.append('_method', 'PUT'); }

                    return AdminApi.request(url, { method: 'POST', body: data }).then(function (json) {
                        AdminUI.toast(json.message, 'success');
                        controller.reload(isEdit ? undefined : 1);
                    });
                }
            });
        }).catch(function (error) {
            AdminUI.toast(error.message, 'error');
        });
    }

    var controller = AdminTable.mount({
        tbody: 'category-rows',
        apiUrl: table.getAttribute('data-api-url'),
        form: 'category-filters',
        pagination: 'category-pagination',
        count: 'category-count',
        countSuffix: 'kategori',
        empty: { title: 'Belum ada kategori', text: 'Buat kategori pertama untuk tokomu.' },
        noMatch: { title: 'Tidak ada kategori yang cocok', text: 'Coba ubah kata kunci atau filternya.' },
        renderRow: rowHtml
    });

    if (!controller) { return; }

    document.getElementById('category-create').addEventListener('click', function () {
        openCategoryModal(null);
    });

    // Aksi baris tanpa reload halaman: edit (modal) & hapus.
    controller.tbody.addEventListener('click', function (event) {
        var editButton = event.target.closest('[data-action="edit"]');
        if (editButton) {
            var payload = null;
            try { payload = JSON.parse(editButton.getAttribute('data-payload')); } catch (e) { payload = null; }
            if (payload) { openCategoryModal(payload); }
            return;
        }

        var button = event.target.closest('[data-action="destroy"]');
        if (!button) { return; }

        AdminUI.confirm({
            title: 'Hapus kategori?',
            message: button.getAttribute('data-name') + ' akan dihapus. Kategori yang masih punya subkategori atau produk tidak dapat dihapus.',
            confirmText: 'Ya, hapus',
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
    });
});
</script>
@endsection
