<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// Pastikan direktori penyimpanan sementara di /tmp tersedia untuk serverless (Vercel)
foreach ([
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
] as $dir) {
    if (! is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Vercel serverless function di /api/index.php otomatis memotong prefix /api dari URL
// (contoh: /api/v1/stores/2/products di-pass sebagai /v1/stores/2/products).
// Kembalikan prefix /api agar cocok dengan routing Laravel.
foreach (['REQUEST_URI', 'PATH_INFO', 'PHP_SELF', 'REDIRECT_URL'] as $key) {
    if (isset($_SERVER[$key]) && str_starts_with($_SERVER[$key], '/v1/')) {
        $_SERVER[$key] = '/api' . $_SERVER[$key];
    }
}

require __DIR__.'/../public/index.php';
