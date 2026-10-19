/**
 * Helper UI admin: toast + modal (konfirmasi & form) tanpa dependency.
 *
 * Dipakai bersama AdminApi supaya aksi (hapus, koreksi stok, dsb.)
 * bisa selesai tanpa reload halaman.
 *
 * Contoh:
 *   AdminUI.toast('Produk dihapus.', 'success');
 *   const ok = await AdminUI.confirm({ title: 'Hapus?', danger: true });
 *   const values = await AdminUI.formModal({ title: '...', fields: [...] });
 */
window.AdminUI = (function () {
    'use strict';

    function esc(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    }

    /* ------------------------------ Toast ------------------------------ */

    function toastWrap() {
        var wrap = document.querySelector('.toast-wrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.className = 'toast-wrap';
            document.body.appendChild(wrap);
        }
        return wrap;
    }

    function toast(message, type) {
        var el = document.createElement('div');
        el.className = 'toast toast-' + (type === 'error' ? 'error' : 'success');
        el.setAttribute('role', 'status');
        el.textContent = message;
        toastWrap().appendChild(el);

        setTimeout(function () {
            el.classList.add('toast-out');
            setTimeout(function () { el.remove(); }, 220);
        }, 3600);
    }

    /* ------------------------------ Modal ------------------------------ */

    function openModal(build) {
        return new Promise(function (resolve) {
            var backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop';
            var modal = document.createElement('div');
            modal.className = 'modal';
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-modal', 'true');
            backdrop.appendChild(modal);

            function close(result) {
                backdrop.remove();
                document.removeEventListener('keydown', onKey);
                resolve(result);
            }

            function onKey(event) {
                if (event.key === 'Escape') {
                    close(null);
                }
            }

            backdrop.addEventListener('mousedown', function (event) {
                if (event.target === backdrop) {
                    close(null);
                }
            });
            document.addEventListener('keydown', onKey);

            build(modal, close);
            document.body.appendChild(backdrop);

            var focusable = modal.querySelector('input, textarea, select, [data-confirm], button');
            if (focusable) {
                focusable.focus();
            }
        });
    }

    function buttons(cancelText, submitText, danger, submitType) {
        return '<div class="modal-actions">'
            + '<button type="button" class="btn btn-secondary" data-cancel>' + esc(cancelText || 'Batal') + '</button>'
            + '<button type="' + (submitType || 'button') + '" class="btn ' + (danger ? 'btn-danger' : 'btn-primary') + '" data-confirm>' + esc(submitText || 'Ya, lanjutkan') + '</button>'
            + '</div>';
    }

    /**
     * Modal konfirmasi. Mengembalikan Promise<boolean>.
     */
    function confirm(options) {
        options = options || {};

        return openModal(function (modal, close) {
            modal.innerHTML = '<h3 class="modal-title">' + esc(options.title || 'Konfirmasi') + '</h3>'
                + (options.message ? '<p class="modal-text">' + esc(options.message) + '</p>' : '')
                + buttons(options.cancelText, options.confirmText, options.danger);

            modal.querySelector('[data-cancel]').addEventListener('click', function () { close(false); });
            modal.querySelector('[data-confirm]').addEventListener('click', function () { close(true); });
        });
    }

    /**
     * Modal berisi form sederhana.
     * fields: [{ name, label, type: 'text|number|textarea|select', value, placeholder,
     *            required, min, max, step, help, options: [{ value, label }] }]
     * Mengembalikan Promise<object|null> berisi nilai per field.
     */
    function formModal(options) {
        options = options || {};
        var fields = options.fields || [];

        return openModal(function (modal, close) {
            var html = '<h3 class="modal-title">' + esc(options.title || '') + '</h3>';
            if (options.message) {
                html += '<p class="modal-text">' + esc(options.message) + '</p>';
            }

            html += '<form class="modal-form" novalidate>';
            fields.forEach(function (field) {
                var id = 'modal-field-' + field.name;
                html += '<div class="field"><label for="' + id + '">' + esc(field.label) + '</label>';

                if (field.type === 'textarea') {
                    html += '<textarea id="' + id + '" name="' + esc(field.name) + '" class="input" rows="3"'
                        + (field.placeholder ? ' placeholder="' + esc(field.placeholder) + '"' : '') + '>'
                        + esc(field.value || '') + '</textarea>';
                } else if (field.type === 'select') {
                    html += '<select id="' + id + '" name="' + esc(field.name) + '" class="select">';
                    (field.options || []).forEach(function (opt) {
                        html += '<option value="' + esc(opt.value) + '"'
                            + (String(opt.value) === String(field.value) ? ' selected' : '') + '>'
                            + esc(opt.label) + '</option>';
                    });
                    html += '</select>';
                } else {
                    html += '<input id="' + id + '" name="' + esc(field.name) + '" class="input" type="' + esc(field.type || 'text') + '"'
                        + (field.value !== undefined && field.value !== null ? ' value="' + esc(field.value) + '"' : '')
                        + (field.placeholder ? ' placeholder="' + esc(field.placeholder) + '"' : '')
                        + (field.min !== undefined ? ' min="' + esc(field.min) + '"' : '')
                        + (field.max !== undefined ? ' max="' + esc(field.max) + '"' : '')
                        + (field.step !== undefined ? ' step="' + esc(field.step) + '"' : '') + '>';
                }

                if (field.help) {
                    html += '<small class="modal-help">' + esc(field.help) + '</small>';
                }
                html += '</div>';
            });

            html += '<div class="modal-error" data-error hidden></div>';
            html += buttons(options.cancelText, options.submitText, options.danger, 'submit');
            html += '</form>';

            modal.innerHTML = html;

            var form = modal.querySelector('form');
            var errorBox = modal.querySelector('[data-error]');

            function submitValues(event) {
                if (event) { event.preventDefault(); }

                var values = {};
                var missing = null;

                fields.forEach(function (field) {
                    var el = form.elements[field.name];
                    var raw = el ? el.value : '';
                    if (field.required && String(raw).trim() === '') {
                        missing = missing || (field.label + ' wajib diisi.');
                    }
                    values[field.name] = raw;
                });

                if (missing) {
                    errorBox.textContent = missing;
                    errorBox.hidden = false;
                    return;
                }

                close(values);
            }

            modal.querySelector('[data-cancel]').addEventListener('click', function () { close(null); });
            modal.querySelector('[data-confirm]').addEventListener('click', submitValues);
            form.addEventListener('submit', submitValues);
        });
    }

    return {
        esc: esc,
        toast: toast,
        confirm: confirm,
        formModal: formModal
    };
})();
