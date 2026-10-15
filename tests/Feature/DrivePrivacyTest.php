<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\GoogleDriveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DrivePrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_validasi_mime_file_ditolak(): void
    {
        Storage::fake('public');
        $service = app(GoogleDriveService::class);
        $bad = UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->validateFile($bad);
    }

    public function test_validasi_ukuran_file_ditolak(): void
    {
        $service = app(GoogleDriveService::class);
        $big = UploadedFile::fake()->create('besar.jpg', 6 * 1024, 'image/jpeg');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->validateFile($big);
    }

    public function test_upload_tanpa_kredensial_memakai_fallback_lokal(): void
    {
        Storage::fake('public');
        config(['services.google_drive.refresh_token' => null]);

        $store = Store::create(['name' => 'Toko Uji', 'status' => 'active']);
        $file = UploadedFile::fake()->create('kaos.jpg', 100, 'image/jpeg');

        $media = app(GoogleDriveService::class)->uploadProductImage($store, $file);

        $this->assertSame('local', $media->provider);
        $this->assertNotNull($media->public_url);
    }

    public function test_token_tidak_bocor_ke_frontend(): void
    {
        $store = Store::create(['name' => 'Toko Uji', 'status' => 'active']);
        Product::create(['store_id' => $store->id, 'name' => 'Kaos', 'slug' => 'kaos', 'selling_price' => 10000, 'status' => 'active']);

        $html = $this->get('/'.$store->slug)->getContent();
        $this->assertStringNotContainsString('GOOGLE', $html);
        $this->assertStringNotContainsString('refresh_token', $html);
        $this->assertStringNotContainsString('client_secret', $html);
    }

    public function test_harga_beli_dan_hpp_tidak_bocor_ke_publik(): void
    {
        $admin = User::create(['name' => 'A', 'email' => 'a@x.com', 'role' => 'admin', 'is_active' => true]);
        $store = Store::create(['name' => 'Toko Uji', 'status' => 'active']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Kaos', 'slug' => 'kaos', 'selling_price' => 50000, 'status' => 'active']);

        app(\App\Services\InventoryService::class)->createBatch([
            'store_id' => $store->id, 'batch_code' => 'B1', 'purchase_date' => now()->toDateString(),
        ], [['product_id' => $product->id, 'quantity' => 10, 'unit_purchase_price' => 20000]], $admin);

        $katalog = $this->get('/'.$store->slug)->getContent();
        $detail = $this->get("/{$store->slug}/produk/kaos")->getContent();

        foreach ([$katalog, $detail] as $html) {
            $lower = strtolower($html);
            $this->assertStringNotContainsString('hpp', $lower);
            $this->assertStringNotContainsString('estimasi margin', $lower);
            $this->assertStringNotContainsString('rata-rata', $lower);
            $this->assertStringNotContainsString('harga beli', $lower);
            $this->assertStringNotContainsString('batch', $lower);
            $this->assertStringNotContainsString('20.000', $html);
        }
        $this->assertStringContainsString('50.000', $detail);
    }
}
