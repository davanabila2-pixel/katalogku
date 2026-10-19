<?php

namespace Tests\Feature;

use App\Models\MediaFile;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\BarcodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductBarcodeTest extends TestCase
{
    use RefreshDatabase;

    protected function createStoreWithAdmin(string $name = 'Toko Barcode'): array
    {
        $admin = User::create([
            'name' => 'Admin Toko',
            'email' => 'admintoko@test.com',
            'password' => 'secret123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $store = Store::create([
            'name' => $name,
            'slug' => 'toko-barcode',
            'status' => 'active',
        ]);

        $store->users()->attach($admin->id, ['is_primary' => true]);

        return [$store, $admin];
    }

    public function test_membuat_produk_otomatis_generate_barcode_dan_upload_ke_gdrive(): void
    {
        [$store, $admin] = $this->createStoreWithAdmin();

        $response = $this->actingAs($admin)->post(route('admin.products.store', $store), [
            'name' => 'Baju Kemeja Katun',
            'selling_price' => 150000,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.products.index', $store));

        $product = Product::where('store_id', $store->id)->where('name', 'Baju Kemeja Katun')->first();
        $this->assertNotNull($product);
        $this->assertNotNull($product->barcode);
        $this->assertStringStartsWith('BC'.$store->id.'-', $product->barcode);
        $this->assertNotNull($product->barcode_image_path);
        $this->assertStringContainsString('drive.google.com', $product->barcode_image_path);

        // Pastikan tersimpan sebagai media gdrive di database, bukan local disk
        $media = MediaFile::where('store_id', $store->id)->where('file_name', 'like', "%{$product->barcode}%")->first();
        $this->assertNotNull($media);
        $this->assertSame('gdrive', $media->provider);
        $this->assertSame('gdrive', $media->disk);
    }

    public function test_scan_barcode_oleh_admin_toko_mengarah_ke_edit_produk(): void
    {
        [$store, $admin] = $this->createStoreWithAdmin();

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Celana Jeans Slim',
            'slug' => 'celana-jeans-slim',
            'barcode' => 'BC'.$store->id.'-TEST123',
            'selling_price' => 180000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('product.scan', $product->barcode));

        $response->assertRedirect(route('admin.products.edit', [$store, $product]));
    }

    public function test_scan_barcode_oleh_superadmin_mengarah_ke_edit_produk(): void
    {
        [$store] = $this->createStoreWithAdmin();

        $superadmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => 'secret123',
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Jaket Parka Outdoor',
            'slug' => 'jaket-parka-outdoor',
            'barcode' => 'BC'.$store->id.'-PARKA99',
            'selling_price' => 250000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($superadmin)->get(route('product.scan', $product->barcode));

        $response->assertRedirect(route('admin.products.edit', [$store, $product]));
    }

    public function test_scan_barcode_oleh_tamu_mengarah_ke_detail_produk_katalog_publik(): void
    {
        [$store] = $this->createStoreWithAdmin();

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Sepatu Sneakers Pria',
            'slug' => 'sepatu-sneakers-pria',
            'barcode' => 'BC'.$store->id.'-SNEAK01',
            'selling_price' => 320000,
            'status' => 'active',
        ]);

        $response = $this->get(route('product.scan', $product->barcode));

        $response->assertRedirect(route('store.product', [$store->slug, $product->slug]));
    }

    public function test_scan_barcode_dengan_parameter_admin_oleh_tamu_mengarah_ke_login(): void
    {
        [$store] = $this->createStoreWithAdmin();

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Topi Baseball Vintage',
            'slug' => 'topi-baseball-vintage',
            'barcode' => 'BC'.$store->id.'-TOPI123',
            'selling_price' => 75000,
            'status' => 'active',
        ]);

        $response = $this->get(route('product.scan', $product->barcode).'?admin=1');

        $response->assertRedirect(route('login'));
        $this->assertEquals(route('admin.products.edit', [$store, $product]), session('url.intended'));
    }

    public function test_scan_dapat_mengenali_sku_atau_id_produk(): void
    {
        [$store, $admin] = $this->createStoreWithAdmin();

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Dompet Kulit Asli',
            'slug' => 'dompet-kulit-asli',
            'sku' => 'SKU-DOMPET-01',
            'barcode' => 'BC'.$store->id.'-DOMPET1',
            'selling_price' => 95000,
            'status' => 'active',
        ]);

        // Scan menggunakan SKU (sebagai admin)
        $responseSku = $this->actingAs($admin)->get(route('product.scan', 'SKU-DOMPET-01'));
        $responseSku->assertRedirect(route('admin.products.edit', [$store, $product]));

        // Scan menggunakan ID (sebagai publik/tamu)
        auth()->logout();
        $responseId = $this->get(route('product.scan', (string) $product->id));
        $responseId->assertRedirect(route('store.product', [$store->slug, $product->slug]));
    }

    public function test_regenerate_barcode_memperbarui_label_dan_upload_ke_gdrive(): void
    {
        [$store, $admin] = $this->createStoreWithAdmin();

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Kaos Polos Premium',
            'slug' => 'kaos-polos-premium',
            'selling_price' => 50000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.products.barcode.regenerate', [$store, $product]));
        $response->assertRedirect();
        $response->assertSessionHas('status');

        $product->refresh();
        $this->assertNotNull($product->barcode);
        $this->assertNotNull($product->barcode_image_path);
        $this->assertStringContainsString('drive.google.com', $product->barcode_image_path);
    }

    public function test_barcode_label_image_composite_dihasilkan_dengan_valid(): void
    {
        [$store] = $this->createStoreWithAdmin();

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Kemeja Flannel Tartan',
            'slug' => 'kemeja-flannel-tartan',
            'selling_price' => 125000,
            'status' => 'active',
        ]);

        $barcodeService = app(BarcodeService::class);
        $code = $barcodeService->generateBarcodeString($product);
        $pngBinary = $barcodeService->generateLabelImage($product, $code);

        $this->assertNotEmpty($pngBinary);
        // Memastikan output berupa header biner gambar PNG yang valid
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $pngBinary);

        // Memastikan ukuran gambar
        $size = getimagesizefromstring($pngBinary);
        $this->assertSame(420, $size[0]);
        $this->assertSame(530, $size[1]);
    }
}
