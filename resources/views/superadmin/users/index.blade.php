@extends('layouts.dashboard')
@section('title', 'Pengguna')
@section('subtitle', 'Kelola akun sesuai kewenangan (role tidak bisa dinaikkan sendiri)')
@section('content')
<div class="toolbar">
  <form method="GET" id="user-filters" style="display:flex;gap:8px">
    <input class="search-pill" type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama/email…" autocomplete="off">
    <select class="select" style="width:140px" name="role"><option value="">Semua role</option><option value="superadmin" {{ request('role')==='superadmin'?'selected':'' }}>Superadmin</option><option value="admin" {{ request('role')==='admin'?'selected':'' }}>Admin</option></select>
  </form>
  <span class="spacer"></span>
  <span class="category-hierarchy" id="user-count" style="margin-right:8px"></span>
</div>
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
            + '<td><a href="' + esc(u.edit_url) + '">Kelola</a></td>'
            + '</tr>';
    }

    AdminTable.mount({
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
});
</script>
@endsection
