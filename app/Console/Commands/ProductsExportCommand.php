<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\PurchaseBatch;
use App\Models\Store;
use App\Services\ProductImportExportService;
use Illuminate\Console\Command;

class ProductsExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:export
                            {--store= : ID atau Slug toko yang akan diexport}
                            {--path= : Path file tujuan export (misal: storage/app/products.xlsx)}
                            {--format=xlsx : Format file export: xlsx (default) atau csv}
                            {--batch= : Filter berdasarkan ID atau Kode Batch pembelian (opsional)}
                            {--category= : Filter berdasarkan ID atau nama kategori (opsional)}
                            {--columns= : Pilih kolom yang ingin diexport, pisahkan koma (contoh: name,selling_price,stock)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export data produk toko ke format file native Excel (.xlsx) per-kolom atau CSV (dengan checksum integritas baris)';

    public function handle(ProductImportExportService $service): int
    {
        $this->info('📦 Menyiapkan Export Data Produk...');

        // 1. Tentukan Toko
        $store = $this->resolveStore();
        if (! $store) {
            $this->error('❌ Toko tidak ditemukan.');
            return self::FAILURE;
        }

        $this->line("🏪 Toko: <comment>{$store->name}</comment> (ID: {$store->id}, Slug: {$store->slug})");

        // 2. Format export (xlsx default, atau csv)
        $format = mb_strtolower((string) $this->option('format'));
        if (! in_array($format, ['xlsx', 'csv'], true)) {
            $this->warn("⚠️  Format '{$format}' tidak valid. Menggunakan format default: xlsx.");
            $format = 'xlsx';
        }

        // 3. Filter Batch Pembelian opsional
        $purchaseBatchId = null;
        $batch = null;
        $batchInput = $this->option('batch');
        if ($batchInput) {
            $batch = PurchaseBatch::where('store_id', $store->id)
                ->where(function ($q) use ($batchInput) {
                    if (is_numeric($batchInput)) {
                        $q->where('id', (int) $batchInput);
                    } else {
                        $q->where('batch_code', $batchInput);
                    }
                })->first();

            if (! $batch) {
                $this->error("❌ Batch pembelian '{$batchInput}' tidak ditemukan pada toko ini.");
                return self::FAILURE;
            }
            $purchaseBatchId = $batch->id;
            $this->line("📦 Filter Batch Pembelian: <comment>{$batch->batch_code}</comment> ({$batch->purchase_date?->format('d/m/Y')} - {$batch->supplier_name})");
        }

        // 4. Filter Kategori opsional
        $categoryId = null;
        $categoryInput = $this->option('category');
        if ($categoryInput) {
            $category = Category::forStore($store->id)
                ->where(function ($q) use ($categoryInput) {
                    if (is_numeric($categoryInput)) {
                        $q->where('id', (int) $categoryInput);
                    } else {
                        $q->where('name', $categoryInput)->orWhere('slug', $categoryInput);
                    }
                })->first();

            if (! $category) {
                $this->error("❌ Kategori '{$categoryInput}' tidak ditemukan pada toko ini.");
                return self::FAILURE;
            }
            $categoryId = $category->id;
            $this->line("🏷️  Filter Kategori: <comment>{$category->name}</comment>");
        }

        // 5. Kolom yang dipilih
        $columnsInput = $this->option('columns');
        $columns = null;
        if ($columnsInput) {
            $columns = array_filter(array_map('trim', explode(',', $columnsInput)));
        }

        // 6. Tentukan Path File Output
        $outputPath = $this->option('path');
        $batchPart = $batch ? '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $batch->batch_code) : '';

        if (! $outputPath) {
            $dir = storage_path('app/exports');
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $filename = sprintf('products_%s%s_%s.%s', $store->slug, $batchPart, date('Ymd_His'), $format);
            $outputPath = $dir . DIRECTORY_SEPARATOR . $filename;
        } else {
            // Jika path relatif, ubah ke absolute path terhadap base path
            if (! str_starts_with($outputPath, '/') && ! preg_match('/^[A-Za-z]:\\\\/', $outputPath)) {
                $outputPath = base_path($outputPath);
            }
            // Auto deteksi format dari ekstensi file jika di-override
            $fileExt = mb_strtolower(pathinfo($outputPath, PATHINFO_EXTENSION));
            if (in_array($fileExt, ['xlsx', 'csv'], true)) {
                $format = $fileExt;
            }
        }

        // 7. Hitung total produk untuk progress bar
        $totalQuery = $store->products();
        if ($categoryId) {
            $totalQuery->where('category_id', $categoryId);
        }
        if ($purchaseBatchId) {
            $totalQuery->whereHas('batchItems', fn ($q) => $q->where('purchase_batch_id', $purchaseBatchId));
        }
        $totalProducts = $totalQuery->count();

        if ($totalProducts === 0) {
            $this->warn('⚠️  Tidak ada produk yang memenuhi kriteria untuk diexport.');
        }

        $progressBar = $this->output->createProgressBar($totalProducts);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');
        $progressBar->setMessage('Memulai export (' . strtoupper($format) . ')...');
        $progressBar->start();

        $rowCallback = function ($product, $currentCount) use ($progressBar) {
            $progressBar->setMessage(substr($product->name, 0, 30));
            $progressBar->advance();
        };

        if ($format === 'csv') {
            $count = $service->exportToFile(
                $store,
                $outputPath,
                $categoryId,
                $columns,
                $rowCallback,
                $purchaseBatchId
            );
        } else {
            $count = $service->exportToXlsxFile(
                $store,
                $outputPath,
                $categoryId,
                $purchaseBatchId,
                $columns,
                $rowCallback
            );
        }

        $progressBar->finish();
        $this->newLine(2);

        $activeColumns = $service->resolveExportColumns($columns);

        $this->info("✅ Berhasil mengexport {$count} produk ke format " . strtoupper($format) . "!");
        $this->table(
            ['Informasi', 'Detail'],
            [
                ['Toko', "{$store->name} (ID: {$store->id})"],
                ['Format', strtoupper($format) . ($format === 'xlsx' ? ' (Native Excel Spreadsheet)' : ' (CSV Separated)')],
                ['Filter Batch', $batch ? "{$batch->batch_code} (ID: {$batch->id})" : 'Semua Batch'],
                ['Total Baris Produk', $count],
                ['Kolom Terpilih (' . count($activeColumns) . ')', implode(', ', $activeColumns)],
                ['Lokasi File', $outputPath],
                ['Ukuran File', file_exists($outputPath) ? number_format(filesize($outputPath) / 1024, 2) . ' KB' : '0 KB'],
            ]
        );

        $this->newLine();
        $this->line('💡 <comment>Tips Import & Checksum:</comment>');
        $this->line("   • Kolom 'checksum' otomatis disertakan untuk verifikasi perubahan data baris.");
        $this->line("   • Baris yang tidak diubah akan dilewati (unchanged) tanpa query pembaruan.");
        $this->line("   • File hasil export dapat diedit langsung di Microsoft Excel dan diimport kembali per-kolom.");
        $this->line("   • Jalankan perintah berikut untuk mengimport kembali per batch:");
        $this->line("   <info>php artisan products:import \"{$outputPath}\" --store={$store->id} --batch-size=100</info>");

        return self::SUCCESS;
    }

    /**
     * Resolusi objek Store dari argumen atau prompt interaktif.
     */
    protected function resolveStore(): ?Store
    {
        $storeInput = $this->option('store');

        if ($storeInput) {
            return Store::query()
                ->where('id', is_numeric($storeInput) ? (int) $storeInput : 0)
                ->orWhere('slug', $storeInput)
                ->orWhere('name', $storeInput)
                ->first();
        }

        $stores = Store::orderBy('name')->get();
        if ($stores->isEmpty()) {
            return null;
        }

        if ($stores->count() === 1) {
            return $stores->first();
        }

        $options = $stores->mapWithKeys(function ($s) {
            return [$s->id => "{$s->name} (slug: {$s->slug}, id: {$s->id})"];
        })->toArray();

        $selectedName = $this->choice('Pilih toko yang produknya ingin diexport:', array_values($options));
        $selectedId = array_search($selectedName, $options, true);

        return $stores->firstWhere('id', $selectedId);
    }
}
