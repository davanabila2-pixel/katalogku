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

// Beritahu Symfony/Laravel bahwa entry point aplikasi ada di root (/index.php),
// bukan di subdirektori /api/. Tanpa ini, Symfony menganggap /api adalah base folder
// dan memotong awalan /api dari route API (sehingga /api/v1/stores/... dipotong menjadi /v1/stores/...).
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../public/index.php';

require __DIR__.'/../public/index.php';
