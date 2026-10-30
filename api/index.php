<?php

header('X-Katalog-Commit: 3201579');

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

// 1. Gateway Vercel memotong prefix /api saat memanggil api/index.php
// (contoh: /api/v1/stores/... dikirim ke PHP sebagai /v1/stores/...).
// Kembalikan awalan /api pada REQUEST_URI agar sesuai dengan rute Laravel.
foreach (['REQUEST_URI', 'PATH_INFO', 'PHP_SELF', 'REDIRECT_URL'] as $key) {
    if (isset($_SERVER[$key]) && str_starts_with($_SERVER[$key], '/v1/')) {
        $_SERVER[$key] = '/api' . $_SERVER[$key];
    }
}

// 2. Beritahu Symfony/Laravel bahwa entry point aplikasi ada di root (/index.php),
// bukan di subdirektori /api/. Tanpa ini, Symfony menganggap /api adalah base folder
// dan memotong kembali awalan /api dari route API.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../public/index.php';

require __DIR__.'/../public/index.php';
