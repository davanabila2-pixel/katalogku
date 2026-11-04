<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseBatch;
use App\Models\PurchaseBatchItem;
use App\Models\StockMovement;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

class ProductImportExportService
{
    /**
     * Definisi kolom-kolom produk yang dapat diexport/diimport beserta labelnya.
     */
    public const AVAILABLE_COLUMNS = [
        'id' => 'ID Produk',
        'name' => 'Nama Produk',
        'slug' => 'Slug URL',
        'sku' => 'SKU',
        'category' => 'Kategori',
        'selling_price' => 'Harga Jual',
        'weight' => 'Berat (Gram)',
        'stock' => 'Stok',
        'status' => 'Status',
        'is_bundle' => 'Setelan',
        'is_hidden' => 'Tersembunyi',
        'description' => 'Deskripsi',
        'checksum' => 'Checksum',
    ];

    /**
     * Urutan kolom default untuk export lengkap.
     */
    public const DEFAULT_EXPORT_COLUMNS = [
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
        'checksum',
    ];

    /**
     * Backward-compatibility alias HEADERS.
     */
    public const HEADERS = self::DEFAULT_EXPORT_COLUMNS;

    /**
     * Hitung checksum deterministik (12-char hex berawalan ck_) dari nilai-nilai atribut.
     * Mengabaikan 'id', 'slug', dan 'checksum' agar checksum murni merepresentasikan isi data produk.
     */
    public static function calculateChecksum(array $attributes): string
    {
        $normalized = [];
        foreach ($attributes as $key => $val) {
            if (in_array($key, ['id', 'slug', 'checksum'], true)) {
                continue;
            }

            if ($val === null || $val === '') {
                $normalized[$key] = '';
            } elseif (is_bool($val)) {
                $normalized[$key] = $val ? '1' : '0';
            } elseif (is_numeric($val)) {
                $normalized[$key] = (string) (int) round((float) $val);
            } else {
                $normalized[$key] = trim((string) $val);
            }
        }

        ksort($normalized);
        $json = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return 'ck_' . substr(hash('sha256', (string) $json), 0, 12);
    }

    /**
     * Resolusi daftar kolom yang diminta menjadi nama kolom standar yang valid.
     */
    public function resolveExportColumns(?array $requested): array
    {
        if (empty($requested)) {
            return self::DEFAULT_EXPORT_COLUMNS;
        }

        $resolved = [];
        foreach ($requested as $col) {
            $clean = mb_strtolower(trim((string) $col));
            $clean = str_replace([' ', '-', '_', '.'], '', $clean);

            foreach (self::AVAILABLE_COLUMNS as $k => $label) {
                $canonical = str_replace([' ', '-', '_', '.'], '', mb_strtolower($k));
                $labelClean = str_replace([' ', '-', '_', '.'], '', mb_strtolower($label));
                if ($clean === $canonical || $clean === $labelClean) {
                    $resolved[] = $k;
                    break;
                }
            }
        }

        $resolved = array_unique($resolved);
        if (empty($resolved)) {
            return self::DEFAULT_EXPORT_COLUMNS;
        }

        // Kolom 'id' diletakkan di paling depan untuk identifikasi baris
        if (! in_array('id', $resolved, true)) {
            array_unshift($resolved, 'id');
        }

        // Kolom 'checksum' selalu disertakan di paling akhir untuk verifikasi integritas
        if (! in_array('checksum', $resolved, true)) {
            $resolved[] = 'checksum';
        }

        return array_values($resolved);
    }

    /**
     * Ekstrak nilai atribut produk untuk subset kolom tertentu.
     */
    public function extractProductValuesForSubset(Product $product, array $keys): array
    {
        $values = [];
        foreach ($keys as $key) {
            switch ($key) {
                case 'id':
                    $values['id'] = $product->id;
                    break;
                case 'name':
                    $values['name'] = $product->name;
                    break;
                case 'slug':
                    $values['slug'] = $product->slug;
                    break;
                case 'sku':
                    $values['sku'] = $product->sku ?? '';
                    break;
                case 'category':
                    $values['category'] = $product->category ? $product->category->name : '';
                    break;
                case 'selling_price':
                    $values['selling_price'] = (int) round((float) $product->selling_price);
                    break;
                case 'weight':
                    $values['weight'] = (int) ($product->weight ?? 500);
                    break;
                case 'stock':
                    $values['stock'] = (int) $product->stock;
                    break;
                case 'status':
                    $values['status'] = $product->status;
                    break;
                case 'is_bundle':
                    $values['is_bundle'] = $product->is_bundle ? '1' : '0';
                    break;
                case 'is_hidden':
                    $values['is_hidden'] = $product->is_hidden_from_catalog ? '1' : '0';
                    break;
                case 'description':
                    $values['description'] = $product->description ?? '';
                    break;
            }
        }

        return $values;
    }

