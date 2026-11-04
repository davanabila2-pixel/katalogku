<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductImportExportService
{
    /**
     * Header CSV standar untuk export dan import produk.
     */
    public const HEADERS = [
        'id',
        'name',
        'slug',
        'sku',
        'category',
        'selling_price',
        'weight',
        'stock',
        'status',
        'is_bundle',
        'is_hidden',
        'description',
    ];

    /**
     * Export produk milik toko ke stream resource (misal php://output atau file pointer).
     *
     * @param resource $stream
     */
    public function exportToStream(Store $store, $stream, ?int $categoryId = null, ?callable $onRowExported = null): int
    {
        // Tulis UTF-8 BOM agar langsung terbaca rapi di Microsoft Excel
        fputs($stream, "\xEF\xBB\xBF");

        // Tulis baris header
        fputcsv($stream, self::HEADERS);

        $query = Product::forStore($store->id)
            ->with('category')
            ->orderBy('id');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $exportedCount = 0;

        $query->chunk(200, function ($products) use ($stream, &$exportedCount, $onRowExported) {
            foreach ($products as $p) {
                $row = [
                    $p->id,
                    $p->name,
                    $p->slug,
                    $p->sku ?? '',
                    $p->category ? $p->category->name : '',
                    (int) round((float) $p->selling_price),
                    (int) ($p->weight ?? 500),
                    (int) $p->stock,
                    $p->status,
                    $p->is_bundle ? '1' : '0',
                    $p->is_hidden_from_catalog ? '1' : '0',
                    $p->description ?? '',
                ];

                fputcsv($stream, $row);
                $exportedCount++;

                if ($onRowExported) {
                    $onRowExported($p, $exportedCount);
                }
            }
        });

        return $exportedCount;
    }

    /**
     * Export produk milik toko langsung ke file di disk.
     */
    public function exportToFile(Store $store, string $filePath, ?int $categoryId = null, ?callable $onRowExported = null): int
    {
        $dir = dirname($filePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $handle = fopen($filePath, 'w');
        if (! $handle) {
            throw new \RuntimeException("Gagal membuka file tujuan untuk menulis: {$filePath}");
        }

        try {
            return $this->exportToStream($store, $handle, $categoryId, $onRowExported);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Import produk dari file CSV ke toko tertentu per batch.
     * Hasil exportan bisa diedit atau ditambah baris baru lalu diimport kembali.
     *
     * @return array{
     *     total_rows: int,
     *     created_count: int,
     *     updated_count: int,
     *     skipped_count: int,
     *     batches_count: int,
     *     errors: array<int, string>,
     *     dry_run: bool
     * }
     */
    public function importFromFile(
        Store $store,
        string $filePath,
        int $batchSize = 100,
        bool $dryRun = false,
        ?int $userId = null,
        ?callable $onBatchProcessed = null
    ): array {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            throw new \InvalidArgumentException("File CSV tidak ditemukan atau tidak dapat dibaca: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if (! $handle) {
            throw new \RuntimeException("Gagal membuka file CSV: {$filePath}");
        }

        try {
            return $this->importFromStream($store, $handle, $batchSize, $dryRun, $userId, $onBatchProcessed);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Import produk dari stream resource per batch (chunk).
     *
     * @param resource $stream
     */
    public function importFromStream(
        Store $store,
        $stream,
        int $batchSize = 100,
        bool $dryRun = false,
        ?int $userId = null,
        ?callable $onBatchProcessed = null
    ): array {
        $batchSize = max(1, min(500, $batchSize));

        // 1. Baca baris pertama untuk header & deteksi delimiter (koma atau titik koma)
        $firstLine = fgets($stream);
        if ($firstLine === false) {
            return [
                'total_rows' => 0,
                'created_count' => 0,
                'updated_count' => 0,
                'skipped_count' => 0,
                'batches_count' => 0,
                'errors' => ['File CSV kosong.'],
                'dry_run' => $dryRun,
            ];
        }

        // Hapus UTF-8 BOM jika ada
        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);

        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        $headerRaw = str_getcsv($firstLine, $delimiter);

        $headerMap = $this->resolveHeaderMap($headerRaw);
        if (empty($headerMap['name'])) {
            return [
                'total_rows' => 0,
                'created_count' => 0,
                'updated_count' => 0,
                'skipped_count' => 0,
                'batches_count' => 0,
                'errors' => ['Kolom nama produk wajib ada pada file CSV (header "name" atau "nama").'],
                'dry_run' => $dryRun,
            ];
        }

        $totalRows = 0;
        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $batchesCount = 0;
        $errors = [];

        $currentBatch = [];
        $lineNumber = 1; // baris 1 adalah header

        while (($row = fgetcsv($stream, 0, $delimiter)) !== false) {
            $lineNumber++;

            // Abaikan baris kosong
            if (empty($row) || (count($row) === 1 && trim((string) $row[0]) === '')) {
                continue;
            }

            $totalRows++;
            $currentBatch[] = [
                'line' => $lineNumber,
                'data' => $row,
            ];

            if (count($currentBatch) >= $batchSize) {
                $batchesCount++;
                $batchResult = $this->processBatch($store, $currentBatch, $headerMap, $dryRun, $userId);
                $createdCount += $batchResult['created'];
                $updatedCount += $batchResult['updated'];
                $skippedCount += $batchResult['skipped'];
                $errors = array_merge($errors, $batchResult['errors']);

                if ($onBatchProcessed) {
                    $onBatchProcessed($batchesCount, count($currentBatch), $createdCount, $updatedCount);
                }

                $currentBatch = [];
            }
        }

        // Proses sisa baris batch terakhir jika ada
        if (! empty($currentBatch)) {
            $batchesCount++;
            $batchResult = $this->processBatch($store, $currentBatch, $headerMap, $dryRun, $userId);
            $createdCount += $batchResult['created'];
            $updatedCount += $batchResult['updated'];
            $skippedCount += $batchResult['skipped'];
            $errors = array_merge($errors, $batchResult['errors']);

            if ($onBatchProcessed) {
                $onBatchProcessed($batchesCount, count($currentBatch), $createdCount, $updatedCount);
            }
        }

        return [
            'total_rows' => $totalRows,
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'skipped_count' => $skippedCount,
            'batches_count' => $batchesCount,
            'errors' => $errors,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * Proses 1 batch data produk dalam DB transaction.
     */
    protected function processBatch(
        Store $store,
        array $batch,
        array $headerMap,
        bool $dryRun,
        ?int $userId
    ): array {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        $operation = function () use ($store, $batch, $headerMap, &$created, &$updated, &$skipped, &$errors, $userId) {
            // Cache kategori di memori untuk toko ini agar hemat query
            $categoryCache = Category::forStore($store->id)->get()->keyBy(fn ($c) => mb_strtolower(trim($c->name)));

            foreach ($batch as $item) {
                $line = $item['line'];
                $row = $item['data'];

                try {
                    $parsed = $this->parseRow($row, $headerMap);

                    if (empty($parsed['name'])) {
                        $skipped++;
                        $errors[] = "Baris {$line}: Nama produk tidak boleh kosong.";
                        continue;
                    }

                    // Resolusi Kategori
                    $categoryId = null;
                    if (! empty($parsed['category'])) {
                        $catKey = mb_strtolower(trim($parsed['category']));
                        if ($categoryCache->has($catKey)) {
                            $categoryId = $categoryCache->get($catKey)->id;
                        } else {
                            $newCat = Category::create([
                                'store_id' => $store->id,
                                'name' => trim($parsed['category']),
                                'slug' => SlugService::uniqueCategorySlug($store->id, $parsed['category']),
                                'is_active' => true,
                            ]);
                            $categoryCache->put($catKey, $newCat);
                            $categoryId = $newCat->id;
                        }
                    }

                    // Logika UPSERT (Edit vs Tambah):
                    // 1. Cek berdasarkan ID jika ID diisi
                    // 2. Jika ID kosong, cek berdasarkan SKU jika SKU diisi
                    // 3. Jika tidak ada yang cocok, TAMBAHKAN produk baru!
                    $product = null;

                    if (! empty($parsed['id']) && is_numeric($parsed['id'])) {
                        $product = Product::forStore($store->id)->where('id', (int) $parsed['id'])->first();
                    }

                    if (! $product && ! empty($parsed['sku'])) {
                        $product = Product::forStore($store->id)->where('sku', trim($parsed['sku']))->first();
                    }

                    if (! $product && ! empty($parsed['slug'])) {
                        $product = Product::forStore($store->id)->where('slug', trim($parsed['slug']))->first();
                    }

                    if ($product) {
                        // MODE EDIT (UPDATE)
                        $this->updateExistingProduct($store, $product, $parsed, $categoryId, $userId);
                        $updated++;
                    } else {
                        // MODE TAMBAH (INSERT)
                        $this->createNewProduct($store, $parsed, $categoryId, $userId);
                        $created++;
                    }
                } catch (\Throwable $e) {
                    $skipped++;
                    $errors[] = "Baris {$line}: Terjadi kesalahan - {$e->getMessage()}";
                    Log::warning("Gagal memproses baris {$line} import produk: {$e->getMessage()}");
                }
            }
        };

        if ($dryRun) {
            try {
                DB::transaction(function () use ($operation) {
                    $operation();
                    throw new \RuntimeException('DRY_RUN_ROLLBACK');
                });
            } catch (\RuntimeException $e) {
                if ($e->getMessage() !== 'DRY_RUN_ROLLBACK') {
                    throw $e;
                }
            }
        } else {
            DB::transaction($operation);
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Perbarui produk yang sudah ada (Mode Edit).
     */
    protected function updateExistingProduct(Store $store, Product $product, array $parsed, ?int $categoryId, ?int $userId): void
    {
        $oldStock = (int) $product->stock;
        $newStock = isset($parsed['stock']) ? (int) $parsed['stock'] : $oldStock;

        $updateData = [
            'name' => $parsed['name'],
            'selling_price' => max(0, (float) ($parsed['selling_price'] ?? $product->selling_price)),
            'weight' => max(1, (int) ($parsed['weight'] ?? $product->weight ?? 500)),
            'status' => in_array($parsed['status'] ?? '', ['active', 'draft', 'inactive'], true) ? $parsed['status'] : $product->status,
        ];

        if (array_key_exists('sku', $parsed)) {
            $updateData['sku'] = ! empty($parsed['sku']) ? trim($parsed['sku']) : null;
        }

        if (array_key_exists('description', $parsed)) {
            $updateData['description'] = $parsed['description'];
        }

        if ($categoryId !== null) {
            $updateData['category_id'] = $categoryId;
        }

        if (isset($parsed['is_bundle'])) {
            $updateData['is_bundle'] = (bool) $parsed['is_bundle'];
        }

        if (isset($parsed['is_hidden'])) {
            $updateData['is_hidden_from_catalog'] = (bool) $parsed['is_hidden'];
        }

        // Jika bukan bundle dan stok berubah, catat movement
        if (! $product->isBundle() && isset($parsed['stock']) && $newStock !== $oldStock) {
            $updateData['stock'] = $newStock;

            $diff = $newStock - $oldStock;
            StockMovement::create([
                'store_id' => $store->id,
                'product_id' => $product->id,
                'movement_type' => 'adjustment',
                'quantity' => $diff,
                'reference_type' => 'import_csv',
                'reference_id' => $product->id,
                'notes' => "Penyesuaian stok dari import CSV ({$oldStock} -> {$newStock})",
                'created_by' => $userId,
                'created_at' => now(),
            ]);
        }

        $product->update($updateData);

        if ($product->isBundle()) {
            $product->syncBundleStock();
        }
    }

    /**
     * Tambahkan produk baru (Mode Insert).
     */
    protected function createNewProduct(Store $store, array $parsed, ?int $categoryId, ?int $userId): Product
    {
        $slug = ! empty($parsed['slug'])
            ? SlugService::uniqueProductSlug($store->id, $parsed['slug'])
            : SlugService::uniqueProductSlug($store->id, $parsed['name']);

        $stock = isset($parsed['stock']) ? max(0, (int) $parsed['stock']) : 0;
        $isBundle = ! empty($parsed['is_bundle']);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $categoryId,
            'name' => $parsed['name'],
            'slug' => $slug,
            'sku' => ! empty($parsed['sku']) ? trim($parsed['sku']) : null,
            'selling_price' => max(0, (float) ($parsed['selling_price'] ?? 0)),
            'weight' => max(1, (int) ($parsed['weight'] ?? 500)),
            'stock' => $stock,
            'status' => in_array($parsed['status'] ?? '', ['active', 'draft', 'inactive'], true) ? $parsed['status'] : 'active',
            'is_bundle' => $isBundle,
            'is_hidden_from_catalog' => ! empty($parsed['is_hidden']),
            'description' => $parsed['description'] ?? null,
        ]);

        // Catat stok awal jika stok > 0
        if (! $isBundle && $stock > 0) {
            StockMovement::create([
                'store_id' => $store->id,
                'product_id' => $product->id,
                'movement_type' => 'in',
                'quantity' => $stock,
                'reference_type' => 'import_csv',
                'reference_id' => $product->id,
                'notes' => 'Stok awal produk baru dari import CSV',
                'created_by' => $userId,
                'created_at' => now(),
            ]);
        }

        return $product;
    }

    /**
     * Petakan posisi kolom header ke nama properti terstandarisasi.
     */
    protected function resolveHeaderMap(array $headerRaw): array
    {
        $map = [];

        foreach ($headerRaw as $index => $colName) {
            $clean = mb_strtolower(trim((string) $colName));
            $clean = str_replace([' ', '-', '_'], '', $clean);

            if (in_array($clean, ['id', 'idproduk', 'productid'], true)) {
                $map['id'] = $index;
            } elseif (in_array($clean, ['name', 'nama', 'namaproduk', 'productname'], true)) {
                $map['name'] = $index;
            } elseif ($clean === 'slug') {
                $map['slug'] = $index;
            } elseif (in_array($clean, ['sku', 'kodeproduk', 'kode'], true)) {
                $map['sku'] = $index;
            } elseif (in_array($clean, ['category', 'kategori', 'categoryname', 'namakategori'], true)) {
                $map['category'] = $index;
            } elseif (in_array($clean, ['sellingprice', 'hargajual', 'harga', 'price'], true)) {
                $map['selling_price'] = $index;
            } elseif (in_array($clean, ['weight', 'berat', 'beratgram', 'weightgrams'], true)) {
                $map['weight'] = $index;
            } elseif (in_array($clean, ['stock', 'stok', 'qty', 'quantity', 'jumlah'], true)) {
                $map['stock'] = $index;
            } elseif ($clean === 'status') {
                $map['status'] = $index;
            } elseif (in_array($clean, ['isbundle', 'bundle', 'setelan', 'paket'], true)) {
                $map['is_bundle'] = $index;
            } elseif (in_array($clean, ['ishidden', 'hidden', 'sembunyi', 'sembunyikan'], true)) {
                $map['is_hidden'] = $index;
            } elseif (in_array($clean, ['description', 'deskripsi', 'keterangan'], true)) {
                $map['description'] = $index;
            }
        }

        return $map;
    }

    /**
     * Ekstrak data 1 baris CSV berdasarkan header map.
     */
    protected function parseRow(array $row, array $map): array
    {
        $get = fn (string $key) => isset($map[$key], $row[$map[$key]]) ? trim((string) $row[$map[$key]]) : null;

        $sellingPrice = $get('selling_price');
        if ($sellingPrice !== null) {
            // Bersihkan format titik/koma ribuan jika user input 'Rp 150.000'
            $cleanPrice = preg_replace('/[^0-9.]/', '', str_replace(',', '.', str_replace('.', '', $sellingPrice)));
            $priceVal = (float) $cleanPrice;
        } else {
            $priceVal = null;
        }

        $weight = $get('weight');
        if ($weight !== null) {
            $cleanWeight = (int) preg_replace('/[^0-9]/', '', $weight);
            $weightVal = max(1, $cleanWeight);
        } else {
            $weightVal = null;
        }

        $stock = $get('stock');
        $stockVal = ($stock !== null && $stock !== '') ? (int) $stock : null;

        $status = mb_strtolower((string) $get('status'));
        if (! in_array($status, ['active', 'draft', 'inactive'], true)) {
            $status = 'active';
        }

        $isBundle = $get('is_bundle');
        $isBundleVal = in_array(mb_strtolower((string) $isBundle), ['1', 'true', 'ya', 'yes'], true);

        $isHidden = $get('is_hidden');
        $isHiddenVal = in_array(mb_strtolower((string) $isHidden), ['1', 'true', 'ya', 'yes'], true);

        return [
            'id' => $get('id'),
            'name' => $get('name'),
            'slug' => $get('slug'),
            'sku' => $get('sku'),
            'category' => $get('category'),
            'selling_price' => $priceVal,
            'weight' => $weightVal,
            'stock' => $stockVal,
            'status' => $status,
            'is_bundle' => $isBundleVal,
            'is_hidden' => $isHiddenVal,
            'description' => $get('description'),
        ];
    }
}
