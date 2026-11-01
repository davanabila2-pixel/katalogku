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
        $stores = Store::whereIn('slug', ['byvii', 'toko-1', 'toko-utama'])->get();
        if ($stores->isEmpty()) {
            $stores = Store::take(2)->get();
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
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=800&auto=format&fit=crop&q=80',
                ],
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
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1589310243389-96a5483213a8?w=800&auto=format&fit=crop&q=80',
                ],
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
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1509967419530-da38b4704bc6?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?w=800&auto=format&fit=crop&q=80',
                ],
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
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=800&auto=format&fit=crop&q=80',
                ],
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
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1582552938357-32b906df40cb?w=800&auto=format&fit=crop&q=80',
                ],
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
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&auto=format&fit=crop&q=80',
                ],
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
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1622560480605-d83c853bc5c3?w=800&auto=format&fit=crop&q=80',
                ],
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
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1575428652377-a2d80e2277fc?w=800&auto=format&fit=crop&q=80',
                ],
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
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1559563458-527698bf5295?w=800&auto=format&fit=crop&q=80',
                ],
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
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1508296695146-257a814070b4?w=800&auto=format&fit=crop&q=80',
                ],
            ],
            [
                'name' => 'Jam Tangan Pria Chronograph Leather Strap',
                'category_name' => 'Aksesoris',
                'parent_category_name' => null,
                'sku' => 'WAT-CHR-11',
                'selling_price' => 389000,
                'unit_purchase_price' => 180000,
                'quantity' => 25,
                'description' => 'Jam tangan analog pria dengan fungsi chronograph aktif dan strap genuine leather cokelat tua. Water resistant hingga 3 ATM dengan kaca mineral glass anti-gores.',
                'image_path' => 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?w=800&auto=format&fit=crop&q=80',
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&auto=format&fit=crop&q=80',
                ],
            ],
            [
                'name' => 'Sweater Crewneck Knit Minimalist Warm',
                'category_name' => 'Atasan',
                'parent_category_name' => 'Pakaian Pria',
                'sku' => 'SWT-CRW-12',
                'selling_price' => 185000,
                'unit_purchase_price' => 90000,
                'quantity' => 30,
                'description' => 'Sweater rajut kerah bulat bahan katun knit lembut dan tebal. Nyaman digunakan untuk cuaca dingin maupun layering outfit kasual harian.',
                'image_path' => 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=800&auto=format&fit=crop&q=80',
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1576871337632-b9aef4c17ab9?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=800&auto=format&fit=crop&q=80',
                ],
            ],
            [
                'name' => 'Celana Pendek Cargo Tactical Ripstop',
                'category_name' => 'Bawahan Pria',
                'parent_category_name' => 'Pakaian Pria',
                'sku' => 'PNT-CRG-13',
                'selling_price' => 129000,
                'unit_purchase_price' => 65000,
                'quantity' => 40,
                'description' => 'Celana pendek kasual bermotif outdoor berbahan katun ripstop kuat dan tidak mudah robek. Dilengkapi 6 kantong fungsional dengan kancing kokoh.',
                'image_path' => 'https://images.unsplash.com/photo-1591195853828-11db59a44f6b?w=800&auto=format&fit=crop&q=80',
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1506629082955-511b1aa562c8?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1517445312882-bc9910d016b7?w=800&auto=format&fit=crop&q=80',
                ],
            ],
            [
                'name' => 'Sandal Slide Pria Anti-Slip Ergonomis',
                'category_name' => 'Sepatu & Sandal',
                'parent_category_name' => null,
                'sku' => 'SHO-SLD-14',
                'selling_price' => 95000,
                'unit_purchase_price' => 45000,
                'quantity' => 45,
                'description' => 'Sandal slide kasual berbahan Phylon ultra empuk dengan bantalan footbed ergonomis. Anti-slip di permukaan licin, cepat kering, dan sangat ringan.',
                'image_path' => 'https://images.unsplash.com/photo-1603808033192-082d6919d3e1?w=800&auto=format&fit=crop&q=80',
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?w=800&auto=format&fit=crop&q=80',
                ],
            ],
            [
                'name' => 'Jaket Parka Outdoor Windbreaker Waterproof',
                'category_name' => 'Atasan',
                'parent_category_name' => 'Pakaian Pria',
                'sku' => 'JKT-PRK-15',
                'selling_price' => 275000,
                'unit_purchase_price' => 140000,
                'quantity' => 20,
                'description' => 'Jaket parka outdoor dengan lapisan tahan air dan penahan angin (water repellent & windproof). Dilengkapi hoodie yang dapat disesuaikan dan kantong dalam.',
                'image_path' => 'https://images.unsplash.com/photo-1544441893-675973e31985?w=800&auto=format&fit=crop&q=80',
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1495105787522-5334e3ffa0ef?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1509551388413-e18d0ac5d495?w=800&auto=format&fit=crop&q=80',
                ],
            ],
            [
                'name' => 'Tas Selempang Sling Bag Compact Cordura',
                'category_name' => 'Tas & Ransel',
                'parent_category_name' => null,
                'sku' => 'BAG-SLG-16',
                'selling_price' => 119000,
                'unit_purchase_price' => 55000,
                'quantity' => 35,
                'description' => 'Sling bag ringkas multifungsi berbahan Cordura 1000D kuat dan tahan percikan air. Dilengkapi kompartemen smartphone, dompet, kunci, dan slot kabel headset.',
                'image_path' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&auto=format&fit=crop&q=80',
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1622560480605-d83c853bc5c3?w=800&auto=format&fit=crop&q=80',
                ],
            ],
            [
                'name' => 'Ikat Pinggang Kulit Asli Automatic Buckle',
                'category_name' => 'Aksesoris',
                'parent_category_name' => null,
                'sku' => 'ACC-BLT-17',
                'selling_price' => 115000,
                'unit_purchase_price' => 52000,
                'quantity' => 40,
                'description' => 'Ikat pinggang pria kulit sapi asli dengan buckle otomatis berlapis alloy anti karat. Mudah disesuaikan tanpa perlu melubangi sabuk, tampak formal dan elegan.',
                'image_path' => 'https://images.unsplash.com/photo-1624222247344-550fb60583dc?w=800&auto=format&fit=crop&q=80',
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1614179689702-355944cd0918?w=800&auto=format&fit=crop&q=80',
                ],
            ],
            [
                'name' => 'Polo Shirt Pique Classic Fit Bordir',
                'category_name' => 'Atasan',
                'parent_category_name' => 'Pakaian Pria',
                'sku' => 'TSH-POL-18',
                'selling_price' => 110000,
                'unit_purchase_price' => 55000,
                'quantity' => 50,
                'description' => 'Polo shirt katun CVC Lacoste pique premium dengan daya serap keringat tinggi. Kerah rajut padat tidak mudah melar, jahitan rapi untuk tampilan smart-casual.',
                'image_path' => 'https://images.unsplash.com/photo-1586363104862-3a5e2ab60d99?w=800&auto=format&fit=crop&q=80',
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=800&auto=format&fit=crop&q=80',
                ],
            ],
            [
                'name' => 'Kemeja Polos Katun Oxford Lengan Pendek',
                'category_name' => 'Atasan',
                'parent_category_name' => 'Pakaian Pria',
                'sku' => 'SHR-OXF-19',
                'selling_price' => 139000,
                'unit_purchase_price' => 68000,
                'quantity' => 35,
                'description' => 'Kemeja pendek bahan katun oxford bertekstur halus, adem, dan tidak transparan. Potongan slim fit modern cocok untuk kuliah, kerja, maupun hangout santai.',
                'image_path' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=800&auto=format&fit=crop&q=80',
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1607345366928-199ea26cfe3e?w=800&auto=format&fit=crop&q=80',
                ],
            ],
            [
                'name' => 'Sepatu Loafers Pria Casual Slip On',
                'category_name' => 'Sepatu & Sandal',
                'parent_category_name' => null,
                'sku' => 'SHO-LOF-20',
                'selling_price' => 349000,
                'unit_purchase_price' => 165000,
                'quantity' => 25,
                'description' => 'Sepatu loafers slip-on pria model penny loafer berbahan synthetic micro-leather halus. Sol karet elastis anti licin dan insole busa empuk tidak bikin lecet kaki.',
                'image_path' => 'https://images.unsplash.com/photo-1533867617858-e7b97e060509?w=800&auto=format&fit=crop&q=80',
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=800&auto=format&fit=crop&q=80',
                ],
            ],
        ];

        $barcodeService = app(BarcodeService::class);
        $inventoryService = app(InventoryService::class);

        foreach ($stores as $store) {
            $this->command->info("Menambahkan " . count($items) . " produk untuk: {$store->name} (slug: {$store->slug})");

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

                // Simpan foto galeri tambahan
                if (! empty($itemData['gallery_images'])) {
                    foreach ($itemData['gallery_images'] as $pos => $gUrl) {
                        $product->images()->firstOrCreate([
                            'image_path' => $gUrl,
                        ], [
                            'position' => $pos + 1,
                        ]);
                    }
                }

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
                    'notes' => 'Stok awal produk dari seeder',
                    'additional_cost' => 25000,
                ], $batchItemsPayload, $superadmin);

                $this->command->info("  -> Batch pembelian {$batchCode} berhasil dibuat dengan total " . count($batchItemsPayload) . " item.");
            }
        }

        $this->command->info('ProductSeeder selesai: ' . count($items) . ' produk dengan multi-foto dan stok siap digunakan!');
    }
}

