<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseBatch;
use App\Models\PurchaseBatchItem;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use App\Services\ProductImportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
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

    public function test_artisan_export_generates_valid_xlsx_file_per_kolom_with_checksum(): void
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

        $outputPath = storage_path('app/testing_export.xlsx');
        if (file_exists($outputPath)) {
            unlink($outputPath);
        }

        // Export default ke format xlsx
        $this->artisan('products:export', [
            '--store' => $this->store->id,
            '--path' => $outputPath,
        ])->assertSuccessful();

        $this->assertFileExists($outputPath);

        // Baca file spreadsheet hasil export
        $reader = IOFactory::createReaderForFile($outputPath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($outputPath);
        $sheet = $spreadsheet->getActiveSheet();

        // Verifikasi sel baris 1 adalah kolom terpisah (bukan digabung dalam 1 kotak)
        $this->assertEquals('id', $sheet->getCell('A1')->getValue());
        $this->assertEquals('name', $sheet->getCell('B1')->getValue());
        $this->assertEquals('slug', $sheet->getCell('C1')->getValue());
        $this->assertEquals('sku', $sheet->getCell('D1')->getValue());
        $this->assertEquals('category', $sheet->getCell('E1')->getValue());
        $this->assertEquals('selling_price', $sheet->getCell('F1')->getValue());
        $this->assertEquals('weight', $sheet->getCell('G1')->getValue());
        $this->assertEquals('stock', $sheet->getCell('H1')->getValue());
        $this->assertEquals('status', $sheet->getCell('I1')->getValue());
        $this->assertEquals('checksum', $sheet->getCell('M1')->getValue());

        // Verifikasi data baris 2
        $this->assertEquals($product->id, $sheet->getCell('A2')->getValue());
        $this->assertEquals('Dress Floral Cantik', $sheet->getCell('B2')->getValue());
        $this->assertEquals('dress-floral-cantik', $sheet->getCell('C2')->getValue());
        $this->assertEquals('DRS-001', $sheet->getCell('D2')->getValue());
        $this->assertEquals('Pakaian Wanita', $sheet->getCell('E2')->getValue());
        $this->assertEquals(150000, $sheet->getCell('F2')->getValue());
        $this->assertEquals(300, $sheet->getCell('G2')->getValue());
        $this->assertEquals(25, $sheet->getCell('H2')->getValue());
        $this->assertEquals('active', $sheet->getCell('I2')->getValue());

        $checksum = (string) $sheet->getCell('M2')->getValue();
        $this->assertStringStartsWith('ck_', $checksum);

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

        $outputPath = storage_path('app/testing_export_columns.xlsx');
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

        $reader = IOFactory::createReaderForFile($outputPath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($outputPath);
        $sheet = $spreadsheet->getActiveSheet();

        // Kolom harus berupa: id, name, selling_price, stock, checksum
        $this->assertEquals('id', $sheet->getCell('A1')->getValue());
        $this->assertEquals('name', $sheet->getCell('B1')->getValue());
        $this->assertEquals('selling_price', $sheet->getCell('C1')->getValue());
        $this->assertEquals('stock', $sheet->getCell('D1')->getValue());
        $this->assertEquals('checksum', $sheet->getCell('E1')->getValue());

        $this->assertEquals('Kemeja Linen Pria', $sheet->getCell('B2')->getValue());
        $this->assertEquals(175000, $sheet->getCell('C2')->getValue());
        $this->assertEquals(30, $sheet->getCell('D2')->getValue());

        if (file_exists($outputPath)) {
            unlink($outputPath);
        }
    }

    public function test_artisan_export_filtered_by_purchase_batch(): void
    {
        $batch = PurchaseBatch::create([
            'store_id' => $this->store->id,
            'batch_code' => 'BCH-20261102-001',
            'purchase_date' => now()->toDateString(),
            'supplier_name' => 'Supplier Kain Jaya',
            'created_by' => $this->admin->id,
        ]);

        $productInBatch = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Produk Dalam Batch',
            'slug' => 'produk-dalam-batch',
            'sku' => 'IN-BCH-01',
            'selling_price' => 100000,
            'stock' => 20,
            'status' => 'active',
        ]);

        PurchaseBatchItem::create([
            'purchase_batch_id' => $batch->id,
            'product_id' => $productInBatch->id,
            'quantity' => 20,
            'unit_purchase_price' => 70000,
        ]);

        $productNotInBatch = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Produk Luar Batch',
            'slug' => 'produk-luar-batch',
            'sku' => 'OUT-BCH-01',
            'selling_price' => 80000,
            'stock' => 15,
            'status' => 'active',
        ]);

        $outputPath = storage_path('app/testing_export_batch.xlsx');
        if (file_exists($outputPath)) {
            unlink($outputPath);
        }

        // Export dengan filter batch
        $this->artisan('products:export', [
            '--store' => $this->store->id,
            '--batch' => $batch->batch_code,
            '--path' => $outputPath,
        ])->assertSuccessful();

        $this->assertFileExists($outputPath);

        $reader = IOFactory::createReaderForFile($outputPath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($outputPath);
        $sheet = $spreadsheet->getActiveSheet();

        $rows = $sheet->toArray(null, true, false, false);
        // Header (row 0) + 1 baris data produk dalam batch (row 1)
        $this->assertCount(2, $rows);
        $this->assertEquals('Produk Dalam Batch', $rows[1][1]);

        if (file_exists($outputPath)) {
            unlink($outputPath);
        }
    }

    public function test_import_from_xlsx_file_inserts_and_updates_by_checksum(): void
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

        // Buat file spreadsheet .xlsx menggunakan PhpSpreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['id', 'name', 'slug', 'sku', 'category', 'selling_price', 'weight', 'stock', 'status', 'is_bundle', 'is_hidden', 'description', 'checksum'],
            // Row 2: UPDATE produk eksisting (ID diisi, ubah harga dan stok)
            [$existing->id, 'Kemeja Katun Putih (Updated)', 'kemeja-katun-putih', 'KMJ-001', 'Kemeja Formal', 120000, 260, 30, 'active', 0, 0, 'Kemeja formal baru.', ''],
            // Row 3: INSERT produk baru
            ['', 'Rok Plisket Panjang', '', 'ROK-PLK-01', 'Rok & Celana', 85000, 200, 50, 'active', 0, 0, 'Rok plisket adem.', ''],
        ], null, 'A1');

        $xlsxPath = tempnam(sys_get_temp_dir(), 'test_import_') . '.xlsx';
        $writer = new XlsxWriter($spreadsheet);
        $writer->save($xlsxPath);

        $this->artisan('products:import', [
            'file' => $xlsxPath,
            '--store' => $this->store->id,
            '--batch-size' => 2,
        ])->assertSuccessful();

        $existing->refresh();
        $this->assertEquals('Kemeja Katun Putih (Updated)', $existing->name);
        $this->assertEquals(120000, (int) $existing->selling_price);
        $this->assertEquals(30, $existing->stock);

        $newProduct = Product::forStore($this->store->id)->where('sku', 'ROK-PLK-01')->first();
        $this->assertNotNull($newProduct);
        $this->assertEquals('Rok Plisket Panjang', $newProduct->name);
        $this->assertEquals(50, $newProduct->stock);

        unlink($xlsxPath);
    }

    public function test_import_with_purchase_batch_links_stock_movement_and_batch_items(): void
    {
        $batch = PurchaseBatch::create([
            'store_id' => $this->store->id,
            'batch_code' => 'BCH-20261102-099',
            'purchase_date' => now()->toDateString(),
            'supplier_name' => 'Supplier Impor',
            'created_by' => $this->admin->id,
        ]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['id', 'name', 'sku', 'selling_price', 'stock'],
            ['', 'Blouse Korea Import', 'BLS-KOR-01', 99000, 40],
        ], null, 'A1');

        $xlsxPath = tempnam(sys_get_temp_dir(), 'test_batch_import_') . '.xlsx';
        $writer = new XlsxWriter($spreadsheet);
        $writer->save($xlsxPath);

        $this->artisan('products:import', [
            'file' => $xlsxPath,
            '--store' => $this->store->id,
            '--batch' => $batch->batch_code,
        ])->assertSuccessful();

        $product = Product::forStore($this->store->id)->where('sku', 'BLS-KOR-01')->first();
        $this->assertNotNull($product);
        $this->assertEquals(40, $product->stock);

        // Verifikasi stock movement memiliki purchase_batch_id
        $movement = StockMovement::where('product_id', $product->id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals($batch->id, $movement->purchase_batch_id);

        // Verifikasi PurchaseBatchItem otomatis terbuat
        $batchItem = PurchaseBatchItem::where('purchase_batch_id', $batch->id)
            ->where('product_id', $product->id)
            ->first();
        $this->assertNotNull($batchItem);
        $this->assertEquals(40, $batchItem->quantity);

        unlink($xlsxPath);
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
        $this->assertEquals(1, $res1['unchanged_count']); // Checksum cocok, dilewati tanpa rewrite!

        // 2. Import kedua: update harga_jual ke 135.000 (per-kolom saja)
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
        $this->assertEquals('Baju Tidur Satin', $existing->name);
        $this->assertEquals(250, $existing->weight);
        $this->assertEquals(20, $existing->stock);
    }

    public function test_admin_web_export_and_template_downloads_native_xlsx(): void
    {
        // 1. Template XLSX
        $responseTemplate = $this->actingAs($this->admin)->get(route('admin.products.template', $this->store));
        $responseTemplate->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $responseTemplate->headers->get('Content-Type'));
        $this->assertStringContainsString('template_import_produk.xlsx', $responseTemplate->headers->get('Content-Disposition'));

        Product::create([
            'store_id' => $this->store->id,
            'name' => 'Gamis Syari Elegan',
            'slug' => 'gamis-syari-elegan',
            'selling_price' => 200000,
            'stock' => 12,
            'status' => 'active',
        ]);

        // 2. Export default (.xlsx)
        $responseExport = $this->actingAs($this->admin)->get(
            route('admin.products.export', ['store' => $this->store, 'columns' => 'name,selling_price,stock'])
        );
        $responseExport->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $responseExport->headers->get('Content-Type'));
        $this->assertStringContainsString('products_toko-busana-cantik', $responseExport->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.xlsx', $responseExport->headers->get('Content-Disposition'));
    }

    public function test_admin_web_import_xlsx_ajax_response(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['id', 'name', 'slug', 'sku', 'category', 'selling_price', 'weight', 'stock', 'status', 'is_bundle', 'is_hidden', 'description', 'checksum'],
            ['', 'Jilbab Segi Empat Voal', '', 'JLB-01', 'Hijab', 35000, 100, 100, 'active', 0, 0, 'Bahan voal premium lembut.', ''],
        ], null, 'A1');

        $xlsxTemp = tempnam(sys_get_temp_dir(), 'test_web_upload_') . '.xlsx';
        $writer = new XlsxWriter($spreadsheet);
        $writer->save($xlsxTemp);

        $file = new UploadedFile(
            $xlsxTemp,
            'import_test.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

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

        if (file_exists($xlsxTemp)) {
            unlink($xlsxTemp);
        }
    }
}
