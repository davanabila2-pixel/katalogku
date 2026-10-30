@extends('layouts.dashboard')
@section('title', 'Kategori')
@section('subtitle', $store->name)
@section('content')
<div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:flex-start;gap:14px">
  <div style="font-size:22px;line-height:1;margin-top:2px">💡</div>
  <div style="font-size:13px;line-height:1.5;color:#475569">
    <strong style="color:#0f172a">Kelompok Kategori Induk &amp; Subkategori:</strong><br>
    • <strong>Kategori Utama (Induk)</strong>: Kelompok produk tingkat atas (contoh: <em>Fashion &amp; Busana</em>, <em>Elektronik &amp; Gadget</em>, <em>Makanan &amp; Minuman</em>).<br>
    • <strong>Subkategori</strong>: Cabang spesifik di bawah kategori induk (contoh: <em>Pakaian Pria</em> di dalam <em>Fashion &amp; Busana</em>). Klik tombol hijau <strong>"+ Subkategori"</strong> pada baris induk untuk langsung menambahkan cabang di bawahnya.
  </div>
</div>

<div class="toolbar">
  <div class="toolbar-left">
    <select class="select" id="cat-status" style="width:140px">
      <option value="">Semua status</option>
      <option value="active">Aktif</option>
      <option value="inactive">Nonaktif</option>
    </select>
    <select class="select" id="cat-parent-filter" style="max-width:210px">
      <option value="">Semua Kategori Induk</option>
    </select>
    <div id="tree-toggle-actions" style="display:flex;gap:6px">
      <button type="button" class="btn btn-ghost btn-sm" id="btn-expand-all" style="font-size:12px;padding:0 10px;height:40px;border:1px solid #ced0d4;background:#fff" title="Buka semua subkategori">▾ Buka Semua</button>
      <button type="button" class="btn btn-ghost btn-sm" id="btn-collapse-all" style="font-size:12px;padding:0 10px;height:40px;border:1px solid #ced0d4;background:#fff" title="Tutup semua subkategori">▸ Tutup Semua</button>
    </div>
  </div>

  <div class="toolbar-right">
    <x-search-input id="cat-search" placeholder="Cari nama kategori…" width="250px" />
    <button class="btn btn-primary btn-sm" type="button" id="category-create">+ Tambah Kategori Baru</button>
  </div>
</div>

{{-- Daftar kategori dikelompokkan berdasarkan induk (tree hierarchy) --}}
<div id="category-tree-container" data-tree-url="{{ route('api.categories.tree', $store) }}" data-options-url="{{ route('api.categories.options', $store) }}" data-store-url="{{ route('api.categories.store', $store) }}">
  <div id="tree-loading" style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:32px;text-align:center;color:#64748b">
    <div class="table-loading" style="justify-content:center"><span class="skeleton" style="width:160px"></span> Memuat kelompok kategori…</div>
  </div>
  <div id="tree-list" style="display:none"></div>
  <div id="tree-empty" style="display:none;background:#fff;border:1px dashed #cbd5e1;border-radius:10px;padding:48px 20px;text-align:center;color:#64748b">
    <div style="font-size:36px;margin-bottom:8px">🗂️</div>
    <h3 style="font-size:16px;font-weight:700;color:#0f172a;margin:0 0 6px">Tidak ada kategori yang cocok</h3>
    <p style="font-size:13px;margin:0 0 16px">Coba ubah kata kunci pencarian atau filter status.</p>
    <button type="button" class="btn btn-sm btn-ghost" id="btn-reset-filters" style="border:1px solid #cbd5e1">Reset Filter</button>
  </div>
</div>

