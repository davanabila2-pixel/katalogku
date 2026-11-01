<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\BarcodeService;
use App\Services\InventoryService;
use App\Services\SlugService;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Jalankan seeder 10 produk realistis lengkap dengan foto dan stok batch.
     */
    public function run(): void
    {
        $superadmin = User::where('role', 'superadmin')->first();
        if (! $superadmin) {
            $superadmin = User::first();
        }

        // Tentukan toko yang akan diisi produk
        // Utamakan 'Toko 1' (slug: toko-1), jika tidak ada gunakan toko pertama yang ada
        $stores = Store::whereIn('slug', ['toko-1', 'toko-utama'])->get();
        if ($stores->isEmpty()) {
            $stores = Store::take(1)->get();
        }

        if ($stores->isEmpty()) {
            $this->command->error('Tidak ada toko yang ditemukan untuk mengisi produk.');
            return;
        }

        $items = [
            [
                'name' => 'Kaos Polos Cotton Combed 30s',
                'category_name' => 'Kaos',
                'parent_category_name' => 'Pakaian Pria',
                'sku' => 'TSH-WHT-01',
                'selling_price' => 75000,
                'unit_purchase_price' => 35000,
                'quantity' => 50,
                'description' => 'Kaos polos premium bahan 100% katun combed 30s reaktif. Karakteristik bahan sangat adem, menyerap keringat dengan baik, dan dijahit dengan jahitan rantai standar distro.',
                'image_path' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Kemeja Flanel Lengan Panjang Slim Fit',
                'category_name' => 'Kemeja',
                'parent_category_name' => 'Pakaian Pria',
                'sku' => 'SHR-FLN-02',
                'selling_price' => 149000,
                'unit_purchase_price' => 75000,
                'quantity' => 35,
                'description' => 'Kemeja flanel kasual motif kotak-kotak klasik dengan potongan modern slim fit. Cocok untuk kuliah, nongkrong santai, maupun luaran (outer layer).',
                'image_path' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Jaket Hoodie Oversize Heavy Fleece',
                'category_name' => 'Jaket & Hoodie',
                'parent_category_name' => 'Pakaian Pria',
                'sku' => 'JKT-HOD-03',
                'selling_price' => 220000,
                'unit_purchase_price' => 110000,
                'quantity' => 25,
                'description' => 'Hoodie oversize bahan cotton fleece tebal 330 gsm dengan saku kanguru. Bagian dalam sangat lembut, tidak berbulu saat dicuci, dan memberikan kehangatan maksimal.',
                'image_path' => 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Celana Chino Panjang Regular Stretch',
                'category_name' => 'Celana Chino',
                'parent_category_name' => 'Bawahan Pria',
                'sku' => 'PNT-CHN-04',
                'selling_price' => 179000,
                'unit_purchase_price' => 90000,
                'quantity' => 30,
                'description' => 'Celana chino katun twill stretch berkualitas tinggi. Sangat elastis dan fleksibel untuk bergerak bebas, cocok untuk pakaian kantor kasual maupun jalan-jalan.',
                'image_path' => 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Celana Denim Jeans Selvedge 14oz',
                'category_name' => 'Celana Jeans',
                'parent_category_name' => 'Bawahan Pria',
                'sku' => 'PNT-JNS-05',
                'selling_price' => 299000,
                'unit_purchase_price' => 150000,
                'quantity' => 20,
                'description' => 'Denim raw selvedge aksen indigo wash klasik dengan ketebalan 14oz. Menghasilkan kontur fading yang unik seiring pemakaian, kuat dan awet bertahun-tahun.',
                'image_path' => 'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Sepatu Sneakers Canvas Low Vintage',
                'category_name' => 'Sepatu & Sandal',
                'parent_category_name' => null,
                'sku' => 'SHO-SNK-06',
                'selling_price' => 325000,
                'unit_purchase_price' => 160000,
                'quantity' => 20,
                'description' => 'Sepatu sneakers siluet klasik dengan kanvas 12oz tahan robek dan sol karet vulkanisir anti-slip. Insole empuk memory foam memberikan kenyamanan melangkah sepanjang hari.',
                'image_path' => 'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Tas Ransel Laptop Urban Waterproof',
                'category_name' => 'Tas & Ransel',
                'parent_category_name' => null,
                'sku' => 'BAG-BPK-07',
                'selling_price' => 245000,
                'unit_purchase_price' => 120000,
                'quantity' => 25,
                'description' => 'Tas ransel kompartemen laptop hingga 15.6 inci dengan bantalan busa tebal. Dibuat dari material poliester tahan air (water-repellent) dengan saku tersembunyi anti-maling.',
                'image_path' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Topi Baseball Cap Classic Twill',
                'category_name' => 'Aksesoris',
                'parent_category_name' => null,
                'sku' => 'ACC-CAP-08',
                'selling_price' => 59000,
                'unit_purchase_price' => 25000,
                'quantity' => 40,
                'description' => 'Topi baseball model polo cap 6-panel bahan katun twill premium. Dilengkapi lubang sirkulasi udara bordir dan pengait gesper logam yang mudah diatur ukurannya.',
                'image_path' => 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Dompet Kulit Pria Bifold Slim',
                'category_name' => 'Aksesoris',
                'parent_category_name' => null,
                'sku' => 'ACC-WLT-09',
                'selling_price' => 135000,
                'unit_purchase_price' => 60000,
                'quantity' => 30,
                'description' => 'Dompet bifold ramping dengan sentuhan tekstur kulit alami. Memiliki 6 slot kartu, 2 slot tersembunyi, dan 2 sekat uang kertas dengan jahitan tepi yang rapi dan presisi.',
                'image_path' => 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Kacamata Hitam Polarized UV400 Retro',
                'category_name' => 'Aksesoris',
                'parent_category_name' => null,
                'sku' => 'ACC-SGL-10',
                'selling_price' => 109000,
                'unit_purchase_price' => 45000,
                'quantity' => 35,
                'description' => 'Kacamata hitam pelindung radiasi sinar UV400 dengan lensa polarized anti-pantulan silau. Frame terbuat dari TR90 super ringan dan fleksibel, tidak menekan hidung.',
                'image_path' => 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?w=800&auto=format&fit=crop&q=80',
            ],
        ];

        $barcodeService = app(BarcodeService::class);
        $inventoryService = app(InventoryService::class);

        foreach ($stores as $store) {
            $this->command->info("Menambahkan 10 produk untuk: {$store->name} (slug: {$store->slug})");

            $batchItemsPayload = [];

            foreach ($items as $itemData) {
                // Pastikan kategori induk & kategori tersedia di toko ini
                $parentId = null;
                if (! empty($itemData['parent_category_name'])) {
                    $parentCat = Category::firstOrCreate([
                        'store_id' => $store->id,
                        'slug' => str($itemData['parent_category_name'])->slug()->toString(),
                    ], [
                        'name' => $itemData['parent_category_name'],
                        'is_active' => true,
                    ]);
                    $parentId = $parentCat->id;
                }

                $category = Category::firstOrCreate([
                    'store_id' => $store->id,
                    'slug' => str($itemData['category_name'])->slug()->toString(),
                ], [
                    'name' => $itemData['category_name'],
                    'parent_id' => $parentId,
                    'is_active' => true,
                ]);

                $slug = str($itemData['name'])->slug()->toString();

                $product = Product::firstOrCreate([
                    'store_id' => $store->id,
                    'slug' => $slug,
                ], [
                    'category_id' => $category->id,
                    'name' => $itemData['name'],
                    'sku' => $itemData['sku'],
                    'description' => $itemData['description'],
                    'selling_price' => $itemData['selling_price'],
                    'image_path' => $itemData['image_path'],
                    'status' => 'active',
                    'is_bundle' => false,
                    'is_hidden_from_catalog' => false,
                ]);

                // Pastikan barcode terisi
                if (empty($product->barcode)) {
                    $product->updateQuietly([
                        'barcode' => $barcodeService->generateBarcodeString($product),
                    ]);
                }

                // Siapkan data batch jika produk belum memiliki riwayat pembelian
                if ($product->batchItems()->count() === 0) {
                    $batchItemsPayload[] = [
                        'product_id' => $product->id,
                        'quantity' => $itemData['quantity'],
                        'unit_purchase_price' => $itemData['unit_purchase_price'],
                    ];
                }
            }

            // Buat batch pembelian masuk agar stok, HPP, riwayat stok, dan estimasi margin otomatis terbentuk
            if (! empty($batchItemsPayload)) {
                $batchCode = 'BATCH-' . strtoupper(str_replace('-', '', $store->slug)) . '-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(4));
                
                $inventoryService->createBatch([
                    'store_id' => $store->id,
                    'batch_code' => $batchCode,
                    'purchase_date' => now()->toDateString(),
                    'supplier_name' => 'Supplier Garmen Nusantara',
                    'notes' => 'Stok awal 10 produk dari seeder',
                    'additional_cost' => 25000,
                ], $batchItemsPayload, $superadmin);

                $this->command->info("  -> Batch pembelian {$batchCode} berhasil dibuat dengan total " . count($batchItemsPayload) . " item.");
            }
        }

        $this->command->info('ProductSeeder selesai: 10 produk dengan foto dan stok siap digunakan!');
    }
}

