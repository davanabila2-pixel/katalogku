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
  <a class="btn btn-primary btn-sm" href="{{ route('admin.categories.create', $store) }}">+ Tambah Kategori</a>
</div>
<div class="panel" style="padding:0">
<div class="table-wrap" style="border:none">
<table class="table" id="category-table" data-api-url="{{ route('api.categories.index', $store) }}">
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

    function rowHtml(c) {
        var status = c.is_active
            ? '<span class="badge badge-success">Aktif</span>'
            : '<span class="badge badge-neutral">Nonaktif</span>';

        return '<tr>'
            + '<td><strong>' + esc(c.name) + '</strong><br><span class="category-hierarchy">' + esc(c.slug) + '</span></td>'
            + '<td>' + esc(c.parent || '—') + '</td>'
            + '<td>' + esc(c.children_count) + '</td>'
            + '<td>' + esc(c.products_count) + '</td>'
            + '<td>' + esc(c.sort_order) + '</td>'
            + '<td>' + status + '</td>'
            + '<td style="white-space:nowrap"><a href="' + esc(c.edit_url) + '">Edit</a> · '
            + '<button type="button" data-action="destroy" data-url="' + esc(c.destroy_url) + '" data-name="' + esc(c.name) + '" title="Hapus kategori" style="background:none;border:none;padding:0;color:var(--critical);cursor:pointer;font-size:14px">Hapus</button></td>'
            + '</tr>';
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

    // Aksi hapus tanpa reload halaman.
    controller.tbody.addEventListener('click', function (event) {
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
