<?php

namespace App\Console\Commands;

use App\Models\Category;
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
                            {--path= : Path file tujuan export (misal: storage/app/products.csv)}
                            {--category= : Filter berdasarkan ID atau nama kategori}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export data produk toko ke format file CSV untuk edit atau penambahan per batch';

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

        // 2. Filter Kategori opsional
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

        // 3. Tentukan Path File Output
        $outputPath = $this->option('path');
        if (! $outputPath) {
            $dir = storage_path('app/exports');
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $filename = sprintf('products_%s_%s.csv', $store->slug, date('Ymd_His'));
            $outputPath = $dir . DIRECTORY_SEPARATOR . $filename;
        } else {
            // Jika path relatif, ubah ke absolute path terhadap base path
            if (! str_starts_with($outputPath, '/') && ! preg_match('/^[A-Za-z]:\\\\/', $outputPath)) {
                $outputPath = base_path($outputPath);
            }
        }

        // 4. Hitung total produk untuk progress bar
        $totalQuery = $store->products();
        if ($categoryId) {
            $totalQuery->where('category_id', $categoryId);
        }
        $totalProducts = $totalQuery->count();

        if ($totalProducts === 0) {
            $this->warn('⚠️  Tidak ada produk yang ditemukan untuk diexport.');
        }

        $progressBar = $this->output->createProgressBar($totalProducts);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');
        $progressBar->setMessage('Memulai export...');
        $progressBar->start();

        $count = $service->exportToFile(
            $store,
            $outputPath,
            $categoryId,
            function ($product, $currentCount) use ($progressBar) {
                $progressBar->setMessage(substr($product->name, 0, 30));
                $progressBar->advance();
            }
        );

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Berhasil mengexport {$count} produk!");
        $this->table(
            ['Informasi', 'Detail'],
            [
                ['Toko', "{$store->name} (ID: {$store->id})"],
                ['Total Baris Produk', $count],
                ['Lokasi File', $outputPath],
                ['Ukuran File', file_exists($outputPath) ? number_format(filesize($outputPath) / 1024, 2) . ' KB' : '0 KB'],
            ]
        );

        $this->newLine();
        $this->line('💡 <comment>Tips Import:</comment>');
        $this->line("   Untuk mengedit produk, ubah nilai di file CSV (jangan ubah kolom 'id').");
        $this->line("   Untuk menambah produk baru, tambahkan baris baru dengan mengosongkan kolom 'id'.");
        $this->line("   Jalankan perintah berikut untuk mengimport kembali per batch:");
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