    /**
     * Export produk milik toko ke instance PhpOffice Spreadsheet (.xlsx native).
     * Setiap atribut ditempatkan pada kolom terpisah (bukan perkotak pemisah teks),
     * dilengkapi header dengan styling profesional, auto-filter, freeze-row, dan auto-size.
     */
    public function exportToXlsx(
        Store $store,
        ?int $categoryId = null,
        ?int $purchaseBatchId = null,
        ?array $columns = null,
        ?callable $onRowExported = null
    ): Spreadsheet {
        $targetColumns = $this->resolveExportColumns($columns);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator(config('app.name', 'Katalog'))
            ->setTitle("Daftar Produk {$store->name}")
            ->setSubject('Export Produk XLSX')
            ->setDescription('Export data produk katalog per-kolom dengan format native Excel dan checksum integritas baris');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Produk');

        // Header Row (Baris 1)
        $sheet->fromArray([$targetColumns], null, 'A1');

        $colCount = count($targetColumns);
        $lastColLetter = Coordinate::stringFromColumnIndex($colCount);
        $headerRange = "A1:{$lastColLetter}1";

        // Styling Header: Rapi, Semi-bold, Fill Slate-100, Border Slate-300, Center vertical
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FF1E293B'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF1F5F9'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCBD5E1'],
                ],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // Filter Query Produk
        $query = Product::forStore($store->id)
            ->with('category')
            ->orderBy('id');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($purchaseBatchId) {
            $query->whereHas('batchItems', function ($q) use ($purchaseBatchId) {
                $q->where('purchase_batch_id', $purchaseBatchId);
            });
        }

        $allAttributeKeys = array_keys(self::AVAILABLE_COLUMNS);
        $currentRow = 2;
        $exportedCount = 0;

        $query->chunk(200, function ($products) use ($sheet, $targetColumns, $allAttributeKeys, &$currentRow, &$exportedCount, $onRowExported) {
            $chunkRows = [];
            foreach ($products as $p) {
                $allValues = $this->extractProductValuesForSubset($p, $allAttributeKeys);
                $allValues['checksum'] = self::calculateChecksum($allValues);

                $row = [];
                foreach ($targetColumns as $colKey) {
                    $row[] = $allValues[$colKey] ?? '';
                }

                $chunkRows[] = $row;
                $exportedCount++;

                if ($onRowExported) {
                    $onRowExported($p, $exportedCount);
                }
            }

            if (! empty($chunkRows)) {
                $sheet->fromArray($chunkRows, null, 'A' . $currentRow);
                $currentRow += count($chunkRows);
            }
        });

        $totalDataRows = $currentRow - 1;

        // Freeze baris 1 agar header tetap terlihat saat scroll ke bawah
        $sheet->freezePane('A2');

        // Pasang AutoFilter jika ada data
        if ($totalDataRows >= 1) {
            $sheet->setAutoFilter("A1:{$lastColLetter}" . max(1, $totalDataRows));
        }

        // Atur tipe sel, format angka, dan auto-size per-kolom
        foreach ($targetColumns as $idx => $colKey) {
            $colLetter = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);

