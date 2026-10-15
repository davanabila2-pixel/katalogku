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
            $old = MediaFile::where('store_id', $store->id)->where('public_url', $currentPath)->first()
                ?? MediaFile::where('store_id', $store->id)->where('file_name', $currentPath)->first();
            if ($old && $old->id !== $media->id) {
                app(GoogleDriveService::class)->delete($old);
            }
        }

        return $media->public_url ?? $currentPath;
    }
}
