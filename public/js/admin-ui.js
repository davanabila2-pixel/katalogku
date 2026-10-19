/**
 * Helper UI admin: toast + modal (konfirmasi & form) tanpa dependency.
 *
 * Dipakai bersama AdminApi supaya aksi (hapus, koreksi stok, tambah/edit
 * data lewat form) bisa selesai tanpa reload halaman.
 *
 * Contoh:
 *   AdminUI.toast('Produk dihapus.', 'success');
 *   const ok = await AdminUI.confirm({ title: 'Hapus?', danger: true });
 *   const values = await AdminUI.formModal({ title: '...', fields: [...] });
 *
 * FormModal lanjutan (submit langsung ke API + error dari server):
 *   await AdminUI.formModal({
 *       title: 'Edit kategori',
 *       width: 'lg',                       // 'lg' (760px) / 'xl' (980px)
 *       fields: [...],                     // tipe: text|number|textarea|select|checkbox|file
 *       body: '<div>...</div>',            // HTML kustom (dipakai sebagai isi form)
 *       onMount: function (form, ui) { },  // pasang event untuk field kustom
 *       submit: function (values, ui) {    // async: biarkan modal terbuka sampai selesai
 *           return AdminApi.request(url, { method: 'POST', body: ui.formData() });
 *       }
 *   });
 *
 * ui yang diterima submit/onMount: { form, modal, close, setBusy, setError,
 *                                    formData(), values() }
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
        }, 4200);
    }

    /* ------------------------------ Modal ------------------------------ */

    function openModal(options, build) {
        options = options || {};

        return new Promise(function (resolve) {
            var backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop';
            var modal = document.createElement('div');
            modal.className = 'modal' + (options.width ? ' modal-' + options.width : '');
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

        return openModal({}, function (modal, close) {
            modal.innerHTML = '<h3 class="modal-title">' + esc(options.title || 'Konfirmasi') + '</h3>'
                + (options.message ? '<p class="modal-text">' + esc(options.message) + '</p>' : '')
                + buttons(options.cancelText, options.confirmText, options.danger);

            modal.querySelector('[data-cancel]').addEventListener('click', function () { close(false); });
            modal.querySelector('[data-confirm]').addEventListener('click', function () { close(true); });
        });
    }

    /* --------------------------- Form modal --------------------------- */

    function fieldHtml(field) {
        var id = 'modal-field-' + String(field.name).replace(/[^a-zA-Z0-9_-]/g, '_');
        var html = '<div class="field">';

        if (field.type === 'checkbox') {
            var checked = field.checked !== undefined ? field.checked : Boolean(field.value);
            return html
                + '<label class="modal-check" for="' + id + '">'
                + '<input id="' + id + '" type="checkbox" name="' + esc(field.name) + '" value="' + esc(field.value === undefined ? '1' : field.value) + '"' + (checked ? ' checked' : '') + '> '
                + '<span>' + esc(field.label) + '</span></label>'
                + (field.help ? '<small class="modal-help">' + esc(field.help) + '</small>' : '')
                + '</div>';
        }

        html += '<label for="' + id + '">' + esc(field.label) + '</label>';

        if (field.type === 'textarea') {
            html += '<textarea id="' + id + '" name="' + esc(field.name) + '" class="input" rows="3"'
                + (field.placeholder ? ' placeholder="' + esc(field.placeholder) + '"' : '') + '>'
                + esc(field.value || '') + '</textarea>';
        } else if (field.type === 'select') {
            html += '<select id="' + id + '" name="' + esc(field.name) + '" class="select">';
            (field.options || []).forEach(function (opt) {
                html += '<option value="' + esc(opt.value) + '"'
                    + (String(opt.value) === String(field.value === undefined || field.value === null ? '' : field.value) ? ' selected' : '') + '>'
                    + esc(opt.label) + '</option>';
            });
            html += '</select>';
        } else if (field.type === 'file') {
            html += '<input id="' + id + '" name="' + esc(field.name) + '" class="input" type="file" accept="' + esc(field.accept || 'image/*') + '">';
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
        return html + '</div>';
    }

    /**
     * Modal berisi form.
     *
     * fields: [{ name, label, type: 'text|number|textarea|select|checkbox|file',
     *            value, checked, placeholder, required, min, max, step, accept,
     *            help, options: [{ value, label }] }]
     *
     * Tanpa `submit`: Promise<object|null> berisi nilai per field (perilaku lama).
     * Dengan `submit(values, ui)`: modal bertahan sampai submit selesai; error dari
     * server (422) otomatis ditampilkan beserta penanda field yang bermasalah.
     */
    function formModal(options) {
        options = options || {};
        var fields = options.fields || [];

        return openModal(options, function (modal, close) {
            var html = '<h3 class="modal-title">' + esc(options.title || '') + '</h3>';
            if (options.message) {
                html += '<p class="modal-text">' + esc(options.message) + '</p>';
            }

            html += '<form class="modal-form" novalidate>';
            if (options.body) {
                html += options.body;
            } else {
                fields.forEach(function (field) { html += fieldHtml(field); });
            }
            if (options.html) {
                html += options.html;
            }

            html += '<div class="modal-error" data-error hidden></div>';
            html += buttons(options.cancelText, options.submitText, options.danger, 'submit');
            html += '</form>';

            modal.innerHTML = html;

            var form = modal.querySelector('form');
            var errorBox = modal.querySelector('[data-error]');
            var confirmBtn = modal.querySelector('[data-confirm]');
            var confirmLabel = confirmBtn.textContent;
            var busy = false;

            function setBusy(isBusy) {
                busy = !!isBusy;
                confirmBtn.disabled = busy;
                confirmBtn.textContent = busy ? (options.busyText || 'Menyimpan…') : confirmLabel;
            }

            function clearErrors() {
                errorBox.hidden = true;
                errorBox.innerHTML = '';
                form.querySelectorAll('.input-error').forEach(function (el) { el.classList.remove('input-error'); });
                form.querySelectorAll('.field-error[data-generated]').forEach(function (el) { el.remove(); });
            }

            function showError(error) {
                var messages = [];

                if (error && error.body && error.body.errors) {
                    Object.keys(error.body.errors).forEach(function (key) {
                        var list = error.body.errors[key] || [];
                        list.forEach(function (msg) { messages.push(msg); });

                        var input = form.querySelector('[name="' + key + '"]') || form.querySelector('[name="' + key + '[]"]');
                        if (input) {
                            input.classList.add('input-error');
                            var note = document.createElement('small');
                            note.className = 'field-error';
                            note.setAttribute('data-generated', '1');
                            note.textContent = list[0] || '';
                            var anchor = input.closest('.field') || input.parentNode;
                            anchor.appendChild(note);
                        }
                    });
                }

                if (!messages.length) {
                    var raw = (error && error.message) || String(error || 'Terjadi kesalahan.');
                    messages.push(raw);
                }

                errorBox.innerHTML = messages.map(esc).join('<br>');
                errorBox.hidden = false;
            }

            function collectValues() {
                var values = {};
                fields.forEach(function (field) {
                    var el = form.elements[field.name];
                    if (!el) { values[field.name] = ''; return; }
                    if (field.type === 'checkbox') {
                        values[field.name] = !!el.checked;
                    } else if (field.type === 'file') {
                        values[field.name] = el.files && el.files[0] ? el.files[0] : null;
                    } else {
                        values[field.name] = el.value;
                    }
                });
                return values;
            }

            var ui = {
                form: form,
                modal: modal,
                close: close,
                setBusy: setBusy,
                setError: showError,
                formData: function () { return new FormData(form); },
                values: collectValues
            };

            function submit(event) {
                if (event) { event.preventDefault(); }
                if (busy) { return; }
                clearErrors();

                var values = collectValues();
                var missing = null;

                fields.forEach(function (field) {
                    if (!field.required) { return; }
                    var value = values[field.name];
                    var empty = field.type === 'file'
                        ? !value
                        : String(value == null ? '' : value).trim() === '';
                    if (empty) {
                        missing = missing || (field.label + ' wajib diisi.');
                    }
                });

                if (missing) {
                    showError(missing);
                    return;
                }

                if (typeof options.submit === 'function') {
                    setBusy(true);
                    Promise.resolve()
                        .then(function () { return options.submit(values, ui); })
                        .then(function (result) { close(result === undefined ? true : result); })
                        .catch(function (error) { setBusy(false); showError(error); });
                } else {
                    close(values);
                }
            }

            if (typeof options.onMount === 'function') {
                options.onMount(form, ui);
            }

            modal.querySelector('[data-cancel]').addEventListener('click', function () { close(null); });
            confirmBtn.addEventListener('click', submit);
            form.addEventListener('submit', submit);
        });
    }

    return {
        esc: esc,
        toast: toast,
        confirm: confirm,
        formModal: formModal
    };
})();
