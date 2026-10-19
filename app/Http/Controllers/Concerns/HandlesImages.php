<?php

namespace App\Http\Controllers\Concerns;

use App\Models\MediaFile;
use App\Models\Store;
use App\Services\GoogleDriveService;
use Illuminate\Http\UploadedFile;

trait HandlesImages
{
    protected function storeImage(Store $store, ?UploadedFile $file, ?string $currentPath = null): ?string
    {
        if (! $file) {
            return $currentPath;
        }

        $media = app(GoogleDriveService::class)->uploadProductImage($store, $file);

        if ($currentPath) {
            $this->deleteImage($store, $currentPath, $media->id);
        }

        return $media->public_url ?? $currentPath;
    }

    protected function deleteImage(Store $store, ?string $path, ?int $excludeMediaId = null): void
    {
        if (! $path) {
            return;
        }

        $query = MediaFile::where('store_id', $store->id)
            ->where(function ($q) use ($path) {
                $q->where('public_url', $path)
                  ->orWhere('file_name', $path);
            });

        if ($excludeMediaId) {
            $query->where('id', '!=', $excludeMediaId);
        }

        $old = $query->first();
        if ($old) {
            app(GoogleDriveService::class)->delete($old);
        }
    }
}
