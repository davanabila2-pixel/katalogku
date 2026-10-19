<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Str;
use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeService
{
    public function generateBarcodeString(Product $product): string
    {
        if (! empty($product->barcode)) {
            return $product->barcode;
        }

        do {
            $code = 'BC'.$product->store_id.'-'.strtoupper(Str::random(7));
        } while (Product::where('barcode', $code)->exists());

        return $code;
    }

    public function generateLabelImage(Product $product, string $barcode): string
    {
        $w = 420;
        $h = 530;
        $im = imagecreatetruecolor($w, $h);

        $white = imagecolorallocate($im, 255, 255, 255);
        $dark = imagecolorallocate($im, 20, 24, 28);
        $steel = imagecolorallocate($im, 100, 110, 120);
        $border = imagecolorallocate($im, 210, 215, 222);
        $blue = imagecolorallocate($im, 0, 100, 224);

        imagefill($im, 0, 0, $white);
        imagerectangle($im, 6, 6, $w - 7, $h - 7, $border);

        // Header: Store Name
        $storeName = mb_strtoupper($product->store->name ?? 'KATALOG TOKO');
        if (mb_strlen($storeName) > 30) {
            $storeName = mb_substr($storeName, 0, 27).'...';
        }
        $storeX = max(12, (int) (($w - (strlen($storeName) * imagefontwidth(4))) / 2));
        imagestring($im, 4, $storeX, 18, $storeName, $steel);

        // Product Name
        $prodName = $product->name;
        if (mb_strlen($prodName) > 28) {
            $prodName = mb_substr($prodName, 0, 25).'...';
        }
        $prodX = max(12, (int) (($w - (strlen($prodName) * imagefontwidth(5))) / 2));
        imagestring($im, 5, $prodX, 42, $prodName, $dark);

        // QR Code
        $scanUrl = route('product.scan', $barcode);
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'scale' => 5,
            'outputBase64' => false,
        ]);
        $qrRaw = (new QRCode($options))->render($scanUrl);
        $qrIm = imagecreatefromstring($qrRaw);
        $targetQrSize = 190;
        $qrX = (int) (($w - $targetQrSize) / 2);
        imagecopyresampled($im, $qrIm, $qrX, 75, 0, 0, $targetQrSize, $targetQrSize, imagesx($qrIm), imagesy($qrIm));
        imagedestroy($qrIm);

        // Hint
        $hint = 'Scan untuk detail produk & edit admin';
        $hintX = max(12, (int) (($w - (strlen($hint) * imagefontwidth(2))) / 2));
        imagestring($im, 2, $hintX, 275, $hint, $steel);

        // Linear Barcode 1D (Code-128)
        $gen = new BarcodeGeneratorPNG;
        $barRaw = $gen->getBarcode($barcode, BarcodeGeneratorPNG::TYPE_CODE_128, 2, 45);
        $barIm = imagecreatefromstring($barRaw);
        $barW = imagesx($barIm);
        $barH = imagesy($barIm);
        $targetBarW = min(320, $barW);
        $barX = (int) (($w - $targetBarW) / 2);
        imagecopyresampled($im, $barIm, $barX, 305, 0, 0, $targetBarW, 45, $barW, $barH);
        imagedestroy($barIm);

        // Code Text
        $codeText = '* '.$barcode.' *';
        $codeX = max(12, (int) (($w - (strlen($codeText) * imagefontwidth(4))) / 2));
        imagestring($im, 4, $codeX, 360, $codeText, $dark);

        // Price
        $priceText = 'Rp '.number_format((float) $product->selling_price, 0, ',', '.');
        $priceX = max(12, (int) (($w - (strlen($priceText) * imagefontwidth(5))) / 2));
        imagestring($im, 5, $priceX, 395, $priceText, $blue);

        ob_start();
        imagepng($im);
        $finalPng = ob_get_clean();
        imagedestroy($im);

        return $finalPng;
    }

    public function generateAndUpload(Product $product, ?User $user = null): string
    {
        $barcode = $this->generateBarcodeString($product);
        $pngBinary = $this->generateLabelImage($product, $barcode);
        $fileName = 'barcode-'.$product->id.'-'.$barcode.'.png';

        $media = app(GoogleDriveService::class)->uploadBarcode($product->store, $pngBinary, $fileName, $user?->id);

        $product->updateQuietly([
            'barcode' => $barcode,
            'barcode_image_path' => $media->public_url,
        ]);

        return $media->public_url;
    }

    public function generateQrCodeDataUri(Product $product): string
    {
        if (empty($product->barcode)) {
            $product->barcode = $this->generateBarcodeString($product);
            $product->saveQuietly();
        }

        $scanUrl = route('product.scan', $product->barcode);

        return (new QRCode)->render($scanUrl);
    }

    public function ensureBarcodeAndQr(Product $product, ?User $user = null): Product
    {
        if (empty($product->barcode) || empty($product->barcode_image_path)) {
            try {
                $this->generateAndUpload($product, $user);
                $product->refresh();
            } catch (\Throwable $e) {
                if (empty($product->barcode)) {
                    $product->updateQuietly(['barcode' => $this->generateBarcodeString($product)]);
                }
            }
        }

        return $product;
    }

    public function generateBatchQrCodes(\App\Models\PurchaseBatch $batch, ?User $user = null): array
    {
        $batch->loadMissing('items.product');
        $updated = [];

        foreach ($batch->items as $item) {
            if ($item->product) {
                try {
                    $this->generateAndUpload($item->product, $user);
                    $updated[] = $item->product->refresh();
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Gagal upload barcode produk {$item->product_id} pada batch {$batch->id}: ".$e->getMessage());
                    if (empty($item->product->barcode)) {
                        $item->product->updateQuietly(['barcode' => $this->generateBarcodeString($item->product)]);
                    }
                    $updated[] = $item->product->refresh();
                }
            }
        }

        return $updated;
    }
}
