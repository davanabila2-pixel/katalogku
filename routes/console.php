<?php

use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('drive:auth', function () {
    $clientId = config('services.google_drive.client_id');
    $clientSecret = config('services.google_drive.client_secret');
    $redirectUri = config('services.google_drive.redirect') ?: 'http://localhost:8000/superadmin/drive/callback';

    if (! $clientId || ! $clientSecret) {
        $this->error('GOOGLE_DRIVE_CLIENT_ID dan GOOGLE_DRIVE_CLIENT_SECRET belum dikonfigurasi di .env');
        return 1;
    }

    $client = new GoogleClient();
    $client->setClientId($clientId);
    $client->setClientSecret($clientSecret);
    $client->setRedirectUri($redirectUri);
    $client->setScopes([GoogleDrive::DRIVE_FILE]);
    $client->setAccessType('offline');
    $client->setPrompt('consent');

    $authUrl = $client->createAuthUrl();

    $this->info('=== Google Drive Authorization ===');
    $this->line('1. Buka URL berikut di browser Anda:');
    $this->newLine();
    $this->line($authUrl);
    $this->newLine();
    $this->line("2. Login dan berikan izin.");
    $this->line("3. Setelah dialihkan ke browser callback, salin kode ('code=...') dari address bar dan masukkan ke sini.");
    $this->newLine();

    $code = $this->ask('Masukkan parameter code dari URL redirect');

    if (! $code) {
        $this->error('Kode tidak boleh kosong.');
        return 1;
    }

    // Jika user mem-paste full URL
    if (str_contains($code, 'code=')) {
        parse_str(parse_url($code, PHP_URL_QUERY) ?? $code, $query);
        $code = $query['code'] ?? $code;
    }

    $code = urldecode(trim($code));

    try {
        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (isset($token['error'])) {
            $this->error('Gagal mendapatkan token: ' . ($token['error_description'] ?? $token['error']));
            return 1;
        }

        File::put(storage_path('app/drive-token.json'), json_encode($token, JSON_PRETTY_PRINT));

        if (! empty($token['refresh_token'])) {
            $envPath = base_path('.env');
            if (File::exists($envPath)) {
                $envContent = File::get($envPath);
                $envContent = preg_replace('/^GOOGLE_DRIVE_REFRESH_TOKEN=.*/m', 'GOOGLE_DRIVE_REFRESH_TOKEN=' . $token['refresh_token'], $envContent);
                File::put($envPath, $envContent);
            }
            $this->info('Sukses! GOOGLE_DRIVE_REFRESH_TOKEN berhasil disimpan ke .env!');
        } else {
            $this->warn('Access token didapat, namun Google tidak mengembalikan refresh_token. Coba ulangi dengan prompt=consent.');
        }

        return 0;
    } catch (\Throwable $e) {
        $this->error('Error: ' . $e->getMessage());
        return 1;
    }
})->purpose('Otorisasi Google Drive dan simpan refresh token ke .env');
