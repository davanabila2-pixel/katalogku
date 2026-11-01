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

    protected function storeBannerImage(Store $store, ?UploadedFile $file, ?string $currentPath = null): ?string
    {
        if (! $file) {
            return $currentPath;
        }

        $croppedFile = $this->autoCropBannerImage($file);

        return $this->storeImage($store, $croppedFile, $currentPath);
    }

    protected function autoCropBannerImage(UploadedFile $file, int $targetWidth = 1200, int $targetHeight = 400): UploadedFile
    {
        if (! extension_loaded('gd') || ! file_exists($file->getRealPath())) {
            return $file;
        }

        $imageInfo = @getimagesize($file->getRealPath());
        if (! $imageInfo) {
            return $file;
        }

        [$srcWidth, $srcHeight, $type] = $imageInfo;
        if ($srcWidth <= 0 || $srcHeight <= 0) {
            return $file;
        }

        $srcImage = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $srcImage = @imagecreatefromjpeg($file->getRealPath());
                break;
            case IMAGETYPE_PNG:
                $srcImage = @imagecreatefrompng($file->getRealPath());
                break;
            case IMAGETYPE_WEBP:
                $srcImage = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file->getRealPath()) : null;
                break;
            case IMAGETYPE_GIF:
                $srcImage = @imagecreatefromgif($file->getRealPath());
                break;
        }

        if (! $srcImage) {
            return $file;
        }

        $targetRatio = $targetWidth / $targetHeight;
        $srcRatio = $srcWidth / $srcHeight;

        if ($srcRatio > $targetRatio) {
            $cropHeight = $srcHeight;
            $cropWidth = (int) round($srcHeight * $targetRatio);
            $cropX = (int) round(($srcWidth - $cropWidth) / 2);
            $cropY = 0;
        } else {
            $cropWidth = $srcWidth;
            $cropHeight = (int) round($srcWidth / $targetRatio);
            $cropX = 0;
            $cropY = (int) round(($srcHeight - $cropHeight) / 2);
        }

        $destImage = imagecreatetruecolor($targetWidth, $targetHeight);

        imagealphablending($destImage, false);
        imagesavealpha($destImage, true);
        $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
        imagefilledrectangle($destImage, 0, 0, $targetWidth, $targetHeight, $transparent);
        imagealphablending($destImage, true);

        imagecopyresampled(
            $destImage,
            $srcImage,
            0, 0,
            $cropX, $cropY,
            $targetWidth, $targetHeight,
            $cropWidth, $cropHeight
        );

        $tempPath = tempnam(sys_get_temp_dir(), 'banner_crop_');
        $mime = 'image/jpeg';
        if (function_exists('imagewebp')) {
            $tempPath .= '.webp';
            imagewebp($destImage, $tempPath, 90);
            $mime = 'image/webp';
        } else {
            $tempPath .= '.jpg';
            imagejpeg($destImage, $tempPath, 90);
            $mime = 'image/jpeg';
        }

        imagedestroy($srcImage);
        imagedestroy($destImage);

        return new UploadedFile(
            $tempPath,
            'banner_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . ($mime === 'image/webp' ? '.webp' : '.jpg'),
            $mime,
            null,
            true
        );
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
