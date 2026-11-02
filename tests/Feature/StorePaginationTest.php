<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorePaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function createStoreWithProducts(int $count = 15): Store
    {
        $store = Store::create([
            'name' => 'Toko Pagination Test',
            'slug' => 'toko-pagination-test',
            'status' => 'active',
        ]);

        for ($i = 1; $i <= $count; $i++) {
            Product::create([
                'store_id' => $store->id,
                'name' => "Produk Item {$i}",
                'slug' => "produk-item-{$i}",
                'selling_price' => 10000 * $i,
                'stock' => 10,
                'status' => 'active',
            ]);
        }

        return $store;
    }

    public function test_katalog_toko_menampilkan_dropdown_pilihan_per_page_10_25_50_100(): void
    {
        $store = $this->createStoreWithProducts(15);

        $response = $this->get(route('store.show', $store->slug));

        $response->assertOk();
        $response->assertSee('pagination-per-page-select', false);
        $response->assertSee('Tampilkan:', false);
        $response->assertSee('per_page=10', false);
        $response->assertSee('per_page=25', false);
        $response->assertSee('per_page=50', false);
        $response->assertSee('per_page=100', false);

        // Default 10 per halaman, total 15 produk -> 2 halaman
        $response->assertSee('Menampilkan', false);
        $response->assertSee('1–10', false);
        $response->assertSee('15 produk', false);
    }

    public function test_pilihan_per_page_diterapkan_dengan_benar(): void
    {
        $store = $this->createStoreWithProducts(15);

        // Pilih 25 per halaman
        $response = $this->get(route('store.show', [$store->slug, 'per_page' => 25]));

        $response->assertOk();
        $response->assertSee('1–15', false);
        $response->assertSee('15 produk', false);
        $response->assertSee('selected', false);
    }

    public function test_nilai_per_page_tidak_valid_fallback_ke_10(): void
    {
        $store = $this->createStoreWithProducts(15);

        // Kirim nilai acak 999
        $response = $this->get(route('store.show', [$store->slug, 'per_page' => 999]));

        $response->assertOk();
        $response->assertSee('1–10', false);
        $response->assertSee('15 produk', false);
    }
}

