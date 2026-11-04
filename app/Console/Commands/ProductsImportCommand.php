<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\ProductImportExportService;
use Illuminate\Console\Command;

class ProductsImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:import
                            {file : Path ke file CSV produk}
                            {--store= : ID atau Slug toko target}
                            {--batch-size=100 : Jumlah baris produk per transaksi batch (default: 100)}
                            {--dry-run : Validasi dan simulasikan import tanpa menyimpan ke database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data produk dari file CSV per batch (edit produk eksisting atau tambah produk baru)';

    public function handle(ProductImportExportService $service): int
    {
        $file = $this->argument('file');

        // Resolve absolute path if relative
        if (! str_starts_with($file, '/') && ! preg_match('/^[A-Za-z]:\\\\/', $file)) {
            $file = base_path($file);
        }

        if (! file_exists($file) || ! is_readable($file)) {
            $this->error("❌ File tidak ditemukan atau tidak dapat dibaca: {$file}");
            return self::FAILURE;
        }

        $store = $this->resolveStore();
        if (! $store) {
            $this->error('❌ Toko tidak ditemukan.');
            return self::FAILURE;
        }

        $batchSize = (int) $this->option('batch-size');
        if ($batchSize < 1) {
            $batchSize = 100;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->info('🚀 Memulai Proses Import Produk...');
        $this->line("🏪 Toko Target: <comment>{$store->name}</comment> (ID: {$store->id})");
        $this->line("📁 File CSV   : <comment>{$file}</comment>");
        $this->line("📦 Ukuran Batch: <comment>{$batchSize} baris / transaksi</comment>");

        if ($dryRun) {
            $this->warn('⚠️  MODE DRY RUN AKTIF: Tidak ada data yang akan disimpan ke database.');
        }

        // Hitung perkiraan total baris (dikurangi 1 header)
        $lineCount = 0;
        $fh = fopen($file, 'r');
        if ($fh) {
            while (! feof($fh)) {
                $line = fgets($fh);
                if ($line !== false && trim($line) !== '') {
                    $lineCount++;
                }
            }
            fclose($fh);
        }
        $approxDataRows = max(0, $lineCount - 1);
        $approxBatches = (int) ceil(max(1, $approxDataRows) / $batchSize);

        $progressBar = $this->output->createProgressBar($approxBatches);
        $progressBar->setFormat(' Batch %current%/%max% [%bar%] %percent:3s%% -- %message%');
        $progressBar->setMessage('Memproses...');
        $progressBar->start();

        try {
            $result = $service->importFromFile(
                $store,
                $file,
                $batchSize,
                $dryRun,
                null,
                function ($batchIndex, $rowsInBatch, $createdTotal, $updatedTotal, $unchangedTotal) use ($progressBar) {
                    $progressBar->setMessage("Batch #{$batchIndex} ({$rowsInBatch} item) | Dibuat: {$createdTotal}, Diupdate: {$updatedTotal}, Checksum Sama: {$unchangedTotal}");
                    $progressBar->advance();
                }
            );
        } catch (\Throwable $e) {
            $progressBar->finish();
            $this->newLine(2);
            $this->error("❌ Terjadi kegagalan saat proses import: {$e->getMessage()}");
            return self::FAILURE;
        }

        $progressBar->finish();
        $this->newLine(2);

        // Tampilkan Hasil Ringkasan
        $this->info($dryRun ? '🔍 Simulasi Import Selesai (DRY RUN)' : '🎉 Import Produk Selesai!');

        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Toko Target', "{$store->name} (ID: {$store->id})"],
                ['Total Baris CSV Diproses', $result['total_rows']],
                ['Produk Baru Dibuat (Insert)', $result['created_count']],
                ['Produk Diperbarui (Update)', $result['updated_count']],
                ['Produk Tanpa Perubahan (Unchanged by Checksum)', $result['unchanged_count']],
                ['Baris Dilewati / Error', $result['skipped_count']],
                ['Jumlah Batch Transaksi', $result['batches_count']],
                ['Status Eksekusi', $dryRun ? 'SIMULASI (DRY-RUN - Tidak Disimpan)' : 'BERHASIL DISIMPAN'],
            ]
        );

        if (! empty($result['errors'])) {
            $this->newLine();
            $this->warn('⚠️  Catatan / Error yang ditemukan selama proses import:');
            $maxErrorsToShow = 15;
            $displayedErrors = array_slice($result['errors'], 0, $maxErrorsToShow);

            foreach ($displayedErrors as $err) {
                $this->line("  • <fg=red>{$err}</>");
            }

            if (count($result['errors']) > $maxErrorsToShow) {
                $remaining = count($result['errors']) - $maxErrorsToShow;
                $this->line("  ... dan {$remaining} error lainnya.");
            }
        }

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

        $selectedName = $this->choice('Pilih toko target import:', array_values($options));
        $selectedId = array_search($selectedName, $options, true);

        return $stores->firstWhere('id', $selectedId);
    }
}

