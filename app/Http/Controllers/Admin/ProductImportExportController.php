<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseBatch;
use App\Models\Store;
use App\Services\ProductImportExportService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImportExportController extends Controller
{
    /**
     * Export data produk toko ke format native Excel (.xlsx) per-kolom atau CSV.
     * Mendukung pilihan kolom tertentu, filter batch pembelian, dan dilengkapi checksum integritas per baris.
     */
    public function export(Request $request, Store $store, ProductImportExportService $service): StreamedResponse
    {
        $this->authorize('viewAny', [Product::class, $store->id]);

        $categoryId = $request->query('category_id') ? (int) $request->query('category_id') : null;
        $batchId = $request->query('batch_id') ? (int) $request->query('batch_id') : null;
        $format = mb_strtolower((string) $request->query('format', 'xlsx'));

        $batch = null;
        if ($batchId) {
            $batch = PurchaseBatch::where('store_id', $store->id)->find($batchId);
        }

        $batchPart = $batch ? '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $batch->batch_code) : '';

        $columns = null;
        if ($request->filled('columns')) {
            $rawCols = $request->query('columns');
            $columns = is_array($rawCols) ? $rawCols : explode(',', (string) $rawCols);
        }

        // Format CSV (opsional bila diminta spesifik)
        if ($format === 'csv') {
            $filename = sprintf('products_%s%s_%s.csv', $store->slug, $batchPart, date('Ymd_His'));

            return response()->streamDownload(function () use ($store, $categoryId, $columns, $batch, $service) {
                $handle = fopen('php://output', 'w');
                $service->exportToStream($store, $handle, $categoryId, $columns, null, $batch?->id);
                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache',
            ]);
        }

        // Default: Format native Excel (.xlsx) rapi per-kolom tanpa pemisah teks
        $filename = sprintf('products_%s%s_%s.xlsx', $store->slug, $batchPart, date('Ymd_His'));
        $spreadsheet = $service->exportToXlsx($store, $categoryId, $batch?->id, $columns);
        $writer = new XlsxWriter($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-store, no-cache',
        ]);
    }

    /**
     * Download template Excel (.xlsx) atau CSV kosong dengan baris contoh dan checksum untuk panduan pengisian.
     */
    public function template(Request $request, Store $store, ProductImportExportService $service): StreamedResponse
    {
        $this->authorize('viewAny', [Product::class, $store->id]);

        $format = mb_strtolower((string) $request->query('format', 'xlsx'));

        if ($format === 'csv') {
            $filename = 'template_import_produk.csv';

            return response()->streamDownload(function () {
                $handle = fopen('php://output', 'w');
                fputs($handle, "\xEF\xBB\xBF");
                fputcsv($handle, ProductImportExportService::DEFAULT_EXPORT_COLUMNS);

                // Contoh 1: Tambah produk baru
                fputcsv($handle, [
                    '',
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
                    '',
                ]);

                // Contoh 2: Update produk yang sudah ada
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
                $sampleChecksum = ProductImportExportService::calculateChecksum($sampleRow2);

                fputcsv($handle, [
                    '1',
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
                ]);
                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache',
            ]);
        }

        // Default: Native Excel template (.xlsx)
        $filename = 'template_import_produk.xlsx';
        $spreadsheet = $service->createXlsxTemplate();
        $writer = new XlsxWriter($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-store, no-cache',
        ]);
    }

    /**
     * Import produk dari file Excel (.xlsx / .xls) atau CSV yang diunggah pengguna via form web per batch.
     * Mendukung import parsial per-kolom, pilihan batch pembelian, dan deteksi perubahan otomatis via checksum.
     */
    public function import(Request $request, Store $store, ProductImportExportService $service)
    {
        $this->authorize('create', [Product::class, $store->id]);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:20480'], // max 20MB
            'batch_id' => ['nullable', 'integer'],
            'batch_size' => ['nullable', 'integer', 'min:10', 'max:500'],
            'dry_run' => ['nullable', 'boolean'],
        ], [
            'file.required' => 'Pilih file Excel (.xlsx) atau CSV yang ingin diimport.',
            'file.mimes' => 'Format file harus berupa Excel (.xlsx, .xls) atau CSV (.csv).',
            'file.max' => 'Ukuran file maksimal 20 MB.',
        ]);

        $uploadedFile = $request->file('file');
        $batchSize = (int) $request->input('batch_size', 100);
        $dryRun = $request->boolean('dry_run');

        $batchId = $request->input('batch_id') ? (int) $request->input('batch_id') : null;
        $batch = null;
        if ($batchId) {
            $batch = PurchaseBatch::where('store_id', $store->id)->find($batchId);
        }

        try {
            $result = $service->importFromFile(
                $store,
                $uploadedFile->getRealPath(),
                $batchSize,
                $dryRun,
                $request->user()?->id,
                $batch?->id
            );

            $mode = $dryRun ? 'Simulasi Import (Dry-Run)' : 'Import Produk Selesai';
            $batchInfo = $batch ? " [Batch: {$batch->batch_code}]" : '';
            $msg = sprintf(
                '%s%s: %d baru dibuat, %d diperbarui, %d tidak berubah (checksum cocok) dari total %d baris (%d batch).',
                $mode,
                $batchInfo,
                $result['created_count'],
                $result['updated_count'],
                $result['unchanged_count'],
                $result['total_rows'],
                $result['batches_count']
            );

            if ($result['skipped_count'] > 0) {
                $msg .= sprintf(' (%d baris dilewati/gagal).', $result['skipped_count']);
            }

            if (! empty($result['errors'])) {
                $firstErrors = array_slice($result['errors'], 0, 5);
                $errorDetails = implode(' | ', $firstErrors);
                if (count($result['errors']) > 5) {
                    $errorDetails .= ' ... dan ' . (count($result['errors']) - 5) . ' catatan lainnya.';
                }

                if ($request->wantsJson()) {
                    return response()->json([
                        'status' => 'warning',
                        'message' => $msg . ' Catatan: ' . $errorDetails,
                        'result' => $result,
                    ]);
                }

                return back()->with('success', $msg)->with('warning', 'Catatan baris: ' . $errorDetails);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => $msg,
                    'result' => $result,
                ]);
            }

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal memproses file import: ' . $e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'Gagal memproses file import: ' . $e->getMessage());
        }
    }
}
