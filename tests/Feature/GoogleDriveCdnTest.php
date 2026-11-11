<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use App\Support\GoogleDriveUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleDriveCdnTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_drive_url_helper_extracts_id_correctly(): void
    {
        $thumbnailUrl = 'https://drive.google.com/thumbnail?id=14ZOCmngJO4rNN_ekzsQqAK8Xeer3i06D&sz=w1200';
        $viewUrl = 'https://drive.google.com/file/d/14ZOCmngJO4rNN_ekzsQqAK8Xeer3i06D/view';
        $ucUrl = 'https://drive.google.com/uc?id=14ZOCmngJO4rNN_ekzsQqAK8Xeer3i06D';
        $lh3Url = 'https://lh3.googleusercontent.com/d/14ZOCmngJO4rNN_ekzsQqAK8Xeer3i06D=w1200';

        $this->assertEquals('14ZOCmngJO4rNN_ekzsQqAK8Xeer3i06D', GoogleDriveUrl::extractFileId($thumbnailUrl));
        $this->assertEquals('14ZOCmngJO4rNN_ekzsQqAK8Xeer3i06D', GoogleDriveUrl::extractFileId($viewUrl));
        $this->assertEquals('14ZOCmngJO4rNN_ekzsQqAK8Xeer3i06D', GoogleDriveUrl::extractFileId($ucUrl));
        $this->assertEquals('14ZOCmngJO4rNN_ekzsQqAK8Xeer3i06D', GoogleDriveUrl::extractFileId($lh3Url));
        $this->assertNull(GoogleDriveUrl::extractFileId('https://images.unsplash.com/photo-1234'));
    }

    public function test_google_drive_url_converts_to_direct_lh3_cdn_url(): void
    {
        $driveUrl = 'https://drive.google.com/thumbnail?id=14ZOCmngJO4rNN_ekzsQqAK8Xeer3i06D&sz=w1200';
        $cdnUrl = GoogleDriveUrl::toDirectCdnUrl($driveUrl);

        $this->assertEquals('https://lh3.googleusercontent.com/d/14ZOCmngJO4rNN_ekzsQqAK8Xeer3i06D=w1200', $cdnUrl);
    }

    public function test_models_return_direct_lh3_cdn_urls_for_gdrive_images_without_using_vps_storage(): void
    {
        $store = Store::create([
            'name' => 'Toko Cloud GDrive',
            'slug' => 'toko-cloud-gdrive',
            'status' => 'active',
            'logo_path' => 'https://drive.google.com/thumbnail?id=16eYlcnMDNnGs26oYUQu5L-ZUP3pPZgwV&sz=w1200',
            'qr_image_path' => 'https://drive.google.com/thumbnail?id=19X8D7mOhJB7WRAkYEgVT6m-8Dxog5WL0&sz=w1200',
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Produk Cloud GDrive',
            'slug' => 'produk-cloud-gdrive',
            'selling_price' => 50000,
            'status' => 'active',
            'image_path' => 'https://drive.google.com/thumbnail?id=14ZOCmngJO4rNN_ekzsQqAK8Xeer3i06D&sz=w1200',
        ]);

        $extraImage = ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'https://drive.google.com/file/d/1BBF_pYeRtMaOLhccdXKhH9fddzjDBJXh/view',
            'position' => 1,
        ]);

        $this->assertEquals('https://lh3.googleusercontent.com/d/16eYlcnMDNnGs26oYUQu5L-ZUP3pPZgwV=w1200', $store->logoUrl());
        $this->assertEquals('https://lh3.googleusercontent.com/d/19X8D7mOhJB7WRAkYEgVT6m-8Dxog5WL0=w1200', $store->qrImageUrl());
        $this->assertEquals('https://lh3.googleusercontent.com/d/14ZOCmngJO4rNN_ekzsQqAK8Xeer3i06D=w1200', $product->imageUrl());
        $this->assertEquals('https://lh3.googleusercontent.com/d/1BBF_pYeRtMaOLhccdXKhH9fddzjDBJXh=w1200', $extraImage->imageUrl());
    }

    public function test_layout_includes_no_referrer_meta_tag(): void
    {
        $store = Store::create([
            'name' => 'Toko Meta Test',
            'slug' => 'toko-meta-test',
            'status' => 'active',
        ]);

        $response = $this->get('/' . $store->slug);
        $response->assertOk();
        $response->assertSee('<meta name="referrer" content="no-referrer">', false);
    }
}

