<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Store;
use App\Services\SlugService;
use Illuminate\Database\Seeder;

class CategoryHierarchySeeder extends Seeder
{
    /**
     * Data struktur kategori multi-sektor / multi-industri lengkap:
     * Level 1: Kategori Utama (Induk)
     * Level 2: Sub-Kategori
     * Level 3: Sub-Sub Kategori
     * (Sesuai batasan Category::MAX_DEPTH = 3)
     */
    protected array $catalog = [
        [
            'name' => 'Fashion & Busana',
            'children' => [
                [
                    'name' => 'Pakaian Pria',
                    'children' => ['Atasan Pria', 'Bawahan Pria', 'Sepatu & Sandal Pria', 'Aksesoris Pria'],
                ],
                [
                    'name' => 'Pakaian Wanita',
                    'children' => ['Atasan Wanita', 'Bawahan Wanita', 'Dress & Gamis', 'Sepatu & Sandal Wanita', 'Tas Wanita'],
                ],
                [
                    'name' => 'Pakaian Anak & Bayi',
                    'children' => ['Pakaian Anak Laki-laki', 'Pakaian Anak Perempuan', 'Pakaian Bayi & Balita'],
                ],
            ],
        ],
        [
            'name' => 'Elektronik & Gadget',
            'children' => [
                [
                    'name' => 'Handphone & Tablet',
                    'children' => ['Smartphone', 'Tablet & iPad', 'Casing & Pelindung Layar', 'Charger & Kabel Data', 'Powerbank'],
                ],
                [
                    'name' => 'Komputer & Laptop',
                    'children' => ['Laptop & Notebook', 'Komponen PC & Rakitan', 'Keyboard & Mouse', 'Penyimpanan & Flashdisk', 'Monitor & Display'],
                ],
                [
                    'name' => 'Audio, Visual & Kamera',
                    'children' => ['Earphone & Headphone', 'Speaker Bluetooth', 'Kamera & Aksesoris Foto', 'Smart TV & Media Player'],
                ],
            ],
        ],
        [
            'name' => 'Makanan & Minuman',
            'children' => [
                [
                    'name' => 'Makanan Ringan & Camilan',
                    'children' => ['Keripik & Kerupuk', 'Biskuit & Kue Kering', 'Cokelat & Manisan', 'Kacang & Kuaci'],
                ],
                [
                    'name' => 'Bahan Masak & Bumbu Dapur',
                    'children' => ['Minyak Goreng & Margarin', 'Bumbu Instan & Rempah', 'Beras, Mie & Tepung', 'Saus & Kecap'],
                ],
                [
                    'name' => 'Minuman',
                    'children' => ['Kopi & Bubuk Minuman', 'Teh & Minuman Herbal', 'Susu & Olahan Susu', 'Sirup & Jus Buah'],
                ],
                [
                    'name' => 'Makanan Siap Saji & Olahan Beku',
                    'children' => ['Frozen Food & Nugget', 'Makanan Kaleng', 'Lauk Pauk Siap Santap'],
                ],
            ],
        ],
        [
            'name' => 'Kecantikan & Perawatan Tubuh',
            'children' => [
                [
                    'name' => 'Perawatan Wajah (Skincare)',
                    'children' => ['Pembersih Wajah & Micellar', 'Toner & Essence', 'Serum Wajah', 'Pelembap & Sunscreen', 'Masker Wajah'],
                ],
                [
                    'name' => 'Perawatan Tubuh & Rambut',
                    'children' => ['Sabun Mandi & Body Scrub', 'Body Lotion & Body Serum', 'Sampo & Perawatan Rambut', 'Deodorant & Antiperspirant'],
                ],
                [
                    'name' => 'Kosmetik & Makeup',
                    'children' => ['Makeup Wajah (Cushion & Bedak)', 'Makeup Bibir (Lipstik & Tint)', 'Makeup Mata (Mascara & Alis)'],
                ],
                [
                    'name' => 'Parfum & Wewangian',
                    'children' => ['Eau de Parfum (EDP)', 'Eau de Toilette (EDT)', 'Body Mist & Cologne'],
                ],
            ],
        ],
        [
            'name' => 'Kesehatan & Medis',
            'children' => [
                [
                    'name' => 'Suplemen & Vitamin',
                    'children' => ['Vitamin C, D & Multivitamin', 'Suplemen Daya Tahan Tubuh', 'Madu, Propolis & Herbal'],
                ],
                [
                    'name' => 'P3K & Obat-obatan Bebas',
                    'children' => ['Obat Flu, Batuk & Demam', 'Pereda Nyeri & Sakit Kepala', 'Minyak Angin & Balsem', 'Plester & Antiseptik Luka'],
                ],
                [
                    'name' => 'Alat Medis & Sanitasi',
                    'children' => ['Termometer & Tensimeter', 'Masker Medis & Sanitizer', 'Alat Tes Kesehatan Mandiri'],
                ],
            ],
        ],
        [
            'name' => 'Rumah Tangga & Perlengkapan Rumah',
            'children' => [
                [
                    'name' => 'Perabot & Furnitur',
                    'children' => ['Meja & Kursi Kerja', 'Lemari & Rak Serbaguna', 'Kasur & Tempat Tidur'],
                ],
                [
                    'name' => 'Dekorasi & Interior',
                    'children' => ['Lampu Hias & Lampu Meja', 'Jam Dinding & Lukisan', 'Karpet, Keset & Gorden'],
                ],
                [
                    'name' => 'Peralatan Masak & Dapur',
                    'children' => ['Wajan, Panci & Presto', 'Pisau & Spatula Dapur', 'Piring, Gelas & Wadah Makanan'],
                ],
                [
                    'name' => 'Kebersihan & Binatu',
                    'children' => ['Sapu, Pel & Kemoceng', 'Deterjen & Pewangi Pakaian', 'Pembersih Lantai & Kaca'],
                ],
            ],
        ],
        [
            'name' => 'Otomotif & Perkakas',
            'children' => [
                [
                    'name' => 'Aksesoris Sepeda Motor',
                    'children' => ['Helm & Visor', 'Jas Hujan & Sarung Tangan', 'Cover Motor & Box Belakang'],
                ],
                [
                    'name' => 'Aksesoris Mobil',
                    'children' => ['Parfum & Diffuser Mobil', 'Bantal & Cover Jok Mobil', 'Kamera Dashcam & Holder HP'],
                ],
                [
                    'name' => 'Perawatan Kendaraan',
                    'children' => ['Oli Mesin & Pelumas Rantai', 'Sampo Mobil & Motor', 'Lap Microfiber & Spons'],
                ],
                [
                    'name' => 'Perkakas & Alat Pertukangan',
                    'children' => ['Obeng, Tang & Kunci Pas', 'Bor Listrik & Mesin Gerinda', 'Meteran & Perkakas Tangan'],
                ],
            ],
        ],
        [
            'name' => 'Hobi, Olahraga & Outdoor',
            'children' => [
                [
                    'name' => 'Olahraga & Kebugaran',
                    'children' => ['Fitness, Dumbbell & Gym', 'Yoga, Pilates & Matras', 'Sepak Bola & Futsal', 'Bulu Tangkis & Tenis', 'Aksesoris Sepeda'],
                ],
                [
                    'name' => 'Camping & Petualangan Outdoor',
                    'children' => ['Tenda Camping & Matras', 'Sleeping Bag & Hammock', 'Tas Gunung & Daypack', 'Kompor Portable & Nesting'],
                ],
                [
                    'name' => 'Buku, Seni & Alat Tulis',
                    'children' => ['Buku Fiksi & Nonfiksi', 'Buku Catatan, Planner & Diary', 'Alat Tulis Kantor & Sekolah', 'Perlengkapan Menggambar & Melukis'],
                ],
                [
                    'name' => 'Musik & Instrumen',
                    'children' => ['Gitar Akustik & Elektrik', 'Keyboard & Piano Digital', 'Aksesoris Musik & Senar'],
                ],
            ],
        ],
        [
            'name' => 'Ibu, Bayi & Anak',
            'children' => [
                [
                    'name' => 'Kebutuhan Makan & Menyusui',
                    'children' => ['Botol Susu & Dot', 'Pompa ASI & Kantong ASI', 'Perlengkapan MPASI & Kursi Makan'],
                ],
                [
                    'name' => 'Perawatan Bayi',
                    'children' => ['Popok Bayi & Tisu Basah', 'Minyak Telon & Skincare Bayi', 'Perlengkapan Mandi Bayi'],
                ],
                [
                    'name' => 'Mainan Edukasi & Rekreasi',
                    'children' => ['Mainan Balok & Puzzle', 'Mainan Sensorik & Bayi', 'Boneka, Mobilan & Action Figure'],
                ],
            ],
        ],
    ];