            if ($totalDataRows >= 2) {
                $colRange = "{$colLetter}2:{$colLetter}{$totalDataRows}";
                if (in_array($colKey, ['selling_price', 'weight', 'stock'], true)) {
                    $sheet->getStyle($colRange)->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle($colRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                } elseif ($colKey === 'id') {
                    $sheet->getStyle($colRange)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                    $sheet->getStyle($colRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                } elseif (in_array($colKey, ['sku', 'status', 'is_bundle', 'is_hidden', 'checksum'], true)) {
                    $sheet->getStyle($colRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            }
        }

        return $spreadsheet;
    }

    /**
     * Export produk toko langsung ke file Excel native (.xlsx) di disk.
     */
    public function exportToXlsxFile(
        Store $store,
        string $filePath,
        ?int $categoryId = null,
        ?int $purchaseBatchId = null,
        ?array $columns = null,
        ?callable $onRowExported = null
    ): int {
        $dir = dirname($filePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $spreadsheet = $this->exportToXlsx($store, $categoryId, $purchaseBatchId, $columns, $onRowExported);
        $writer = new XlsxWriter($spreadsheet);
        $writer->save($filePath);

        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        return max(0, $highestRow - 1);
    }

    /**
     * Buat template Excel (.xlsx) dengan kolom rapi dan baris contoh untuk panduan import.
     */
    public function createXlsxTemplate(): Spreadsheet
    {
        $targetColumns = self::DEFAULT_EXPORT_COLUMNS;

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator(config('app.name', 'Katalog'))
            ->setTitle('Template Import Produk')
            ->setSubject('Template Import Produk XLSX');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Produk');

        // Header Row
        $sheet->fromArray([$targetColumns], null, 'A1');

        // Contoh Data
        $sampleRow2 = [
            'name' => 'Kemeja Flannel Lengan Panjang (Edit)',
            'sku' => 'KMG-002',
            'category' => 'Kemeja',
            'selling_price' => 125000,
            'weight' => 350,
            'stock' => 25,
            'status' => 'active',
            'is_bundle' => '0',
            'is_hidden' => '0',
            'description' => 'Kemeja flannel motif kotak-kotak.',
        ];
        $sampleChecksum = self::calculateChecksum($sampleRow2);

        $sampleData = [
            // Contoh 1: Tambah produk baru (id kosong, checksum kosong)
            [
                '', // id kosong = tambah produk baru
                'Baju Kaos Katun Polos',
                'baju-kaos-katun-polos',
                'KAOS-001',
                'Pakaian',
                75000,
                200,
                50,
                'active',
                '0',
                '0',
                'Bahan katun combed 30s adem dan nyaman dipakai.',
                '', // checksum kosong untuk produk baru
            ],
            // Contoh 2: Update produk eksisting (id diisi, checksum diisi)
            [
                1, // id produk eksisting
                'Kemeja Flannel Lengan Panjang (Edit)',
                'kemeja-flannel-lengan-panjang',
                'KMG-002',
                'Kemeja',
                125000,
                350,
                25,
                'active',
                '0',
                '0',
                'Kemeja flannel motif kotak-kotak.',
                $sampleChecksum,
            ],
        ];

        $sheet->fromArray($sampleData, null, 'A2');

        $colCount = count($targetColumns);
        $lastColLetter = Coordinate::stringFromColumnIndex($colCount);
        $headerRange = "A1:{$lastColLetter}1";

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FF1E293B'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF1F5F9'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCBD5E1'],
                ],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColLetter}3");

        foreach ($targetColumns as $idx => $colKey) {
            $colLetter = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);

            $colRange = "{$colLetter}2:{$colLetter}3";
            if (in_array($colKey, ['selling_price', 'weight', 'stock'], true)) {
                $sheet->getStyle($colRange)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle($colRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            } elseif ($colKey === 'id') {
                $sheet->getStyle($colRange)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                $sheet->getStyle($colRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        return $spreadsheet;
    }

    /**
     * Export produk milik toko ke stream resource dengan pilihan kolom dan checksum (Format CSV).
     *
     * @param resource $stream
     */
    public function exportToStream(
        Store $store,
        $stream,
        ?int $categoryId = null,
        ?array $columns = null,
        ?callable $onRowExported = null,
        ?int $purchaseBatchId = null
    ): int {
        $targetColumns = $this->resolveExportColumns($columns);

        // Tulis UTF-8 BOM agar langsung terbaca rapi di Microsoft Excel
        fputs($stream, "\xEF\xBB\xBF");

        // Tulis baris header
        fputcsv($stream, $targetColumns);

        $query = Product::forStore($store->id)
            ->with('category')
            ->orderBy('id');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($purchaseBatchId) {
            $query->whereHas('batchItems', function ($q) use ($purchaseBatchId) {
                $q->where('purchase_batch_id', $purchaseBatchId);
            });
        }

        $exportedCount = 0;
        $allAttributeKeys = array_keys(self::AVAILABLE_COLUMNS);

        $query->chunk(200, function ($products) use ($stream, $targetColumns, $allAttributeKeys, &$exportedCount, $onRowExported) {
            foreach ($products as $p) {
                $allValues = $this->extractProductValuesForSubset($p, $allAttributeKeys);
                $allValues['checksum'] = self::calculateChecksum($allValues);

                $row = [];
                foreach ($targetColumns as $colKey) {
                    $row[] = $allValues[$colKey] ?? '';
                }

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
     * Export produk milik toko langsung ke file di disk (Format CSV).
     */
    public function exportToFile(
        Store $store,
        string $filePath,
        ?int $categoryId = null,
        ?array $columns = null,
        ?callable $onRowExported = null,
        ?int $purchaseBatchId = null
    ): int {
        $dir = dirname($filePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $handle = fopen($filePath, 'w');
        if (! $handle) {
            throw new \RuntimeException("Gagal membuka file tujuan untuk menulis: {$filePath}");
        }

        try {
            return $this->exportToStream($store, $handle, $categoryId, $columns, $onRowExported, $purchaseBatchId);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Import produk dari file Excel (.xlsx / .xls) atau CSV ke toko tertentu per batch.
     * Otomatis mengenali tipe file dan memproses data per-kolom serta deteksi checksum.
     *
     * @return array{
     *     total_rows: int,
     *     created_count: int,
     *     updated_count: int,
     *     unchanged_count: int,
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
        ?int $purchaseBatchId = null,
        ?callable $onBatchProcessed = null
    ): array {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            throw new \InvalidArgumentException("File import tidak ditemukan atau tidak dapat dibaca: {$filePath}");
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $isSpreadsheet = in_array($ext, ['xlsx', 'xls', 'ods'], true);

        if (! $isSpreadsheet) {
            try {
                $identified = IOFactory::identify($filePath);
                if (in_array($identified, ['Xlsx', 'Xls', 'Ods'], true)) {
                    $isSpreadsheet = true;
                }
            } catch (\Throwable $e) {
                $isSpreadsheet = false;
            }
        }

        if ($isSpreadsheet) {
            return $this->importFromSpreadsheet(
                $store,
                $filePath,
                $batchSize,
                $dryRun,
                $userId,
                $purchaseBatchId,
                $onBatchProcessed
            );
        }

        $handle = fopen($filePath, 'r');
        if (! $handle) {
            throw new \RuntimeException("Gagal membuka file: {$filePath}");
        }

        try {
            return $this->importFromStream(
                $store,
                $handle,
                $batchSize,
                $dryRun,
                $userId,
                $purchaseBatchId,
                $onBatchProcessed
            );
        } finally {
            fclose($handle);
        }
    }

    /**
     * Import produk langsung dari file spreadsheet (.xlsx / .xls) per batch.
     */
    public function importFromSpreadsheet(
        Store $store,
        string $filePath,
        int $batchSize = 100,
        bool $dryRun = false,
        ?int $userId = null,
        ?int $purchaseBatchId = null,
        ?callable $onBatchProcessed = null
    ): array {
        $batchSize = max(1, min(500, $batchSize));

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $rows = $sheet->toArray(null, true, false, false);
        if (empty($rows)) {
            return [
                'total_rows' => 0,
                'created_count' => 0,
                'updated_count' => 0,
                'unchanged_count' => 0,
                'skipped_count' => 0,
                'batches_count' => 0,
                'errors' => ['File spreadsheet kosong.'],
                'dry_run' => $dryRun,
            ];
        }

        $headerRaw = array_shift($rows);
        $headerMap = $this->resolveHeaderMap($headerRaw);

        if (empty($headerMap)) {
            return [
                'total_rows' => 0,
                'created_count' => 0,
                'updated_count' => 0,
                'unchanged_count' => 0,
                'skipped_count' => 0,
                'batches_count' => 0,
                'errors' => ['File spreadsheet tidak memiliki kolom produk yang dikenali.'],
                'dry_run' => $dryRun,
            ];
        }

        $totalRows = 0;
        $createdCount = 0;
        $updatedCount = 0;
        $unchangedCount = 0;
        $skippedCount = 0;
        $batchesCount = 0;
        $errors = [];

        $currentBatch = [];
        $lineNumber = 1; // Baris 1 adalah header

        foreach ($rows as $row) {
            $lineNumber++;

            // Abaikan baris yang seluruh selnya kosong
            $allEmpty = true;
            foreach ($row as $cell) {
                if ($cell !== null && trim((string) $cell) !== '') {
                    $allEmpty = false;
                    break;
                }
            }
            if ($allEmpty) {
                continue;
            }

            $totalRows++;
            $currentBatch[] = [
                'line' => $lineNumber,
                'data' => $row,
            ];

            if (count($currentBatch) >= $batchSize) {
                $batchesCount++;
                $batchResult = $this->processBatch($store, $currentBatch, $headerMap, $dryRun, $userId, $purchaseBatchId);
                $createdCount += $batchResult['created'];
                $updatedCount += $batchResult['updated'];
                $unchangedCount += $batchResult['unchanged'];
                $skippedCount += $batchResult['skipped'];
                $errors = array_merge($errors, $batchResult['errors']);

                if ($onBatchProcessed) {
                    $onBatchProcessed($batchesCount, count($currentBatch), $createdCount, $updatedCount, $unchangedCount);
                }

                $currentBatch = [];
            }
        }

        if (! empty($currentBatch)) {
            $batchesCount++;
            $batchResult = $this->processBatch($store, $currentBatch, $headerMap, $dryRun, $userId, $purchaseBatchId);
            $createdCount += $batchResult['created'];
            $updatedCount += $batchResult['updated'];
            $unchangedCount += $batchResult['unchanged'];
            $skippedCount += $batchResult['skipped'];
            $errors = array_merge($errors, $batchResult['errors']);

            if ($onBatchProcessed) {
                $onBatchProcessed($batchesCount, count($currentBatch), $createdCount, $updatedCount, $unchangedCount);
            }
        }

        return [
            'total_rows' => $totalRows,
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'unchanged_count' => $unchangedCount,
            'skipped_count' => $skippedCount,
            'batches_count' => $batchesCount,
            'errors' => $errors,
            'dry_run' => $dryRun,
        ];
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
        ?int $purchaseBatchId = null,
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
                'unchanged_count' => 0,
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
        if (empty($headerMap)) {
            return [
                'total_rows' => 0,
                'created_count' => 0,
                'updated_count' => 0,
                'unchanged_count' => 0,
                'skipped_count' => 0,
                'batches_count' => 0,
                'errors' => ['File CSV tidak memiliki kolom produk yang dikenali.'],
                'dry_run' => $dryRun,
            ];
        }

        $totalRows = 0;
        $createdCount = 0;
        $updatedCount = 0;
        $unchangedCount = 0;
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
                $batchResult = $this->processBatch($store, $currentBatch, $headerMap, $dryRun, $userId, $purchaseBatchId);
                $createdCount += $batchResult['created'];
                $updatedCount += $batchResult['updated'];
                $unchangedCount += $batchResult['unchanged'];
                $skippedCount += $batchResult['skipped'];
                $errors = array_merge($errors, $batchResult['errors']);

                if ($onBatchProcessed) {
                    $onBatchProcessed($batchesCount, count($currentBatch), $createdCount, $updatedCount, $unchangedCount);
                }

                $currentBatch = [];
            }
        }

        // Proses sisa baris batch terakhir jika ada
        if (! empty($currentBatch)) {
            $batchesCount++;
            $batchResult = $this->processBatch($store, $currentBatch, $headerMap, $dryRun, $userId, $purchaseBatchId);
            $createdCount += $batchResult['created'];
            $updatedCount += $batchResult['updated'];
            $unchangedCount += $batchResult['unchanged'];
            $skippedCount += $batchResult['skipped'];
            $errors = array_merge($errors, $batchResult['errors']);

            if ($onBatchProcessed) {
                $onBatchProcessed($batchesCount, count($currentBatch), $createdCount, $updatedCount, $unchangedCount);
            }
        }

        return [
            'total_rows' => $totalRows,
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'unchanged_count' => $unchangedCount,
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
        ?int $userId,
        ?int $purchaseBatchId = null
    ): array {
        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $skipped = 0;
        $errors = [];

        $operation = function () use ($store, $batch, $headerMap, &$created, &$updated, &$unchanged, &$skipped, &$errors, $userId, $purchaseBatchId) {
            // Cache kategori di memori untuk toko ini agar hemat query
            $categoryCache = Category::forStore($store->id)->get()->keyBy(fn ($c) => mb_strtolower(trim($c->name)));

            foreach ($batch as $item) {
                $line = $item['line'];
                $row = $item['data'];

                try {
                    $parsed = $this->parseRow($row, $headerMap);
                    if (empty($parsed)) {
                        $skipped++;
                        $errors[] = "Baris {$line}: Baris kosong atau tidak memiliki data yang valid.";
                        continue;
                    }

                    // Resolusi Kategori jika kolom category ada
                    $categoryId = null;
                    if (array_key_exists('category', $parsed) && ! empty($parsed['category'])) {
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
                    // 3. Jika ID/SKU tidak cocok, cek slug jika slug diisi
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
                        // MODE EDIT (UPDATE) per-kolom dengan deteksi checksum
                        $status = $this->updateExistingProduct($store, $product, $parsed, $categoryId, $userId, $purchaseBatchId);
                        if ($status === 'unchanged') {
                            $unchanged++;
                        } else {
                            $updated++;
                        }
                    } else {
                        // MODE TAMBAH (INSERT)
                        if (empty($parsed['name'])) {
                            $skipped++;
                            $errors[] = "Baris {$line}: Produk baru harus memiliki nama produk (kolom 'name' atau 'nama').";
                            continue;
                        }

                        $this->createNewProduct($store, $parsed, $categoryId, $userId, $purchaseBatchId);
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
            'unchanged' => $unchanged,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Perbarui produk yang sudah ada secara parsial per-kolom dengan verifikasi checksum.
     *
     * @return 'updated'|'unchanged'
     */
    protected function updateExistingProduct(
        Store $store,
        Product $product,
        array $parsed,
        ?int $categoryId,
        ?int $userId,
        ?int $purchaseBatchId = null
    ): string {
        // 1. Ekstrak nilai database saat ini untuk kolom-kolom yang ada pada baris import
        $keysToCompare = array_diff(array_keys($parsed), ['id', 'checksum']);
        if (empty($keysToCompare)) {
            return 'unchanged';
        }

        $currentDbValues = $this->extractProductValuesForSubset($product, $keysToCompare);

        // Hitung checksum data DB vs data baris yang diimport
        $dbChecksum = self::calculateChecksum($currentDbValues);
        $importedChecksum = self::calculateChecksum($parsed);

        // Jika checksum sama persis, data baris tidak mengalami perubahan (Unchanged)
        if ($dbChecksum === $importedChecksum) {
            return 'unchanged';
        }

        // 2. Ada perubahan! Tentukan kolom mana saja yang berubah (dirty)
        $dirty = [];
        $oldStock = (int) $product->stock;
        $newStock = $oldStock;
        $stockModified = false;

        if (array_key_exists('name', $parsed) && trim((string) $parsed['name']) !== '') {
            if (trim((string) $parsed['name']) !== trim((string) $product->name)) {
                $dirty['name'] = trim((string) $parsed['name']);
            }
        }

        if (array_key_exists('selling_price', $parsed) && $parsed['selling_price'] !== null) {
            $newPrice = max(0, (float) $parsed['selling_price']);
            if ((int) round($newPrice) !== (int) round((float) $product->selling_price)) {
                $dirty['selling_price'] = $newPrice;
            }
        }

        if (array_key_exists('weight', $parsed) && $parsed['weight'] !== null) {
            $newWeight = max(1, (int) $parsed['weight']);
            if ($newWeight !== (int) ($product->weight ?? 500)) {
                $dirty['weight'] = $newWeight;
            }
        }

        if (array_key_exists('stock', $parsed) && $parsed['stock'] !== null && ! $product->isBundle()) {
            $valStock = max(0, (int) $parsed['stock']);
            if ($valStock !== $oldStock) {
                $newStock = $valStock;
                $dirty['stock'] = $newStock;
                $stockModified = true;
            }
        }

        if (array_key_exists('sku', $parsed)) {
            $cleanSku = trim((string) $parsed['sku']);
            $cleanSku = $cleanSku !== '' ? $cleanSku : null;
            if ($cleanSku !== $product->sku) {
                $dirty['sku'] = $cleanSku;
            }
        }

        if ($categoryId !== null && $categoryId !== $product->category_id) {
            $dirty['category_id'] = $categoryId;
        }

        if (array_key_exists('status', $parsed) && ! empty($parsed['status'])) {
            if ($parsed['status'] !== $product->status) {
                $dirty['status'] = $parsed['status'];
            }
        }

        if (array_key_exists('is_bundle', $parsed)) {
            $bVal = (bool) $parsed['is_bundle'];
            if ($bVal !== (bool) $product->is_bundle) {
                $dirty['is_bundle'] = $bVal;
            }
        }

        if (array_key_exists('is_hidden', $parsed)) {
            $hVal = (bool) $parsed['is_hidden'];
            if ($hVal !== (bool) $product->is_hidden_from_catalog) {
                $dirty['is_hidden_from_catalog'] = $hVal;
            }
        }

        if (array_key_exists('description', $parsed)) {
            $cleanDesc = (string) $parsed['description'];
            if ($cleanDesc !== (string) ($product->description ?? '')) {
                $dirty['description'] = $cleanDesc;
            }
        }

        if (empty($dirty)) {
            return 'unchanged';
        }

        $product->update($dirty);

        if ($stockModified) {
            $diff = $newStock - $oldStock;
            StockMovement::create([
                'store_id' => $store->id,
                'product_id' => $product->id,
                'purchase_batch_id' => $purchaseBatchId,
                'movement_type' => 'adjustment',
                'quantity' => $diff,
                'reference_type' => 'import',
                'reference_id' => $purchaseBatchId ?: $product->id,
                'notes' => "Penyesuaian stok dari import ({$oldStock} -> {$newStock})" . ($purchaseBatchId ? " [Batch #{$purchaseBatchId}]" : ''),
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            if ($purchaseBatchId && $diff > 0) {
                PurchaseBatchItem::firstOrCreate(
                    [
                        'purchase_batch_id' => $purchaseBatchId,
                        'product_id' => $product->id,
                    ],
                    [
                        'quantity' => $diff,
                        'unit_purchase_price' => 0,
                        'allocated_additional_cost' => 0,
                        'notes' => 'Import stok produk',
                    ]
                );
            }
        }

        if ($product->isBundle()) {
            $product->syncBundleStock();
        }

        return 'updated';
    }

    /**
     * Tambahkan produk baru (Mode Insert).
     */
    protected function createNewProduct(
        Store $store,
        array $parsed,
        ?int $categoryId,
        ?int $userId,
        ?int $purchaseBatchId = null
    ): Product {
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
                'purchase_batch_id' => $purchaseBatchId,
                'movement_type' => 'in',
                'quantity' => $stock,
                'reference_type' => 'import',
                'reference_id' => $purchaseBatchId ?: $product->id,
                'notes' => 'Stok awal produk baru dari import' . ($purchaseBatchId ? " [Batch #{$purchaseBatchId}]" : ''),
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            if ($purchaseBatchId) {
                PurchaseBatchItem::firstOrCreate(
                    [
                        'purchase_batch_id' => $purchaseBatchId,
                        'product_id' => $product->id,
                    ],
                    [
                        'quantity' => $stock,
                        'unit_purchase_price' => 0,
                        'allocated_additional_cost' => 0,
                        'notes' => 'Import produk baru ke batch',
                    ]
                );
            }
        }

        return $product;
    }

    /**
     * Petakan posisi kolom header ke nama properti terstandarisasi.
     */
    public function resolveHeaderMap(array $headerRaw): array
    {
        $map = [];

        foreach ($headerRaw as $index => $colName) {
            $clean = mb_strtolower(trim((string) $colName));
            $clean = str_replace([' ', '-', '_', '.', '/'], '', $clean);

            if (in_array($clean, ['id', 'idproduk', 'productid', '#'], true)) {
                $map['id'] = $index;
            } elseif (in_array($clean, ['name', 'nama', 'namaproduk', 'productname', 'item', 'produk'], true)) {
                $map['name'] = $index;
            } elseif ($clean === 'slug') {
                $map['slug'] = $index;
            } elseif (in_array($clean, ['sku', 'kodeproduk', 'kode', 'barcode', 'productcode'], true)) {
                $map['sku'] = $index;
            } elseif (in_array($clean, ['category', 'kategori', 'categoryname', 'namakategori', 'kelompok'], true)) {
                $map['category'] = $index;
            } elseif (in_array($clean, ['sellingprice', 'hargajual', 'harga', 'price', 'hargasatuan'], true)) {
                $map['selling_price'] = $index;
            } elseif (in_array($clean, ['weight', 'berat', 'beratgram', 'weightgrams', 'bobot'], true)) {
                $map['weight'] = $index;
            } elseif (in_array($clean, ['stock', 'stok', 'qty', 'quantity', 'jumlah', 'saldo'], true)) {
                $map['stock'] = $index;
            } elseif (in_array($clean, ['status', 'kondisi'], true)) {
                $map['status'] = $index;
            } elseif (in_array($clean, ['isbundle', 'bundle', 'setelan', 'paket'], true)) {
                $map['is_bundle'] = $index;
            } elseif (in_array($clean, ['ishidden', 'hidden', 'sembunyi', 'sembunyikan', 'tersembunyi'], true)) {
                $map['is_hidden'] = $index;
            } elseif (in_array($clean, ['description', 'deskripsi', 'keterangan', 'detail', 'catatan'], true)) {
                $map['description'] = $index;
            } elseif (in_array($clean, ['checksum', 'hash', 'ceksum', 'chk'], true)) {
                $map['checksum'] = $index;
            }
        }

        return $map;
    }

    /**
     * Ekstrak data 1 baris CSV berdasarkan header map.
     * Hanya mengisi kunci-kunci yang memang ada pada header file CSV (partial column support).
     */
    protected function parseRow(array $row, array $map): array
    {
        $has = fn (string $key) => isset($map[$key]) && array_key_exists($map[$key], $row);
        $get = fn (string $key) => $has($key) ? trim((string) $row[$map[$key]]) : null;

        $parsed = [];

        if ($has('id')) {
            $parsed['id'] = $get('id');
        }

        if ($has('name')) {
            $parsed['name'] = $get('name');
        }

        if ($has('slug')) {
            $parsed['slug'] = $get('slug');
        }

        if ($has('sku')) {
            $parsed['sku'] = $get('sku');
        }

        if ($has('category')) {
            $parsed['category'] = $get('category');
        }

        if ($has('selling_price')) {
            $sellingPrice = $get('selling_price');
            if ($sellingPrice !== null && $sellingPrice !== '') {
                $cleanPrice = preg_replace('/[^0-9.]/', '', str_replace(',', '.', str_replace('.', '', $sellingPrice)));
                $parsed['selling_price'] = (float) $cleanPrice;
            }
        }

        if ($has('weight')) {
            $weight = $get('weight');
            if ($weight !== null && $weight !== '') {
                $parsed['weight'] = max(1, (int) preg_replace('/[^0-9]/', '', $weight));
            }
        }

        if ($has('stock')) {
            $stock = $get('stock');
            if ($stock !== null && $stock !== '') {
                $parsed['stock'] = (int) $stock;
            }
        }

        if ($has('status')) {
            $status = mb_strtolower((string) $get('status'));
            if (in_array($status, ['active', 'draft', 'inactive'], true)) {
                $parsed['status'] = $status;
            }
        }

        if ($has('is_bundle')) {
            $isBundle = $get('is_bundle');
            $parsed['is_bundle'] = in_array(mb_strtolower((string) $isBundle), ['1', 'true', 'ya', 'yes'], true);
        }

        if ($has('is_hidden')) {
            $isHidden = $get('is_hidden');
            $parsed['is_hidden'] = in_array(mb_strtolower((string) $isHidden), ['1', 'true', 'ya', 'yes'], true);
        }

        if ($has('description')) {
            $parsed['description'] = $get('description');
        }

        if ($has('checksum')) {
            $parsed['checksum'] = $get('checksum');
        }

        return $parsed;
    }
}
