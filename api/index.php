<?php

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

require __DIR__.'/../public/index.php';

