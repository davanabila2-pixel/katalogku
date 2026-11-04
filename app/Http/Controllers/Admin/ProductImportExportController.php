<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Store;
use App\Services\ProductImportExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImportExportController extends Controller
{
    /**
     * Export data produk toko ke format CSV (bisa langsung dibuka di Excel & diimport kembali).
     * Mendukung pilihan kolom tertentu dan dilengkapi checksum integritas per baris.
     */
    public function export(Request $request, Store $store, ProductImportExportService $service): StreamedResponse
    {
        $this->authorize('viewAny', [\App\Models\Product::class, $store->id]);

        $categoryId = $request->query('category_id') ? (int) $request->query('category_id') : null;

        $columns = null;
        if ($request->filled('columns')) {
            $rawCols = $request->query('columns');
            $columns = is_array($rawCols) ? $rawCols : explode(',', (string) $rawCols);
        }

        $filename = sprintf('products_%s_%s.csv', $store->slug, date('Ymd_His'));

        return response()->streamDownload(function () use ($store, $categoryId, $columns, $service) {
            $handle = fopen('php://output', 'w');
            $service->exportToStream($store, $handle, $categoryId, $columns);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Download template CSV kosong dengan baris contoh dan checksum untuk panduan pengisian.
     */
    public function template(Store $store): StreamedResponse
    {
        $this->authorize('viewAny', [\App\Models\Product::class, $store->id]);

        $filename = 'template_import_produk.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8
            fputs($handle, "\xEF\xBB\xBF");
            // Header
            fputcsv($handle, ProductImportExportService::DEFAULT_EXPORT_COLUMNS);

            // Contoh 1: Tambah produk baru (kolom 'id' dikosongkan, checksum otomatis dihitung saat import)
            fputcsv($handle, [
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
            ]);

            // Contoh 2: Update produk yang sudah ada (kolom 'id' diisi ID produk asli hasil export)
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
                '1', // id diisi = update produk dengan ID 1
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

    /**
     * Import produk dari file CSV yang diunggah pengguna via form web per batch.
     * Mendukung import parsial per-kolom dan deteksi perubahan otomatis via checksum.
     */
    public function import(Request $request, Store $store, ProductImportExportService $service)
    {
        $this->authorize('create', [\App\Models\Product::class, $store->id]);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:20480'], // max 20MB
            'batch_size' => ['nullable', 'integer', 'min:10', 'max:500'],
            'dry_run' => ['nullable', 'boolean'],
        ], [
            'file.required' => 'Pilih file CSV yang ingin diimport.',
            'file.mimes' => 'Format file harus berupa CSV (.csv atau .txt).',
            'file.max' => 'Ukuran file CSV maksimal 20 MB.',
        ]);

        $uploadedFile = $request->file('file');
        $batchSize = (int) $request->input('batch_size', 100);
        $dryRun = $request->boolean('dry_run');

        try {
            $result = $service->importFromFile(
                $store,
                $uploadedFile->getRealPath(),
                $batchSize,
                $dryRun,
                $request->user()?->id
            );

            $mode = $dryRun ? 'Simulasi Import (Dry-Run)' : 'Import Produk Selesai';
            $msg = sprintf(
                '%s: %d baru dibuat, %d diperbarui, %d tidak berubah (checksum cocok) dari total %d baris (%d batch).',
                $mode,
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