    public function run(): void
    {
        $stores = Store::all();

        if ($stores->isEmpty()) {
            $defaultStore = Store::create([
                'name' => 'Toko Utama',
                'slug' => 'toko-utama',
                'description' => 'Katalog toko resmi.',
                'status' => 'active',
            ]);
            $stores = collect([$defaultStore]);
        }

        foreach ($stores as $store) {
            $this->seedStoreCategories($store);
        }
    }

    public function seedStoreCategories(Store $store): void
    {
        $rootOrder = 1;
        $totalCreated = 0;

        foreach ($this->catalog as $rootData) {
            // Level 1: Kategori Utama (Induk)
            $root = $this->getOrCreateCategory($store, $rootData['name'], null, $rootOrder++);
            $totalCreated++;

            $subOrder = 1;
            foreach ($rootData['children'] as $subData) {
                // Level 2: Sub-Kategori
                $sub = $this->getOrCreateCategory($store, $subData['name'], $root, $subOrder++);
                $totalCreated++;

                $leafOrder = 1;
                foreach ($subData['children'] as $leafName) {
                    // Level 3: Sub-Sub Kategori
                    $this->getOrCreateCategory($store, $leafName, $sub, $leafOrder++);
                    $totalCreated++;
                }
            }
        }

        if ($this->command) {
            $this->command->info("Kategori berhasil dibuat untuk toko '{$store->name}' ({$totalCreated} kategori, level 1 s/d 3).");
        }
    }

    protected function getOrCreateCategory(Store $store, string $name, ?Category $parent, int $sortOrder): Category
    {
        $existing = Category::where('store_id', $store->id)
            ->where('name', $name)
            ->where('parent_id', $parent?->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $slug = SlugService::uniqueCategorySlug($store->id, $name);

        return Category::create([
            'store_id' => $store->id,
            'parent_id' => $parent?->id,
            'name' => $name,
            'slug' => $slug,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
    }
}
