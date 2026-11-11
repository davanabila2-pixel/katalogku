<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private function createStoreWithProduct(): array
    {
        $owner = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $store = Store::create([
            'name' => 'Toko Busana Cantik',
            'slug' => 'toko-busana-cantik',
            'status' => 'active',
            'contact_information' => '081234567890',
            'qr_account_name' => 'BCA a.n Toko Busana',
            'qr_image_path' => 'stores/1/qr.png',
        ]);

        $owner->stores()->attach($store->id, ['is_primary' => true]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Atasan',
            'slug' => 'atasan',
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Kemeja Katun Putih',
            'slug' => 'kemeja-katun-putih',
            'description' => 'Kemeja katun adem formal dan santai.',
            'selling_price' => 150000,
            'stock' => 15,
            'status' => 'active',
        ]);

        return [$owner, $store, $product];
    }

    public function test_buyer_can_submit_order_successfully(): void
    {
        [$owner, $store, $product] = $this->createStoreWithProduct();

        $response = $this->postJson("/{$store->slug}/orders", [
            'product_id' => $product->id,
            'quantity' => 2,
            'buyer_name' => 'Siti Aisyah',
            'buyer_phone' => '087712345678',
            'buyer_address' => 'Jl. Merdeka No. 45, Kebayoran Baru, Jakarta Selatan',
            'buyer_notes' => 'Ukuran M warna putih bersih',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'total_price_formatted' => 'Rp 300.000',
                'has_proof' => false,
            ]);

        $this->assertDatabaseHas('orders', [
            'store_id' => $store->id,
            'product_id' => $product->id,
            'buyer_name' => 'Siti Aisyah',
            'buyer_phone' => '087712345678',
            'quantity' => 2,
            'total_price' => 300000,
            'status' => 'pending',
            'payment_method' => 'qris',
        ]);

        $order = Order::first();
        $this->assertNotNull($order->order_code);
        $this->assertStringStartsWith('ORD-', $order->order_code);
    }

    public function test_buyer_can_submit_order_with_payment_proof(): void
    {
        Storage::fake('public');
        Storage::fake('google');

        [$owner, $store, $product] = $this->createStoreWithProduct();

        $proofFile = UploadedFile::fake()->image('bukti_qris.jpg', 600, 800);

        $response = $this->postJson("/{$store->slug}/orders", [
            'product_id' => $product->id,
            'quantity' => 1,
            'buyer_name' => 'Budi Santoso',
            'buyer_phone' => '081299887766',
            'buyer_address' => 'Jl. Diponegoro No. 12, Surabaya, Jawa Timur 60241',
            'buyer_notes' => 'Langsung proses ya kak',
            'payment_proof' => $proofFile,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'has_proof' => true,
            ]);

        $order = Order::where('buyer_name', 'Budi Santoso')->first();
        $this->assertNotNull($order);
        $this->assertNotNull($order->payment_proof_path);
        $this->assertSame('paid', $order->status);
    }

    public function test_order_submission_validates_required_fields(): void
    {
        [$owner, $store, $product] = $this->createStoreWithProduct();

        $response = $this->postJson("/{$store->slug}/orders", [
            'product_id' => $product->id,
            'quantity' => 1,
            // Missing name, phone, address
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['buyer_name', 'buyer_phone', 'buyer_address']);
    }

    public function test_store_admin_can_view_orders_list(): void
    {
        [$owner, $store, $product] = $this->createStoreWithProduct();

        Order::create([
            'order_code' => 'ORD-TEST-001',
            'store_id' => $store->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_price' => 150000,
            'quantity' => 2,
            'total_price' => 300000,
            'buyer_name' => 'Dewi Sartika',
            'buyer_phone' => '081122334455',
            'buyer_address' => 'Jl. Ganesha No. 10, Bandung',
            'payment_method' => 'qris',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($owner)->get(route('admin.orders.index', $store));

        $response->assertStatus(200);
        $response->assertSee('Pesanan Masuk');
        $response->assertSee('ORD-TEST-001');
        $response->assertSee('Dewi Sartika');
        $response->assertSee('Rp 300.000');
    }

    public function test_store_admin_can_update_order_status(): void
    {
        [$owner, $store, $product] = $this->createStoreWithProduct();

        $order = Order::create([
            'order_code' => 'ORD-TEST-002',
            'store_id' => $store->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_price' => 150000,
            'quantity' => 1,
            'total_price' => 150000,
            'buyer_name' => 'Ahmad Dahlan',
            'buyer_phone' => '081344556677',
            'buyer_address' => 'Jl. Malioboro No. 8, Yogyakarta',
            'payment_method' => 'qris',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($owner)->patchJson(route('admin.orders.status', [$store, $order]), [
            'status' => 'processing',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'processing',
                'status_label' => 'Sedang Diproses',
            ]);

        $this->assertSame('processing', $order->fresh()->status);
    }

    public function test_store_admin_cannot_view_or_update_other_store_orders(): void
    {
        [$owner, $store, $product] = $this->createStoreWithProduct();

        $otherStore = Store::create([
            'name' => 'Toko Sebelah',
            'slug' => 'toko-sebelah',
            'status' => 'active',
        ]);

        $otherProduct = Product::create([
            'store_id' => $otherStore->id,
            'name' => 'Barang Lain',
            'slug' => 'barang-lain',
            'selling_price' => 50000,
            'stock' => 5,
            'status' => 'active',
        ]);

        $otherOrder = Order::create([
            'order_code' => 'ORD-OTHER-001',
            'store_id' => $otherStore->id,
            'product_id' => $otherProduct->id,
            'product_name' => $otherProduct->name,
            'product_price' => 50000,
            'quantity' => 1,
            'total_price' => 50000,
            'buyer_name' => 'Orang Asing',
            'buyer_phone' => '089988776655',
            'buyer_address' => 'Alamat Lain',
            'payment_method' => 'transfer',
            'status' => 'pending',
        ]);

        // Attempt to access other store's orders page
        $response = $this->actingAs($owner)->get(route('admin.orders.index', $otherStore));
        $response->assertStatus(403);

        // Attempt to update other store's order status
        $patchResponse = $this->actingAs($owner)->patchJson(route('admin.orders.status', [$otherStore, $otherOrder]), [
            'status' => 'completed',
        ]);
        $patchResponse->assertStatus(403);

        // Attempt to update other store's order tracking number
        $trackingResponse = $this->actingAs($owner)->patchJson(route('admin.orders.tracking', [$otherStore, $otherOrder]), [
            'tracking_number' => 'JNT123456',
        ]);
        $trackingResponse->assertStatus(403);
    }

    public function test_admin_orders_page_has_processing_tab_and_tracking_number_input(): void
    {
        [$owner, $store, $product] = $this->createStoreWithProduct();

        Order::create([
            'order_code' => 'ORD-PROC-001',
            'store_id' => $store->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_price' => 150000,
            'quantity' => 1,
            'total_price' => 150000,
            'buyer_name' => 'Rina Sari',
            'buyer_phone' => '081234560000',
            'buyer_address' => 'Jl. Sudirman No. 1, Jakarta',
            'shipping_courier' => 'J&T Express',
            'shipping_service' => 'EZ (Reguler)',
            'payment_method' => 'qris',
            'status' => 'processing',
        ]);

        Order::create([
            'order_code' => 'ORD-PAID-001',
            'store_id' => $store->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_price' => 150000,
            'quantity' => 1,
            'total_price' => 150000,
            'buyer_name' => 'Andi Wijaya',
            'buyer_phone' => '081234561111',
            'buyer_address' => 'Jl. Thamrin No. 2, Jakarta',
            'payment_method' => 'qris',
            'status' => 'paid',
        ]);

        $response = $this->actingAs($owner)->get(route('admin.orders.index', [$store, 'status' => 'processing']));

        $response->assertOk();
        $response->assertSee('Diproses (1)');
        $response->assertSee('ORD-PROC-001');
        $response->assertDontSee('ORD-PAID-001');
        $response->assertSee('Input No. Resi');
        $response->assertSee('name="tracking_number"', false);
        $response->assertSee('J&amp;T Express', false);
    }

    public function test_store_admin_can_save_tracking_number_and_customer_sees_it(): void
    {
        [$owner, $store, $product] = $this->createStoreWithProduct();

        $customer = User::factory()->create(['role' => 'user', 'is_active' => true]);

        $processingOrder = Order::create([
            'order_code' => 'ORD-RESI-001',
            'store_id' => $store->id,
            'product_id' => $product->id,
            'user_id' => $customer->id,
            'product_name' => $product->name,
            'product_price' => 150000,
            'quantity' => 1,
            'total_price' => 150000,
            'buyer_name' => 'Citra Kirana',
            'buyer_phone' => '081288990011',
            'buyer_address' => 'Jl. Dago No. 10, Bandung',
            'shipping_courier' => 'JNE',
            'shipping_service' => 'Reguler',
            'payment_method' => 'qris',
            'status' => 'processing',
        ]);

        $paidOrder = Order::create([
            'order_code' => 'ORD-RESI-002',
            'store_id' => $store->id,
            'product_id' => $product->id,
            'user_id' => $customer->id,
            'product_name' => $product->name,
            'product_price' => 150000,
            'quantity' => 1,
            'total_price' => 150000,
            'buyer_name' => 'Doni Tata',
            'buyer_phone' => '081288990022',
            'buyer_address' => 'Jl. Braga No. 5, Bandung',
            'payment_method' => 'qris',
            'status' => 'paid',
        ]);

        // Simpan nomor resi pada pesanan Diproses
        $res1 = $this->actingAs($owner)->patch(route('admin.orders.tracking', [$store, $processingOrder]), [
            'tracking_number' => 'JNE0012345678',
        ]);
        $res1->assertRedirect();

        $processingOrder->refresh();
        $this->assertSame('JNE0012345678', $processingOrder->tracking_number);
        $this->assertNotNull($processingOrder->shipped_at);
        $this->assertSame('processing', $processingOrder->status);

        // Simpan nomor resi pada pesanan Dibayar dengan mark_processing=1 -> otomatis pindah ke Diproses
        $res2 = $this->actingAs($owner)->patchJson(route('admin.orders.tracking', [$store, $paidOrder]), [
            'tracking_number' => 'JP9988776655',
            'mark_processing' => true,
        ]);
        $res2->assertOk()->assertJson([
            'success' => true,
            'status' => 'processing',
            'tracking_number' => 'JP9988776655',
        ]);

        $this->assertSame('JP9988776655', $paidOrder->fresh()->tracking_number);
        $this->assertSame('processing', $paidOrder->fresh()->status);

        // Pencarian admin berdasarkan nomor resi berhasil
        $this->actingAs($owner)->get(route('admin.orders.index', [$store, 'q' => 'JNE0012345678']))
            ->assertOk()
            ->assertSee('ORD-RESI-001')
            ->assertDontSee('ORD-RESI-002');

        // Pelanggan melihat nomor resi di riwayat pesanan toko & akun global
        $this->actingAs($customer)->get(route('store.orders', $store->slug))
            ->assertOk()
            ->assertSee('JNE0012345678');

        $this->actingAs($customer)->get(route('user.orders'))
            ->assertOk()
            ->assertSee('JNE0012345678');
    }
}
