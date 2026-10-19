<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class GoogleDriveController extends Controller
{
    protected function getClient(): GoogleClient
    {
        $clientId = config('services.google_drive.client_id');
        $clientSecret = config('services.google_drive.client_secret');
        $redirectUri = config('services.google_drive.redirect') ?: url('/superadmin/drive/callback');

        if (! $clientId || ! $clientSecret) {
            abort(500, 'GOOGLE_DRIVE_CLIENT_ID dan GOOGLE_DRIVE_CLIENT_SECRET belum dikonfigurasi di .env');
        }

        $client = new GoogleClient();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->setScopes([GoogleDrive::DRIVE_FILE]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }

    public function connect()
    {
        $client = $this->getClient();
        $authUrl = $client->createAuthUrl();

        return redirect()->away($authUrl);
    }

    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('superadmin.dashboard')->with('error', 'Otorisasi Google Drive dibatalkan: ' . $request->get('error'));
        }

        $code = $request->get('code');
        if (! $code) {
            return redirect()->route('superadmin.dashboard')->with('error', 'Kode otorisasi Google Drive tidak ditemukan.');
        }

        try {
            $client = $this->getClient();
            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                return redirect()->route('superadmin.dashboard')->with('error', 'Gagal mendapatkan token: ' . ($token['error_description'] ?? $token['error']));
            }

            File::put(storage_path('app/drive-token.json'), json_encode($token, JSON_PRETTY_PRINT));

            if (! empty($token['refresh_token'])) {
                $this->updateEnv(['GOOGLE_DRIVE_REFRESH_TOKEN' => $token['refresh_token']]);
            }

            return redirect()->route('superadmin.dashboard')->with('success', 'Google Drive berhasil terhubung! Refresh token telah tersimpan.');
        } catch (\Throwable $e) {
            return redirect()->route('superadmin.dashboard')->with('error', 'Terjadi kesalahan saat menghubungkan Google Drive: ' . $e->getMessage());
        }
    }

    protected function updateEnv(array $data): void
    {
        $envPath = base_path('.env');
        if (! File::exists($envPath)) {
            return;
        }

        $envContent = File::get($envPath);
        foreach ($data as $key => $val) {
            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$val}", $envContent);
            } else {
                $envContent .= "\n{$key}={$val}";
            }
        }
        File::put($envPath, $envContent);
    }
}
