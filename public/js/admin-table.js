/**
 * Tabel admin berbasis API JSON (tanpa reload halaman).
 *
 * Menangani semua perilaku umum: fetch data, loading skeleton, state kosong,
 * error + retry, pagination, sinkronisasi URL, dan debounce pencarian.
 * Halaman cukup menyediakan markup kerangka + fungsi renderRow(item).
 *
 * Contoh:
 *   var table = AdminTable.mount({
 *     tbody: 'product-rows',
 *     apiUrl: '/api/v1/stores/1/products',
 *     form: 'product-filters',
 *     pagination: 'product-pagination',
 *     count: 'product-count',
 *     countSuffix: 'produk',
 *     empty: { title: 'Belum ada produk', text: 'Tambahkan produk pertama.' },
 *     noMatch: { title: 'Tidak ada yang cocok', text: 'Coba ubah filternya.' },
 *     renderRow: function (item) { return '<tr>...</tr>'; }
 *   });
 *   table.reload();          // muat ulang halaman saat ini
 *   table.reload(1);         // kembali ke halaman 1
 */
window.AdminTable = (function () {
    'use strict';

    function esc(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    }

    function statusBadge(status) {
        var cls = status === 'active' ? 'badge-success' : (status === 'draft' ? 'badge-warning' : 'badge-neutral');
        return '<span class="badge ' + cls + '">' + esc(status) + '</span>';
    }

    function mount(options) {
        var tbody = document.getElementById(options.tbody);
        if (!tbody) { return null; }

        var form = options.form ? document.getElementById(options.form) : null;
        var paginationBox = options.pagination ? document.getElementById(options.pagination) : null;
        var countEl = options.count ? document.getElementById(options.count) : null;

        var currentPage = 1;
        var requestSeq = 0;
        var debounceTimer = null;

        var loadingRow = '<tr><td colspan="99"><div class="table-loading"><span class="skeleton" style="width:140px"></span> Memuat data…</div></td></tr>';

        function hasActiveFilter() {
            if (!form) { return false; }
            var active = false;
            new FormData(form).forEach(function (value) {
                if (value !== '' && value !== null && String(value).trim() !== '') { active = true; }
            });
            return active;
        }

        function emptyStateRow() {
            var state = hasActiveFilter() && options.noMatch ? options.noMatch : options.empty;
            if (!state) { return ''; }

            return '<tr><td colspan="99"><div class="empty"><h3>' + esc(state.title) + '</h3>'
                + (state.text ? '<p>' + esc(state.text) + '</p>' : '') + '</div></td></tr>';
        }

        function collectFilters() {
            var filters = {};
            if (form) {
                new FormData(form).forEach(function (value, key) { filters[key] = value; });
            }
            return filters;
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

        function renderPagination(meta) {
            if (!paginationBox) { return; }

            if (!meta || !meta.total || meta.total === 0) {
                paginationBox.innerHTML = '';
                return;
            }

            var from = meta.from || (meta.total > 0 ? 1 : 0);
            var to = meta.to || meta.total;
            var current = meta.current_page || 1;
            var last = meta.last_page || 1;
            var total = meta.total || 0;
            var suffix = options.countSuffix || 'data';

            var prevBtn = current > 1
                ? '<button type="button" class="pagination-nav-btn" data-page="' + (current - 1) + '">&lt; PREV PAGE</button>'
                : '<button type="button" class="pagination-nav-btn is-disabled" disabled aria-disabled="true">&lt; PREV PAGE</button>';

            var nextBtn = current < last
                ? '<button type="button" class="pagination-nav-btn" data-page="' + (current + 1) + '">NEXT PAGE &gt;</button>'
                : '<button type="button" class="pagination-nav-btn is-disabled" disabled aria-disabled="true">NEXT PAGE &gt;</button>';

            paginationBox.innerHTML =
                '<div class="pagination-container">'
                + '<div class="pagination-info">Showing ' + from + ' - ' + to + ' of ' + total + ' ' + esc(suffix) + '</div>'
                + '<div class="pagination-controls">'
                + prevBtn
                + '<div class="pagination-page-box">'
                + '<input type="number" class="pagination-page-input" value="' + current + '" min="1" max="' + last + '" data-current="' + current + '" data-max="' + last + '" aria-label="Nomor halaman">'
                + '<span class="pagination-total-label">of ' + last + ' pages</span>'
                + '</div>'
                + nextBtn
                + '</div>'
                + '</div>';
        }

        function reload(page) {
            currentPage = page || currentPage || 1;

            var filters = collectFilters();
            var params = Object.assign({}, filters);
            if (currentPage > 1) { params.page = currentPage; }

            syncUrl(filters, currentPage);

            var seq = ++requestSeq;
            tbody.innerHTML = loadingRow;

            AdminApi.get(options.apiUrl, params).then(function (json) {
                if (seq !== requestSeq) { return; } // abaikan respons yang sudah basi

                if (!json.data || !json.data.length) {
                    tbody.innerHTML = emptyStateRow();
                    if (countEl) { countEl.textContent = ''; }
                    renderPagination(null);
                } else {
                    tbody.innerHTML = json.data.map(options.renderRow).join('');

                    if (countEl && json.meta) {
                        countEl.textContent = 'Menampilkan ' + json.meta.from + '–' + json.meta.to + ' dari ' + json.meta.total + ' ' + (options.countSuffix || 'data');
                    }
                    renderPagination(json.meta);
                }

                if (options.onLoaded) { options.onLoaded(json); }
            }).catch(function (error) {
                if (seq !== requestSeq) { return; }

                tbody.innerHTML = '<tr><td colspan="99"><div class="alert alert-error" style="margin:16px">Gagal memuat data: '
                    + esc(error.message) + ' <button type="button" class="btn btn-secondary btn-sm" data-retry style="margin-left:8px">Coba lagi</button></div></td></tr>';
                renderPagination(null);
            });
        }

        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                reload(1);
            });
            form.querySelectorAll('select').forEach(function (select) {
                select.addEventListener('change', function () { reload(1); });
            });
            form.querySelectorAll('input[type="search"]').forEach(function (input) {
                input.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function () { reload(1); }, 300);
                });
            });
        }

        if (paginationBox) {
            paginationBox.addEventListener('click', function (event) {
                var btn = event.target.closest('[data-page]');
                if (!btn || btn.disabled || btn.classList.contains('is-disabled')) { return; }
                event.preventDefault();
                var target = parseInt(btn.getAttribute('data-page'), 10);
                if (target && target !== currentPage) {
                    reload(target);
                }
            });

            function onPageInput(input) {
                var max = parseInt(input.getAttribute('data-max') || input.getAttribute('max'), 10) || 1;
                var current = parseInt(input.getAttribute('data-current'), 10) || currentPage;
                var target = parseInt(input.value, 10);
                if (isNaN(target) || target < 1) { target = 1; }
                if (target > max) { target = max; }
                input.value = target;
                if (target !== current) {
                    reload(target);
                }
            }

            paginationBox.addEventListener('change', function (event) {
                if (event.target.classList.contains('pagination-page-input')) {
                    onPageInput(event.target);
                }
            });

            paginationBox.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' && event.target.classList.contains('pagination-page-input')) {
                    event.preventDefault();
                    onPageInput(event.target);
                }
            });
        }

        tbody.addEventListener('click', function (event) {
            if (event.target.closest('[data-retry]')) { reload(currentPage); }
        });

        var initialPage = parseInt(new URLSearchParams(window.location.search).get('page') || '1', 10) || 1;
        reload(initialPage);

        return {
            reload: reload,
            tbody: tbody,
            form: form,
            page: function () { return currentPage; }
        };
    }

    return {
        esc: esc,
        statusBadge: statusBadge,
        mount: mount
    };
})();
