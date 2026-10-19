/**
 * Helper fetch untuk API JSON admin (same-origin).
 *
 * - Otomatis mengirim session cookie (credentials: same-origin).
 * - Otomatis mengirim header X-CSRF-TOKEN untuk request non-GET
 *   (token dibaca dari <meta name="csrf-token"> di layout dashboard).
 * - Selalu meminta JSON dan melempar Error berisi pesan server kalau gagal.
 *
 * Contoh pakai:
 *   const json = await AdminApi.get('/api/v1/stores/1/products', { q: 'kaos' });
 *   await AdminApi.request(url, { method: 'DELETE' });
 */
window.AdminApi = (function () {
    'use strict';

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function buildUrl(url, params) {
        var result = new URL(url, window.location.origin);
        if (params) {
            Object.keys(params).forEach(function (key) {
                var value = params[key];
                if (value !== '' && value !== null && value !== undefined) {
                    result.searchParams.set(key, value);
                }
            });
        }
        return result.toString();
    }

    async function request(url, options) {
        options = options || {};

        var method = (options.method || 'GET').toUpperCase();
        var headers = Object.assign({
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }, options.headers || {});

        if (method !== 'GET') {
            headers['X-CSRF-TOKEN'] = csrfToken();
        }

        var response = await fetch(url, Object.assign({ credentials: 'same-origin' }, options, {
            method: method,
            headers: headers
        }));

        var body = null;
        try {
            body = await response.json();
        } catch (e) {
            // Respons bukan JSON (mis. redirect halaman login).
        }

        if (!response.ok) {
            var error = new Error((body && body.message) || ('Permintaan gagal (HTTP ' + response.status + ').'));
            error.status = response.status;
            error.body = body;
            throw error;
        }

        return body;
    }

    return {
        token: csrfToken,
        get: function (url, params) {
            return request(buildUrl(url, params));
        },
        request: request
    };
})();
