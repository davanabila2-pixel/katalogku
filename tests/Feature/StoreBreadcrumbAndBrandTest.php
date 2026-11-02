<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreBreadcrumbAndBrandTest extends TestCase
{
    use RefreshDatabase;

    private function createSampleStore(): Store
    {
        return Store::create([
            'name' => 'Byvii Store',
            'slug' => 'byvii',
            'slogan' => 'Secondhand, First Choice.',
            'description' => 'Pilihan preloved terbaik dan berkelas.',
            'contact_information' => '081234567890',
            'status' => 'active',
        ]);
    }

    public function test_topbar_nama_toko_menggunakan_elemen_store_brand_name(): void
    {
        $store = $this->createSampleStore();

        $response = $this->get('/' . $store->slug);

        $response->assertOk();
        $response->assertSee('store-brand-link', false);
        $response->assertSee('store-brand-name', false);
        $response->assertSee('Byvii Store');
    }

    public function test_katalog_toko_tidak_memuat_breadcrumb_beranda(): void
    {
        $store = $this->createSampleStore();

        $response = $this->get('/' . $store->slug);

        $response->assertOk();
        // Memastikan tidak ada tombol breadcrumb "Beranda"
        $response->assertDontSee('>Beranda</a>', false);
        $response->assertDontSee('Beranda ›', false);
        // Memastikan breadcrumb list menampilkan nama toko dan Katalog
        $response->assertSee('breadcrumb-list', false);
        $response->assertSee('breadcrumb-link', false);
        $response->assertSee('Katalog');
    }

    public function test_halaman_kategori_toko_menampilkan_breadcrumb_toko_dan_kategori_tanpa_beranda(): void
    {
        $store = $this->createSampleStore();

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Sneakers',
            'slug' => 'sneakers',
            'is_active' => true,
        ]);

        $response = $this->get('/' . $store->slug . '/kategori/' . $category->slug);

        $response->assertOk();
        $response->assertDontSee('>Beranda</a>', false);
        $response->assertSee('breadcrumb-list', false);
        $response->assertSee('Byvii Store');
        $response->assertSee('Sneakers');
    }

    public function test_halaman_produk_menampilkan_breadcrumb_toko_kategori_dan_produk_tanpa_beranda(): void
    {
        $store = $this->createSampleStore();

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Sepatu Pria',
            'slug' => 'sepatu-pria',
            'is_active' => true,
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Sepatu Loafers Casual',
            'slug' => 'sepatu-loafers-casual',
            'selling_price' => 349000,
            'stock' => 10,
            'status' => 'active',
        ]);

        $response = $this->get(route('store.product', [$store->slug, $product->slug]));

        $response->assertOk();
        $response->assertDontSee('>Beranda</a>', false);
        $response->assertDontSee('Beranda ›', false);
        $response->assertSee('breadcrumb-list', false);
        $response->assertSee('Byvii Store');
        $response->assertSee('Sepatu Pria');
        $response->assertSee('Sepatu Loafers Casual');
    }

    public function test_halaman_produk_terjual_menampilkan_breadcrumb_toko_tanpa_beranda(): void
    {
        $store = $this->createSampleStore();

        $response = $this->get('/' . $store->slug . '/terjual');

        $response->assertOk();
        $response->assertDontSee('>Beranda</a>', false);
        $response->assertDontSee('Beranda ›', false);
        $response->assertSee('breadcrumb-list', false);
        $response->assertSee('Byvii Store');
        $response->assertSee('Produk Terjual & Ulasan');
    }
}
