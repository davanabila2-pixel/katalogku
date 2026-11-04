<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use App\Services\ProductImportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductImportExportTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Toko',
            'email' => 'admin@toko.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->store = Store::create([
            'name' => 'Toko Busana Cantik',
            'slug' => 'toko-busana-cantik',
            'status' => 'active',
        ]);

        $this->store->users()->attach($this->admin->id, ['is_primary' => true]);
    }

    public function test_artisan_export_generates_valid_csv_file_with_checksum(): void
    {
        $cat = Category::create([
            'store_id' => $this->store->id,
            'name' => 'Pakaian Wanita',
            'slug' => 'pakaian-wanita',
            'is_active' => true,
        ]);

        $product = Product::create([
            'store_id' => $this->store->id,
            'category_id' => $cat->id,
            'name' => 'Dress Floral Cantik',
            'slug' => 'dress-floral-cantik',
            'sku' => 'DRS-001',
            'selling_price' => 150000,
            'weight' => 300,
            'stock' => 25,
            'status' => 'active',
            'description' => 'Dress motif bunga bahan katun premium',
        ]);

        $outputPath = storage_path('app/testing_export.csv');
        if (file_exists($outputPath)) {
            unlink($outputPath);
        }

        $this->artisan('products:export', [
            '--store' => $this->store->id,
            '--path' => $outputPath,
        ])->assertSuccessful();

        $this->assertFileExists($outputPath);
        $content = file_get_contents($outputPath);

        // Pastikan memiliki UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // Pastikan memiliki header standar dan kolom checksum
        $this->assertStringContainsString('id,name,slug,sku,category,selling_price,weight,stock,status,is_bundle,is_hidden,description,checksum', $content);

        // Pastikan produk dan checksum yang diexport ada di dalamnya
        $this->assertStringContainsString('Dress Floral Cantik', $content);
        $this->assertStringContainsString('DRS-001', $content);
        $this->assertStringContainsString('ck_', $content);

        if (file_exists($outputPath)) {
            unlink($outputPath);
        }
    }

    public function test_artisan_export_with_selected_columns_per_kolom(): void
    {
        Product::create([
            'store_id' => $this->store->id,
            'name' => 'Kemeja Linen Pria',
            'slug' => 'kemeja-linen-pria',
            'sku' => 'KLN-001',
            'selling_price' => 175000,
            'stock' => 30,
            'status' => 'active',
        ]);

        $outputPath = storage_path('app/testing_export_columns.csv');
        if (file_exists($outputPath)) {
            unlink($outputPath);
        }

        // Export hanya kolom name, selling_price, dan stock
        $this->artisan('products:export', [
            '--store' => $this->store->id,
            '--path' => $outputPath,
            '--columns' => 'name,selling_price,stock',
        ])->assertSuccessful();

        $this->assertFileExists($outputPath);
        $content = file_get_contents($outputPath);

        // Header harus berisi id, name, selling_price, stock, checksum
        $this->assertStringContainsString('id,name,selling_price,stock,checksum', $content);
        $this->assertStringContainsString('Kemeja Linen Pria', $content);
        $this->assertStringContainsString('175000,30,ck_', $content);

        if (file_exists($outputPath)) {
            unlink($outputPath);
        }
    }

    public function test_partial_column_import_and_checksum_unchanged_detection(): void
    {
        $existing = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Baju Tidur Satin',
            'slug' => 'baju-tidur-satin',
            'sku' => 'BTS-001',
            'selling_price' => 100000,
            'weight' => 250,
            'stock' => 20,
            'status' => 'active',
            'description' => 'Bahan satin adem',
        ]);

        $service = app(ProductImportExportService::class);

        // 1. Import pertama: nilai harga sama persis dengan DB (unchanged detection by checksum)
        $csvUnchanged = "id,harga_jual\n{$existing->id},100000\n";
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $csvUnchanged);
        rewind($stream);

        $res1 = $service->importFromStream($this->store, $stream);
        fclose($stream);

        $this->assertEquals(0, $res1['created_count']);
        $this->assertEquals(0, $res1['updated_count']);
        $this->assertEquals(1, $res1['unchanged_count']); // Checksum sama persis, dilewati tanpa write DB!

        // 2. Import kedua: hanya update harga_jual ke 135.000 (per-kolom saja)
        $csvPriceUpdate = "id,harga_jual\n{$existing->id},135000\n";
        $stream2 = fopen('php://memory', 'r+');
        fwrite($stream2, $csvPriceUpdate);
        rewind($stream2);

        $res2 = $service->importFromStream($this->store, $stream2);
        fclose($stream2);

        $this->assertEquals(1, $res2['updated_count']);
        $this->assertEquals(0, $res2['unchanged_count']);

        $existing->refresh();
        $this->assertEquals(135000, (int) $existing->selling_price);
        // Pastikan kolom lain tidak terhapus atau tertimpa
        $this->assertEquals('Baju Tidur Satin', $existing->name);
        $this->assertEquals(250, $existing->weight);
        $this->assertEquals(20, $existing->stock);
        $this->assertEquals('Bahan satin adem', $existing->description);

        // 3. Import ketiga: update stok via kolom SKU saja (per-kolom)
        $csvStockUpdate = "sku,stok\nBTS-001,35\n";
        $stream3 = fopen('php://memory', 'r+');
        fwrite($stream3, $csvStockUpdate);
        rewind($stream3);

        $res3 = $service->importFromStream($this->store, $stream3);
        fclose($stream3);

        $this->assertEquals(1, $res3['updated_count']);
        $existing->refresh();
        $this->assertEquals(35, $existing->stock);

        // Pastikan tercatat stock movement penyesuaian (+15)
        $movement = StockMovement::where('product_id', $existing->id)
            ->where('movement_type', 'adjustment')
            ->first();
        $this->assertNotNull($movement);
        $this->assertEquals(15, $movement->quantity);
    }

    public function test_artisan_import_can_insert_new_products_and_update_existing_in_batches(): void
    {
        $existing = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Kemeja Katun Putih',
            'slug' => 'kemeja-katun-putih',
            'sku' => 'KMJ-001',
            'selling_price' => 100000,
            'weight' => 250,
            'stock' => 10,
            'status' => 'active',
        ]);

        $csvPath = tempnam(sys_get_temp_dir(), 'test_import_') . '.csv';
        $fh = fopen($csvPath, 'w');
        fputs($fh, "\xEF\xBB\xBF");
        fputcsv($fh, ['id', 'name', 'slug', 'sku', 'category', 'selling_price', 'weight', 'stock', 'status', 'is_bundle', 'is_hidden', 'description', 'checksum']);

        // Baris 1: UPDATE produk eksisting (ID diisi, ubah harga dan stok)
        fputcsv($fh, [
            $existing->id,
            'Kemeja Katun Putih (Updated)',
            'kemeja-katun-putih',
            'KMJ-001',
            'Kemeja Formal',
            120000,
            260,
            30,
            'active',
            '0',
            '0',
            'Kemeja formal katun putih rapi.',
            '',
        ]);

        // Baris 2: INSERT produk baru A (ID kosong, buat kategori baru otomatis)
        fputcsv($fh, [
            '',
            'Rok Plisket Panjang',
            '',
            'ROK-PLK-01',
            'Rok & Celana',
            85000,
            200,
            50,
            'active',
            '0',
            '0',
            'Rok plisket bahan jatuh dan adem.',
            '',
        ]);

        // Baris 3: INSERT produk baru B
        fputcsv($fh, [
            '',
            'Cardigan Rajut Halus',
            '',
            'CRD-001',
            'Outer',
            95000,
            350,
            15,
            'active',
            '0',
            '0',
            'Cardigan rajut lembut.',
            '',
        ]);
        fclose($fh);

        $this->artisan('products:import', [
            'file' => $csvPath,
            '--store' => $this->store->id,
            '--batch-size' => 2,
        ])->assertSuccessful();

        $existing->refresh();
        $this->assertEquals('Kemeja Katun Putih (Updated)', $existing->name);
        $this->assertEquals(120000, (int) $existing->selling_price);
        $this->assertEquals(30, $existing->stock);

        $newProductA = Product::forStore($this->store->id)->where('sku', 'ROK-PLK-01')->first();
        $this->assertNotNull($newProductA);
        $this->assertEquals('Rok Plisket Panjang', $newProductA->name);
        $this->assertEquals(50, $newProductA->stock);

        $cat = Category::forStore($this->store->id)->where('name', 'Rok & Celana')->first();
        $this->assertNotNull($cat);
        $this->assertEquals($cat->id, $newProductA->category_id);

        $newProductB = Product::forStore($this->store->id)->where('sku', 'CRD-001')->first();
        $this->assertNotNull($newProductB);
        $this->assertEquals(15, $newProductB->stock);

        unlink($csvPath);
    }

    public function test_artisan_import_dry_run_does_not_persist_changes(): void
    {
        $csvPath = tempnam(sys_get_temp_dir(), 'test_dry_') . '.csv';
        $fh = fopen($csvPath, 'w');
        fputcsv($fh, ['id', 'name', 'slug', 'sku', 'category', 'selling_price', 'weight', 'stock', 'status', 'is_bundle', 'is_hidden', 'description', 'checksum']);
        fputcsv($fh, ['', 'Produk Percobaan Dry Run', '', 'DRY-999', 'Uji Coba', 50000, 100, 10, 'active', '0', '0', 'Desc', '']);
        fclose($fh);

        $this->artisan('products:import', [
            'file' => $csvPath,
            '--store' => $this->store->id,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertDatabaseMissing('products', [
            'store_id' => $this->store->id,
            'sku' => 'DRY-999',
        ]);

        unlink($csvPath);
    }

    public function test_admin_web_export_and_template_downloads(): void
    {
        $responseTemplate = $this->actingAs($this->admin)->get(route('admin.products.template', $this->store));
        $responseTemplate->assertOk();
        $this->assertStringContainsString('text/csv', $responseTemplate->headers->get('Content-Type'));
        $this->assertStringContainsString('template_import_produk.csv', $responseTemplate->headers->get('Content-Disposition'));

        Product::create([
            'store_id' => $this->store->id,
            'name' => 'Gamis Syari Elegan',
            'slug' => 'gamis-syari-elegan',
            'selling_price' => 200000,
            'stock' => 12,
            'status' => 'active',
        ]);

        // Export dengan pilihan kolom spesifik via query parameter
        $responseExport = $this->actingAs($this->admin)->get(
            route('admin.products.export', ['store' => $this->store, 'columns' => 'name,selling_price,stock'])
        );
        $responseExport->assertOk();
        $this->assertStringContainsString('text/csv', $responseExport->headers->get('Content-Type'));
        $this->assertStringContainsString('products_toko-busana-cantik', $responseExport->headers->get('Content-Disposition'));
    }

    public function test_admin_web_import_ajax_response(): void
    {
        $csvContent = "\xEF\xBB\xBFid,name,slug,sku,category,selling_price,weight,stock,status,is_bundle,is_hidden,description,checksum\n"
            . ",Jilbab Segi Empat Voal,,JLB-01,Hijab,35000,100,100,active,0,0,Bahan voal premium lembut.,\n";

        $file = UploadedFile::fake()->createWithContent('import_test.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('admin.products.import', $this->store), [
                'file' => $file,
                'batch_size' => 50,
            ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'success');
        $this->assertEquals(1, $response->json('result.created_count'));

        $this->assertDatabaseHas('products', [
            'store_id' => $this->store->id,
            'sku' => 'JLB-01',
            'name' => 'Jilbab Segi Empat Voal',
            'stock' => 100,
        ]);
    }
}
