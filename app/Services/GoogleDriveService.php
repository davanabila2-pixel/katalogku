<?php

namespace App\Services;

use App\Models\MediaFile;
use App\Models\Store;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Penyimpanan gambar: Google Drive (utama) dengan fallback lokal.
 *
 * - File fisik TIDAK disimpan di database (hanya metadata + Drive file ID).
 * - Token/secret tidak pernah dikirim ke browser.
 * - Jika kredensial Drive belum dikonfigurasi, upload otomatis memakai
 *   disk lokal dan provider dicatat 'local' agar aktivasi Drive di kemudian
 *   hari tidak merusak data (lihat README bagian Google Drive).
 */
class GoogleDriveService
{
    public const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    public const MAX_SIZE = 5 * 1024 * 1024;

    public function isConfigured(): bool
    {
        return (bool) (config('services.google_drive.refresh_token')
            && config('services.google_drive.root_folder_id'));
    }

    public function validateFile(UploadedFile $file): void
    {
        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            abort(422, 'Jenis file tidak didukung. Gunakan JPG, PNG, WebP, atau GIF.');
        }

        if ($file->getSize() > self::MAX_SIZE) {
            abort(422, 'Ukuran file maksimal 5MB.');
        }
    }

    protected function safeName(UploadedFile $file): string
    {
        return Str::random(12).'-'.preg_replace('/[^a-zA-Z0-9._-]/', '-', $file->getClientOriginalName());
    }

    protected function client(): \Google\Client
    {
        $cfg = config('services.google_drive');

        $client = new \Google\Client;
        $client->setClientId($cfg['client_id']);
        $client->setClientSecret($cfg['client_secret']);
        $client->setScopes([\Google\Service\Drive::DRIVE_FILE]);
        $client->setAccessType('offline');

        $token = $cfg['refresh_token']
            ? ['refresh_token' => $cfg['refresh_token']]
            : json_decode((string) @file_get_contents(storage_path('app/drive-token.json')), true);

        if (! $token) {
            throw new \RuntimeException('Google Drive belum terhubung. Lihat README untuk aktivasi.');
        }

        $client->setAccessToken($token);
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        }

        return $client;
    }

    protected function ensureFolder(\Google\Service\Drive $drive, string $name, ?string $parentId): string
    {
        $query = "name = '{$name}' and mimeType = 'application/vnd.google-apps.folder' and trashed = false";
        if ($parentId) {
            $query .= " and '{$parentId}' in parents";
        }

        $existing = $drive->files->listFiles(['q' => $query, 'fields' => 'files(id)'])->getFiles();
        if ($existing) {
            return $existing[0]->getId();
        }

        $folder = new \Google\Service\Drive\DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => $parentId ? [$parentId] : [],
        ]);

        return $drive->files->create($folder, ['fields' => 'id'])->getId();
    }

    public function storeFolderId(Store $store): ?string
    {
        return $store->slug ? 'store-'.$store->id.'-'.$store->slug : 'store-'.$store->id;
    }

    /**
     * Upload gambar produk. Mengembalikan MediaFile.
     * Folder Drive memakai ID toko sebagai identifier utama sehingga
     * perubahan slug tidak memutus relasi file.
     */
    public function uploadProductImage(Store $store, UploadedFile $file, ?int $userId = null): MediaFile
    {
        $this->validateFile($file);
        $name = $this->safeName($file);

        if (! $this->isConfigured()) {
            $path = $file->storeAs('products/'.$store->id, $name, 'public');

            return MediaFile::create([
                'store_id' => $store->id,
                'disk' => 'public',
                'provider' => 'local',
                'file_name' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'public_url' => asset('storage/'.$path),
                'uploaded_by' => $userId ?? auth()->id(),
            ]);
        }

        try {
            $client = $this->client();
            $drive = new \Google\Service\Drive($client);

            $rootId = config('services.google_drive.root_folder_id');
            $storeFolder = $this->ensureFolder($drive, $this->storeFolderId($store), $rootId);
            $productFolder = $this->ensureFolder($drive, 'Produk', $storeFolder);

            $driveFile = new \Google\Service\Drive\DriveFile([
                'name' => $name,
                'parents' => [$productFolder],
            ]);

            $created = $drive->files->create($driveFile, [
                'data' => file_get_contents($file->getRealPath()),
                'mimeType' => $file->getMimeType(),
                'uploadType' => 'multipart',
                'fields' => 'id, webViewLink',
            ]);

            $permission = new \Google\Service\Drive\Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]);
            $drive->permissions->create($created->getId(), $permission);

            $publicUrl = 'https://drive.google.com/thumbnail?id='.$created->getId().'&sz=w1200';

            return MediaFile::create([
                'store_id' => $store->id,
                'disk' => 'gdrive',
                'provider' => 'gdrive',
                'file_id' => $created->getId(),
                'file_name' => $name,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'public_url' => $publicUrl,
                'metadata' => ['folder' => $productFolder, 'web_view' => $created->getWebViewLink()],
                'uploaded_by' => $userId ?? auth()->id(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Google Drive upload gagal, fallback lokal.', ['error' => $e->getMessage()]);
            $path = $file->storeAs('products/'.$store->id, $name, 'public');

            return MediaFile::create([
                'store_id' => $store->id,
                'disk' => 'public',
                'provider' => 'local',
                'file_id' => null,
                'file_name' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'public_url' => asset('storage/'.$path),
                'metadata' => ['drive_error' => 'upload gagal, memakai lokal'],
                'uploaded_by' => $userId ?? auth()->id(),
            ]);
        }
    }

    public function delete(MediaFile $media): void
    {
        try {
            if ($media->provider === 'gdrive' && $media->file_id && $this->isConfigured()) {
                $drive = new \Google\Service\Drive($this->client());
                $drive->files->delete($media->file_id);
            } elseif ($media->provider === 'local' && $media->file_name) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($media->file_name);
            }
        } catch (\Throwable $e) {
            Log::warning('Hapus file Drive gagal.', ['media_id' => $media->id]);
        }
        $media->delete();
    }
}
