<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseBatch;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use App\Services\HppService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchStockTest extends TestCase
{
    use RefreshDatabase;

    protected function bootstrap(): array
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@x.com', 'role' => 'admin', 'is_active' => true]);
        $store = Store::create(['name' => 'Toko Uji', 'status' => 'active']);
        $store->users()->attach($admin->id, ['is_primary' => true]);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Kaos', 'slug' => 'kaos', 'selling_price' => 50000, 'status' => 'active']);

        return [$admin, $store, $product];
    }

    public function test_perhitungan_total_batch_benar(): void
    {
        [$admin, $store, $product] = $this->bootstrap();

        $batch = app(InventoryService::class)->createBatch([
            'store_id' => $store->id, 'batch_code' => 'B1', 'purchase_date' => now()->toDateString(), 'additional_cost' => 10000,
        ], [
            ['product_id' => $product->id, 'quantity' => 50, 'unit_purchase_price' => 20000],
            ['product_id' => $product->id, 'quantity' => 30, 'unit_purchase_price' => 22000],
        ], $admin);

        // (50×20000) + (30×22000) + 10000 = 1.670.000
        $this->assertEquals(1670000, $batch->totalCost());
        $this->assertEquals(80, $product->fresh()->stock);
        $this->assertEquals(2, StockMovement::where('purchase_batch_id', $batch->id)->count());
    }

    public function test_produk_dan_batch_harus_satu_toko(): void
    {
        [$admin, $store, $product] = $this->bootstrap();
        $other = Store::create(['name' => 'Lain', 'status' => 'active']);
        $foreign = Product::create(['store_id' => $other->id, 'name' => 'Asing', 'slug' => 'asing', 'selling_price' => 1000, 'status' => 'active']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(InventoryService::class)->createBatch([
            'store_id' => $store->id, 'batch_code' => 'BX', 'purchase_date' => now()->toDateString(),
        ], [
            ['product_id' => $foreign->id, 'quantity' => 1, 'unit_purchase_price' => 1000],
        ], $admin);
    }

    public function test_transaksi_gagal_tidak_meninggalkan_stok_parsial(): void
    {
        [$admin, $store, $product] = $this->bootstrap();
        $other = Store::create(['name' => 'Lain', 'status' => 'active']);
        $foreign = Product::create(['store_id' => $other->id, 'name' => 'Asing', 'slug' => 'asing', 'selling_price' => 1000, 'status' => 'active']);

        try {
            app(InventoryService::class)->createBatch([
                'store_id' => $store->id, 'batch_code' => 'BGAGAL', 'purchase_date' => now()->toDateString(),
            ], [
                ['product_id' => $product->id, 'quantity' => 10, 'unit_purchase_price' => 5000],
                ['product_id' => $foreign->id, 'quantity' => 5, 'unit_purchase_price' => 5000],
            ], $admin);
            $this->fail('Harus melempar ValidationException');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // diharapkan
        }

        $this->assertEquals(0, $product->fresh()->stock);
        $this->assertDatabaseMissing('purchase_batches', ['batch_code' => 'BGAGAL']);
        $this->assertEquals(0, StockMovement::count());
    }

    public function test_hpp_rata_tertimbang_dan_estimasi_margin(): void
    {
        [$admin, $store, $product] = $this->bootstrap();

        app(InventoryService::class)->createBatch([
            'store_id' => $store->id, 'batch_code' => 'B1', 'purchase_date' => now()->toDateString(), 'additional_cost' => 0,
        ], [['product_id' => $product->id, 'quantity' => 50, 'unit_purchase_price' => 20000]], $admin);

        app(InventoryService::class)->createBatch([
            'store_id' => $store->id, 'batch_code' => 'B2', 'purchase_date' => now()->toDateString(), 'additional_cost' => 0,
        ], [['product_id' => $product->id, 'quantity' => 30, 'unit_purchase_price' => 22000]], $admin);

        // HPP = (50×20000 + 30×22000) / 80 = 20750
        $hpp = app(HppService::class)->hppForProduct($product->fresh());
        $this->assertEquals(20750, $hpp);
        $this->assertEquals(50000 - 20750, app(HppService::class)->marginForProduct($product->fresh()));
    }

    public function test_pembatalan_batch_mengembalikan_stok(): void
    {
        [$admin, $store, $product] = $this->bootstrap();

        $batch = app(InventoryService::class)->createBatch([
            'store_id' => $store->id, 'batch_code' => 'B1', 'purchase_date' => now()->toDateString(),
        ], [['product_id' => $product->id, 'quantity' => 10, 'unit_purchase_price' => 5000]], $admin);

        $this->assertEquals(10, $product->fresh()->stock);

        app(InventoryService::class)->cancelBatch($batch->fresh(), $admin, 'supplier batal');

        $this->assertEquals(0, $product->fresh()->stock);
        $this->assertEquals('cancelled', $batch->fresh()->status);
    }

    public function test_koreksi_stok_tanpa_alasan_ditolak(): void
    {
        [$admin, $store, $product] = $this->bootstrap();

        $this->actingAs($admin)->post("/admin/{$store->id}/products/{$product->id}/adjust", [
            'quantity' => 5, 'reason' => '',
        ])->assertSessionHasErrors('reason');
    }

    public function test_validasi_jumlah_dan_harga_batch(): void
    {
        [$admin, $store, $product] = $this->bootstrap();

        $this->actingAs($admin)->post("/admin/{$store->id}/batches", [
            'batch_code' => 'BV', 'purchase_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 0, 'unit_purchase_price' => -100]],
        ])->assertSessionHasErrors(['items.0.quantity', 'items.0.unit_purchase_price']);
    }
}