<noscript><div class="alert alert-error" style="margin-top:16px">Daftar kategori ini dimuat dengan JavaScript. Aktifkan JavaScript untuk melihat datanya.</div></noscript>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var treeContainer = document.getElementById('category-tree-container');
    var treeUrl = treeContainer.getAttribute('data-tree-url');
    var optionsUrl = treeContainer.getAttribute('data-options-url');
    var storeUrl = treeContainer.getAttribute('data-store-url');
    var esc = (window.AdminTable && AdminTable.esc) ? AdminTable.esc : function (v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    };

    var searchInput = document.getElementById('cat-search');
    var statusSelect = document.getElementById('cat-status');
    var parentFilterSelect = document.getElementById('cat-parent-filter');
    var treeListEl = document.getElementById('tree-list');
    var treeLoadingEl = document.getElementById('tree-loading');
    var treeEmptyEl = document.getElementById('tree-empty');

    var treeData = [];
    var treeCollapsedState = {}; // id -> boolean
    var searchTimer = null;

    // Expand / Collapse All
    document.getElementById('btn-expand-all').addEventListener('click', function () {
        treeCollapsedState = {};
        renderTree();
    });

    document.getElementById('btn-collapse-all').addEventListener('click', function () {
        treeData.forEach(function (root) {
            treeCollapsedState[root.id] = true;
        });
        renderTree();
    });

    document.getElementById('btn-reset-filters').addEventListener('click', function () {
        searchInput.value = '';
        statusSelect.value = '';
        parentFilterSelect.value = '';
        loadTree();
    });

    // Populate Parent Dropdown in Toolbar
    function updateParentDropdown(roots) {
        var currentVal = parentFilterSelect.value;
        var html = '<option value="">Semua Kategori Induk</option>';
        roots.forEach(function (r) {
            html += '<option value="' + r.id + '"' + (String(currentVal) === String(r.id) ? ' selected' : '') + '>' + esc(r.name) + '</option>';
        });
        parentFilterSelect.innerHTML = html;
    }

    // Load Tree Data from API
    function loadTree() {
        treeLoadingEl.style.display = 'block';
        treeListEl.style.display = 'none';
        treeEmptyEl.style.display = 'none';

        var params = {};
        if (searchInput.value.trim()) { params.q = searchInput.value.trim(); }
        if (statusSelect.value) { params.status = statusSelect.value; }

        AdminApi.get(treeUrl, params).then(function (json) {
            treeData = json.data || [];
            var orphans = json.orphans || [];
            var totalCategories = json.meta ? json.meta.total_categories : 0;
            var totalRoots = json.meta ? json.meta.total_roots : treeData.length;

            // Update parent dropdown if roots changed and dropdown has only 1 option
            if (parentFilterSelect.options.length <= 1) {
                updateParentDropdown(treeData);
            }

            treeLoadingEl.style.display = 'none';
            renderTree(orphans);
        }).catch(function (error) {
            treeLoadingEl.style.display = 'none';
            AdminUI.toast('Gagal memuat kategori: ' + error.message, 'error');
        });
    }

    function renderTree(orphans) {
        orphans = orphans || [];
        var selectedParentId = parentFilterSelect.value;
        var filteredRoots = treeData;

        if (selectedParentId) {
            filteredRoots = treeData.filter(function (r) {
                return String(r.id) === String(selectedParentId);
            });
        }

        if (filteredRoots.length === 0 && orphans.length === 0) {
            treeListEl.style.display = 'none';
            treeEmptyEl.style.display = 'block';
            return;
        }

        treeEmptyEl.style.display = 'none';
        treeListEl.style.display = 'block';

        var html = '';
        filteredRoots.forEach(function (root) {
            html += renderGroupCard(root);
        });

        if (orphans.length > 0) {
            html += renderOrphansCard(orphans);
        }

        treeListEl.innerHTML = html;
    }

    function renderGroupCard(root) {
        var isCollapsed = !!treeCollapsedState[root.id];
        var chevron = isCollapsed ? '▶' : '▼';
        var totalDesc = root.total_descendants_count || 0;
        var totalProds = root.total_products_count || root.products_count || 0;

        var statusBadge = root.is_active
            ? '<span class="badge badge-success" style="font-size:11px">Aktif</span>'
            : '<span class="badge badge-neutral" style="font-size:11px">Nonaktif</span>';

        var rootPayload = esc(JSON.stringify({
            id: root.id,
            name: root.name,
            slug: root.slug,
            description: root.description || '',
            parent_id: '',
            sort_order: root.sort_order,
            is_active: !!root.is_active,
            update_url: root.update_url
        }));

        var card = '<div class="category-group-card" data-root-id="' + root.id + '" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:14px;overflow:hidden;box-shadow:0 1px 3px rgba(15,23,42,0.04)">';

        // Header Induk Bar
        card += '<div class="category-group-header" data-root-id="' + root.id + '" style="padding:14px 18px;display:flex;align-items:center;justify-content:space-between;background:#f8fafc;border-bottom:' + (isCollapsed ? 'none' : '1px solid #e2e8f0') + ';gap:12px;transition:background 0.15s">';
        
        // Left info (Clickable to toggle expand/collapse)
        card += '<div data-action="toggle-group" data-root-id="' + root.id + '" style="display:flex;align-items:center;gap:12px;flex:1;min-width:0;cursor:pointer;user-select:none">';
        card += '<span style="font-size:13px;color:#64748b;width:16px;text-align:center;transition:transform 0.15s">' + chevron + '</span>';
        card += '<span style="font-size:18px;line-height:1">📁</span>';
        card += '<div style="min-width:0">';
        card += '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">';
        card += '<strong style="font-size:15px;color:#0f172a">' + esc(root.name) + '</strong>';
        card += '<span class="category-hierarchy" style="font-size:12px">/' + esc(root.slug) + '</span>';
        card += '<span class="badge badge-neutral" style="font-size:11px;font-weight:600">Kategori Utama (Induk)</span>';
        if (totalDesc > 0) {
            card += '<span class="badge" style="background:#e0f2fe;color:#0369a1;font-size:11px;font-weight:600">' + totalDesc + ' Subkategori</span>';
        } else {
            card += '<span class="badge" style="background:#f1f5f9;color:#64748b;font-size:11px;font-weight:500">Belum ada subkategori</span>';
        }
        card += '</div>';
        card += '</div>';
        card += '</div>';

        // Right side info & actions
        card += '<div style="display:flex;align-items:center;gap:14px;flex-shrink:0">';
        card += '<div style="font-size:12px;color:#64748b;text-align:right"><strong>' + totalProds + '</strong> produk</div>';
        card += '<div>' + statusBadge + '</div>';
        card += '<div style="display:flex;align-items:center;gap:6px">';
        card += '<button type="button" data-action="add-sub" data-parent-id="' + root.id + '" data-parent-name="' + esc(root.name) + '" class="btn btn-sm" style="background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;font-size:12px;padding:4px 10px;font-weight:600;border-radius:6px;cursor:pointer">+ Subkategori</button>';
        card += '<button type="button" data-action="edit" data-payload="' + rootPayload + '" class="btn btn-sm btn-ghost" style="padding:4px 8px;font-size:12px;font-weight:600;color:var(--primary);cursor:pointer">Edit</button>';
        card += '<button type="button" data-action="destroy" data-url="' + esc(root.destroy_url) + '" data-name="' + esc(root.name) + '" class="btn btn-sm btn-ghost" style="padding:4px 8px;font-size:12px;font-weight:600;color:var(--critical);cursor:pointer">Hapus</button>';
        card += '</div>';
        card += '</div>';

        card += '</div>'; // End Header

        // Body Subkategori Table
        if (!isCollapsed) {
            card += '<div class="category-group-body">';
            if (root.children && root.children.length > 0) {
                card += '<div class="table-wrap" style="border:none;margin:0">';
                card += '<table class="table" style="margin:0;border:none">';
                card += '<thead><tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:11px;text-transform:uppercase;color:#64748b;letter-spacing:0.04em">';
                card += '<th style="padding:10px 18px;min-width:260px">Subkategori</th>';
                card += '<th style="padding:10px 14px">Tingkat Hirarki</th>';
                card += '<th style="padding:10px 14px">Produk</th>';
                card += '<th style="padding:10px 14px">Urutan</th>';
                card += '<th style="padding:10px 14px">Status</th>';
                card += '<th style="padding:10px 18px;text-align:right">Aksi</th>';
                card += '</tr></thead>';
                card += '<tbody>';

                root.children.forEach(function (child) {
                    card += renderChildRow(child, 2, root.name);
                    if (child.children && child.children.length > 0) {
                        child.children.forEach(function (grandChild) {
                            card += renderChildRow(grandChild, 3, child.name);
                        });
                    }
                });

                card += '</tbody></table></div>';
            } else {
                card += '<div style="padding:24px 20px;text-align:center;background:#fff;color:#64748b;font-size:13px">';
                card += '<span>Belum ada subkategori di bawah <strong>' + esc(root.name) + '</strong>.</span> ';
                card += '<button type="button" data-action="add-sub" data-parent-id="' + root.id + '" data-parent-name="' + esc(root.name) + '" style="background:none;border:none;color:#047857;font-weight:600;cursor:pointer;text-decoration:underline">+ Tambah Subkategori Pertama</button>';
                card += '</div>';
            }
            card += '</div>';
        }

        card += '</div>'; // End Card
        return card;
    }

    function renderChildRow(c, depth, parentName) {
        var status = c.is_active
            ? '<span class="badge badge-success" style="font-size:11px">Aktif</span>'
            : '<span class="badge badge-neutral" style="font-size:11px">Nonaktif</span>';

        var indentPadding = depth === 3 ? '44px' : '18px';
        var branchIcon = depth === 3
            ? '<span style="color:#94a3b8;font-weight:700;font-size:13px;line-height:1">↳ ↳</span>'
            : '<span style="color:#0284c7;font-weight:700;font-size:15px;line-height:1">↳</span>';

        var levelBadge = depth === 3
            ? '<span class="badge" style="background:#f8fafc;border:1px solid #e2e8f0;color:#64748b;font-size:11px">Subkategori (Lvl 3)</span>'
            : '<span class="badge" style="background:#e0f2fe;color:#0369a1;font-size:11px">Subkategori (Lvl 2)</span>';

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

        var addSubBtn = (depth < 3)
            ? '<button type="button" data-action="add-sub" data-parent-id="' + c.id + '" data-parent-name="' + esc(c.name) + '" title="Tambah cabang di bawah ' + esc(c.name) + '" style="background:none;border:none;padding:0;color:#16a34a;font-weight:600;cursor:pointer;font-size:13px">+ Sub</button> · '
            : '';

        var row = '<tr style="border-bottom:1px solid #f1f5f9;background:' + (depth === 3 ? '#fafafa' : '#ffffff') + '">';
        row += '<td style="padding:10px 18px 10px ' + indentPadding + '">';
        row += '<div style="display:flex;align-items:flex-start;gap:8px">';
        row += branchIcon;
        row += '<div>';
        row += '<strong style="color:#1e293b;font-size:13px">' + esc(c.name) + '</strong>';
        row += '<br><span class="category-hierarchy" style="font-size:12px">/' + esc(c.slug) + '</span>';
        row += '</div>';
        row += '</div>';
        row += '</td>';

        row += '<td style="padding:10px 14px">';
        row += levelBadge;
        row += '<div style="font-size:11px;color:#64748b;margin-top:2px">Induk: <strong>' + esc(parentName) + '</strong></div>';
        row += '</td>';

        row += '<td style="padding:10px 14px"><span style="font-weight:600;color:#0f172a">' + esc(c.products_count) + '</span> <span style="font-size:12px;color:#64748b">produk</span></td>';
        row += '<td style="padding:10px 14px">' + esc(c.sort_order) + '</td>';
        row += '<td style="padding:10px 14px">' + status + '</td>';

        row += '<td style="padding:10px 18px;white-space:nowrap;text-align:right">';
        row += addSubBtn;
        row += '<button type="button" data-action="edit" data-payload="' + payload + '" title="Edit kategori" style="background:none;border:none;padding:0;color:var(--primary);cursor:pointer;font-size:13px;font-weight:600">Edit</button> · ';
        row += '<button type="button" data-action="destroy" data-url="' + esc(c.destroy_url) + '" data-name="' + esc(c.name) + '" title="Hapus kategori" style="background:none;border:none;padding:0;color:var(--critical);cursor:pointer;font-size:13px;font-weight:600">Hapus</button>';
        row += '</td>';
        row += '</tr>';

        return row;
    }

    function renderOrphansCard(orphans) {
        var card = '<div class="category-group-card" style="background:#ffffff;border:1px solid #fecaca;border-radius:10px;margin-bottom:14px;overflow:hidden">';
        card += '<div style="padding:14px 18px;display:flex;align-items:center;background:#fff5f5;border-bottom:1px solid #fecaca;gap:12px">';
        card += '<span style="font-size:18px">⚠️</span>';
        card += '<div><strong style="color:#991b1b">Kategori Tanpa Induk Terdaftar</strong><div style="font-size:12px;color:#b91c1c">Kategori berikut memiliki parent_id yang sudah tidak aktif atau terhapus. Edit untuk menetapkan induknya.</div></div>';
        card += '</div>';
        card += '<div class="table-wrap" style="border:none;margin:0"><table class="table" style="margin:0;border:none"><tbody>';
        orphans.forEach(function (c) {
            card += renderChildRow(c, 2, 'Tidak Ditemukan');
        });
        card += '</tbody></table></div></div>';
        return card;
    }

    // Event delegation on treeListEl
    treeListEl.addEventListener('click', function (event) {
        var addSubBtn = event.target.closest('[data-action="add-sub"]');
        if (addSubBtn) {
            var parentId = addSubBtn.getAttribute('data-parent-id');
            var parentName = addSubBtn.getAttribute('data-parent-name');
            openCategoryModal(null, parentId, parentName);
            return;
        }

        var editBtn = event.target.closest('[data-action="edit"]');
        if (editBtn) {
            var payload = null;
            try { payload = JSON.parse(editBtn.getAttribute('data-payload')); } catch (e) { payload = null; }
            if (payload) { openCategoryModal(payload); }
            return;
        }

        var destroyBtn = event.target.closest('[data-action="destroy"]');
        if (destroyBtn) {
            handleDestroy(destroyBtn);
            return;
        }

        var headerToggle = event.target.closest('[data-action="toggle-group"]');
        if (headerToggle) {
            var rootId = headerToggle.getAttribute('data-root-id');
            treeCollapsedState[rootId] = !treeCollapsedState[rootId];
            renderTree();
            return;
        }
    });

    // Handle Category Modal (Form)
    function openCategoryModal(category, defaultParentId, defaultParentName) {
        var isEdit = !!category;
        var params = isEdit ? { exclude: category.id, all_parents: 1 } : { all_parents: 1 };

        function buildOptions(apiItems) {
            var opts = [{ value: '', label: '— Kategori Utama (Tingkat Teratas / Induk) —' }];
            if (treeData && treeData.length > 0) {
                treeData.forEach(function (root) {
                    if (!isEdit || String(root.id) !== String(category.id)) {
                        opts.push({ value: String(root.id), label: '📁 ' + root.name + ' (Kategori Utama)' });
                        if (root.children && root.children.length > 0) {
                            root.children.forEach(function (child) {
                                if (!isEdit || String(child.id) !== String(category.id)) {
                                    opts.push({ value: String(child.id), label: '   ↳ ' + child.name + ' (di dalam ' + root.name + ')' });
                                }
                            });
                        }
                    }
                });
            } else if (apiItems && apiItems.length > 0) {
                apiItems.forEach(function (p) {
                    var prefix = p.parent_id ? '   ↳ Subkategori: ' : '📁 Kategori Utama: ';
                    opts.push({ value: String(p.id), label: prefix + p.name });
                });
            }
            return opts;
        }

        AdminApi.get(optionsUrl, params).then(function (json) {
            var parentOptions = buildOptions(json.data || []);
            var selectedParent = isEdit ? String(category.parent_id || '') : String(defaultParentId || '');

            var modalTitle = isEdit 
                ? 'Edit Kategori' 
                : (defaultParentName ? 'Tambah Subkategori untuk "' + defaultParentName + '"' : 'Tambah Kategori Baru');

            return AdminUI.formModal({
                title: modalTitle,
                width: 'lg',
                submitText: isEdit ? 'Simpan Perubahan' : 'Simpan Kategori',
                fields: [
                    { name: 'name', label: 'Nama Kategori', required: true, value: isEdit ? category.name : '', placeholder: 'cth: Pakaian / Kemeja Pria' },
                    { name: 'parent_id', label: 'Kategori Induk / Tingkat Hirarki', type: 'select', options: parentOptions, value: selectedParent, help: 'Pilih "Kategori Utama" jika ini kelompok besar. Pilih kategori induk jika ini cabang/subkategori.' },
                    { name: 'slug', label: 'Slug URL (opsional)', value: isEdit ? category.slug : '', help: 'Kosongkan agar dibuat otomatis dari nama kategori.' },
                    { name: 'description', label: 'Deskripsi Kategori', type: 'textarea', value: isEdit ? category.description : '' },
                    { name: 'sort_order', label: 'Urutan Tampil', type: 'number', min: 0, value: isEdit ? category.sort_order : 0 },
                    { name: 'is_active', label: 'Aktif (Tampil di Katalog)', type: 'checkbox', checked: isEdit ? category.is_active : true, help: 'Kategori nonaktif tidak akan ditampilkan kepada pembeli di katalog publik.' },
                    { name: 'image', label: 'Foto Sampul (opsional)', type: 'file', help: 'PNG/JPG/WebP maks. 5 MB — disimpan otomatis di Google Drive toko.' }
                ],
                submit: function (values) {
                    var data = new FormData();
                    data.append('name', values.name);
                    if (values.slug) { data.append('slug', values.slug); }
                    data.append('parent_id', values.parent_id || '');
                    if (values.description) { data.append('description', values.description); }
                    data.append('sort_order', values.sort_order === '' ? '0' : values.sort_order);
                    if (values.is_active) { data.append('is_active', '1'); }
                    if (values.image) { data.append('image', values.image); }

                    var url = isEdit ? category.update_url : storeUrl;
                    if (isEdit) { data.append('_method', 'PUT'); }

                    return AdminApi.request(url, { method: 'POST', body: data }).then(function (json) {
                        AdminUI.toast(json.message, 'success');
                        if (values.parent_id) {
                            treeCollapsedState[values.parent_id] = false;
                        }
                        if (currentView === 'tree') {
                            loadTree();
                        } else if (tableController) {
                            tableController.reload(isEdit ? undefined : 1);
                        }
                    });
                }
            });
        }).catch(function (error) {
            AdminUI.toast(error.message, 'error');
        });
    }

    function handleDestroy(button) {
        AdminUI.confirm({
            title: 'Hapus kategori?',
            message: button.getAttribute('data-name') + ' akan dihapus. Kategori yang masih punya subkategori atau produk tidak dapat dihapus.',
            confirmText: 'Ya, hapus',
            danger: true
        }).then(function (ok) {
            if (!ok) { return; }

            AdminApi.request(button.getAttribute('data-url'), { method: 'DELETE' }).then(function (json) {
                AdminUI.toast(json.message, 'success');
                loadTree();
            }).catch(function (error) {
                AdminUI.toast(error.message, 'error');
            });
        });
    }

    // Top primary action
    document.getElementById('category-create').addEventListener('click', function () {
        openCategoryModal(null);
    });

    // Filters event listeners
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(loadTree, 300);
    });

    statusSelect.addEventListener('change', loadTree);

    parentFilterSelect.addEventListener('change', renderTree);

    // Muat daftar kategori saat halaman dibuka
    loadTree();
});
</script>
@endsection
