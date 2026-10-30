@extends('layouts.dashboard')
@section('title', 'Pengguna')
@section('subtitle', 'Kelola akun sesuai kewenangan (role tidak bisa dinaikkan sendiri)')
@section('content')
<form method="GET" id="user-filters" class="toolbar">
  <div class="toolbar-left">
    <select class="select" style="width:140px" name="role"><option value="">Semua role</option><option value="superadmin" {{ request('role')==='superadmin'?'selected':'' }}>Superadmin</option><option value="admin" {{ request('role')==='admin'?'selected':'' }}>Admin</option></select>
  </div>
  <div class="toolbar-right">
    <x-search-input placeholder="Cari nama/email…" width="250px" />
    <span class="category-hierarchy" id="user-count"></span>
  </div>
</form>
<div class="panel" style="padding:0"><div class="table-wrap" style="border:none">
<table class="table" id="user-table" data-api-url="{{ route('api.superadmin.users.index') }}">
<thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Status</th><th>Toko</th><th>Aksi</th></tr></thead>
<tbody id="user-rows">
<tr><td colspan="6"><div class="table-loading"><span class="skeleton" style="width:140px"></span> Memuat data pengguna…</div></td></tr>
</tbody>
</table>
</div></div>
<div id="user-pagination"></div>
<noscript><div class="alert alert-error" style="margin-top:16px">Daftar pengguna ini dimuat dengan JavaScript. Aktifkan JavaScript untuk melihat datanya.</div></noscript>
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    var table = document.getElementById('user-table');
    if (!table || !window.AdminTable) { return; }

    var esc = AdminTable.esc;

    function rowHtml(u) {
        var roleBadge = u.role === 'superadmin'
            ? '<span class="badge badge-warning">' + esc(u.role) + '</span>'
            : '<span class="badge badge-neutral">' + esc(u.role) + '</span>';

        var activeBadge = u.is_active
            ? '<span class="badge badge-success">Aktif</span>'
            : '<span class="badge badge-critical">Nonaktif</span>';

        return '<tr>'
            + '<td>' + esc(u.name) + '</td>'
            + '<td>' + esc(u.email) + '</td>'
            + '<td>' + roleBadge + '</td>'
            + '<td>' + activeBadge + '</td>'
            + '<td>' + esc(u.stores || '—') + '</td>'
            + '<td><button type="button" data-action="edit" data-form-data-url="' + esc(u.form_data_url) + '" data-name="' + esc(u.name) + '" title="Kelola pengguna" style="background:none;border:none;padding:0;color:var(--primary);cursor:pointer;font-size:14px">Kelola</button></td>'
            + '</tr>';
    }

    function openUserModal(formDataUrl) {
        AdminApi.get(formDataUrl).then(function (data) {
            var u = data.user;
            var stores = data.stores || [];
            var checkedIds = u.store_ids || [];

            var storesHtml = '<div class="field"><label>Toko yang dikelola (admin)</label>';
            if (!stores.length) {
                storesHtml += '<small class="modal-help">Belum ada toko untuk dikelola.</small>';
            }
            stores.forEach(function (s) {
                storesHtml += '<label class="modal-check" style="margin:4px 0"><input type="checkbox" name="stores[]" value="' + esc(s.id) + '"'
                    + (checkedIds.indexOf(s.id) !== -1 ? ' checked' : '') + '> <span>' + esc(s.name) + '</span></label>';
            });
            storesHtml += '<small class="modal-help">Hanya berlaku untuk role admin — superadmin otomatis punya akses ke semua toko.</small></div>';

            storesHtml += '<div class="field"><label for="um-primary">Admin utama di toko</label>'
                + '<select id="um-primary" class="select" name="primary_store"><option value="">— Tidak ada —</option>';
            stores.forEach(function (s) {
                storesHtml += '<option value="' + esc(s.id) + '"' + (String(u.primary_store_id) === String(s.id) ? ' selected' : '') + '>' + esc(s.name) + '</option>';
            });
            storesHtml += '</select><small class="modal-help">Toko ini yang dipakai admin sebagai konteks utama saat login.</small></div>';

            return AdminUI.formModal({
                title: 'Kelola ' + u.name,
                width: 'lg',
                submitText: 'Simpan perubahan',
                fields: [
                    { name: 'name', label: 'Nama', required: true, value: u.name },
                    { name: 'role', label: 'Role', type: 'select', options: [{ value: 'admin', label: 'Admin' }, { value: 'superadmin', label: 'Superadmin' }], value: u.role },
                    { name: 'is_active', label: 'Aktif', type: 'checkbox', checked: u.is_active, help: 'Akun nonaktif tidak bisa login.' }
                ],
                html: storesHtml,
                submit: function (values, ui) {
                    var payload = ui.formData();
                    payload.append('_method', 'PUT');

                    return AdminApi.request(u.update_url, { method: 'POST', body: payload }).then(function (json) {
                        AdminUI.toast(json.message, 'success');
                        controller.reload();
                    });
                }
            });
        }).catch(function (error) {
            AdminUI.toast(error.message, 'error');
        });
    }

    var controller = AdminTable.mount({
        tbody: 'user-rows',
        apiUrl: table.getAttribute('data-api-url'),
        form: 'user-filters',
        pagination: 'user-pagination',
        count: 'user-count',
        countSuffix: 'pengguna',
        empty: { title: 'Tidak ada pengguna' },
        noMatch: { title: 'Tidak ada pengguna yang cocok', text: 'Coba ubah kata kunci atau filternya.' },
        renderRow: rowHtml
    });

    if (!controller) { return; }

    controller.tbody.addEventListener('click', function (event) {
        var editBtn = event.target.closest('[data-action="edit"]');
        if (editBtn) {
            openUserModal(editBtn.getAttribute('data-form-data-url'));
        }
    });
});
</script>
@endsection
