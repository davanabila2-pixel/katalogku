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
    if (!table || !window.AdminApi) { return; }

    var apiUrl = table.getAttribute('data-api-url');
    var tbody = document.getElementById('product-rows');
    var paginationBox = document.getElementById('product-pagination');
    var countEl = document.getElementById('product-count');
    var form = document.getElementById('product-filters');
    var searchInput = form.querySelector('input[name="q"]');
    var csrfToken = AdminApi.token();
    var currentPage = 1;
    var debounceTimer = null;
    var requestSeq = 0;

    var emptyRow = '<tr><td colspan="6"><div class="empty"><h3>Belum ada produk</h3><p>Tambahkan produk pertama untuk tokomu.</p></div></td></tr>';
    var noMatchRow = '<tr><td colspan="6"><div class="empty"><h3>Tidak ada produk yang cocok</h3><p>Coba ubah kata kunci atau filternya.</p></div></td></tr>';
    var loadingRow = '<tr><td colspan="6"><div class="table-loading"><span class="skeleton" style="width:140px"></span> Memuat data produk…</div></td></tr>';

    function esc(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    }

    function statusBadge(status) {
        var cls = status === 'active' ? 'badge-success' : (status === 'draft' ? 'badge-warning' : 'badge-neutral');
        return '<span class="badge ' + cls + '">' + esc(status) + '</span>';
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

        var actions = '<a href="' + esc(p.edit_url) + '">Edit</a>';
        if (p.barcode_url) {
            actions += ' · <a href="' + esc(p.barcode_url) + '" target="_blank" rel="noopener" title="Buka Label Barcode di Google Drive">Barcode</a>';
        }
        actions += ' · <form method="POST" action="' + esc(p.destroy_url) + '" style="display:inline" onsubmit="return confirm(\'Arsipkan/hapus produk ini?\')">'
            + '<input type="hidden" name="_token" value="' + esc(csrfToken) + '">'
            + '<input type="hidden" name="_method" value="DELETE">'
            + '<button type="submit" style="background:none;border:none;color:var(--critical);cursor:pointer;font-size:14px">Arsip</button></form>';

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

    function renderPagination(meta) {
        if (!meta || meta.last_page <= 1) { paginationBox.innerHTML = ''; return; }

        var from = Math.max(1, meta.current_page - 2);
        var to = Math.min(meta.last_page, meta.current_page + 2);
        var html = '<ul class="pagination">';

        html += meta.current_page > 1
            ? '<li><a href="#" data-page="' + (meta.current_page - 1) + '">‹</a></li>'
            : '<li><span>‹</span></li>';

        for (var i = from; i <= to; i++) {
            html += i === meta.current_page
                ? '<li><span class="current">' + i + '</span></li>'
                : '<li><a href="#" data-page="' + i + '">' + i + '</a></li>';
        }

        html += meta.current_page < meta.last_page
            ? '<li><a href="#" data-page="' + (meta.current_page + 1) + '">›</a></li>'
            : '<li><span>›</span></li>';

        paginationBox.innerHTML = html + '</ul>';
    }

    function syncUrl(filters, page) {
        var url = new URL(window.location.href);
        url.search = '';
        Object.keys(filters).forEach(function (key) {
            if (filters[key]) { url.searchParams.set(key, filters[key]); }
        });
        if (page > 1) { url.searchParams.set('page', page); }
        window.history.replaceState({}, '', url.toString());
    }

    function load(page) {
        currentPage = page || 1;

        var filters = {};
        new FormData(form).forEach(function (value, key) { filters[key] = value; });
        syncUrl(filters, currentPage);

        var params = Object.assign({}, filters);
        if (currentPage > 1) { params.page = currentPage; }

        var seq = ++requestSeq;
        tbody.innerHTML = loadingRow;

        AdminApi.get(apiUrl, params).then(function (json) {
            if (seq !== requestSeq) { return; } // abaikan respons yang sudah basi

            if (!json.data.length) {
                var adaFilter = Object.keys(filters).some(function (key) { return filters[key]; });
                tbody.innerHTML = adaFilter ? noMatchRow : emptyRow;
                countEl.textContent = '';
                renderPagination(null);
                return;
            }

            tbody.innerHTML = json.data.map(rowHtml).join('');
            countEl.textContent = 'Menampilkan ' + json.meta.from + '–' + json.meta.to + ' dari ' + json.meta.total + ' produk';
            renderPagination(json.meta);
        }).catch(function (error) {
            if (seq !== requestSeq) { return; }
            tbody.innerHTML = '<tr><td colspan="6"><div class="alert alert-error" style="margin:16px">Gagal memuat data produk: '
                + esc(error.message) + ' <button type="button" class="btn btn-secondary btn-sm" data-retry style="margin-left:8px">Coba lagi</button></div></td></tr>';
            renderPagination(null);
        });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        load(1);
    });

    form.querySelectorAll('select').forEach(function (select) {
        select.addEventListener('change', function () { load(1); });
    });

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () { load(1); }, 300);
    });

    paginationBox.addEventListener('click', function (event) {
        var link = event.target.closest('a[data-page]');
        if (!link) { return; }
        event.preventDefault();
        load(parseInt(link.getAttribute('data-page'), 10) || 1);
    });

    tbody.addEventListener('click', function (event) {
        if (event.target.closest('[data-retry]')) { load(currentPage); }
    });

    var initialPage = parseInt(new URLSearchParams(window.location.search).get('page') || '1', 10) || 1;
    load(initialPage);
});
</script>
@endsection
