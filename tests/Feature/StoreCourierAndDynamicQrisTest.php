<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\QrisService;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreCourierAndDynamicQrisTest extends TestCase
{
    use RefreshDatabase;

    private function crc16(string $data): string
    {
        $crc = 0xFFFF;
        for ($i = 0, $n = strlen($data); $i < $n; $i++) {
            $crc ^= ord($data[$i]) << 8;
            for ($b = 0; $b < 8; $b++) {
                $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
                $crc &= 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    private function staticQris(): string
    {
        $body = '000201'
            .'010211'
            .'2637'.'0014ID.CO.QRIS.WWW'.'0215ID1234567890123'
            .'52045411'
            .'5303360'
            .'5802ID'
            .'5910TOKO BYVII'
            .'6008SURABAYA'
            .'610560255'
            .'6304';

        return $body.$this->crc16($body);
    }

    private function createStore(array $extra = []): array
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $store = Store::create(array_merge([
            'name' => 'Byvii Official',
            'slug' => 'byvii',
            'status' => 'active',
            'contact_information' => '081234567890',
            'postal_code' => '40115',
            'latitude' => -6.9034,
            'longitude' => 107.6186,
        ], $extra));
        $admin->stores()->attach($store->id, ['is_primary' => true]);

        $category = Category::create(['store_id' => $store->id, 'name' => 'Umum', 'slug' => 'umum']);
        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Sepatu Loafers',
            'slug' => 'sepatu-loafers',
            'selling_price' => 100000,
            'stock' => 10,
            'status' => 'active',
        ]);

        return [$admin, $store, $product];
    }

    public function test_qris_service_membuat_qris_dinamis_dengan_nominal_dan_crc_valid(): void
    {
        $service = new QrisService;
        $static = $this->staticQris();

        $this->assertSame($static, $service->normalize($static));

        $dynamic = $service->makeDynamic($static, 212000);

        $this->assertNotNull($dynamic);
        $this->assertStringContainsString('010212', $dynamic);      // 12 = dinamis
        $this->assertStringContainsString('5406212000', $dynamic);  // tag 54 nominal
        $this->assertStringContainsString('5910TOKO BYVII', $dynamic);
        $this->assertSame($dynamic, $service->normalize($dynamic)); // CRC hasil valid
        // tag 54 harus berada sebelum tag 58 (urutan EMV)
        $this->assertLessThan(strpos($dynamic, '5802ID'), strpos($dynamic, '5406212000'));
    }

    public function test_qris_service_menolak_payload_rusak(): void
    {
        $service = new QrisService;

        $this->assertNull($service->normalize('bukan qris'));
        $this->assertNull($service->normalize(substr($this->staticQris(), 0, -1).'0'));
        $this->assertNull($service->makeDynamic('000201', 1000));
    }

    public function test_unggah_gambar_qris_terbaca_otomatis_menjadi_qris_dinamis(): void
    {
        Storage::fake('public');
        [$admin, $store] = $this->createStore();

        $png = (new QRCode(new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => false,
            'scale' => 8,
        ])))->render($this->staticQris());

        $path = tempnam(sys_get_temp_dir(), 'qris').'.png';
        file_put_contents($path, $png);
        $file = new UploadedFile($path, 'qris.png', 'image/png', null, true);

        $this->actingAs($admin)->put(route('admin.profile.update', $store), [
            'name' => $store->name,
            'qr_image' => $file,
        ])->assertRedirect(route('admin.profile.edit', $store));

        $store->refresh();
        $this->assertSame($this->staticQris(), $store->qris_payload);
        $this->assertTrue($store->hasDynamicQris());
    }

    public function test_qris_manual_tidak_valid_ditolak_dan_valid_disimpan(): void
    {
        [$admin, $store] = $this->createStore();

        $this->actingAs($admin)->from(route('admin.profile.edit', $store))->put(route('admin.profile.update', $store), [
            'name' => $store->name,
            'qris_payload' => '000201salah',
        ])->assertSessionHasErrors('qris_payload');

        $this->actingAs($admin)->put(route('admin.profile.update', $store), [
            'name' => $store->name,
            'qris_payload' => $this->staticQris(),
        ])->assertRedirect(route('admin.profile.edit', $store));

        $this->assertSame($this->staticQris(), $store->fresh()->qris_payload);
    }

    public function test_endpoint_qris_mengembalikan_svg_bernominal_dan_404_tanpa_qris(): void
    {
        [, $store] = $this->createStore();

        $this->get(route('store.qris', [$store->slug, 'amount' => 50000]))->assertNotFound();

        $store->update(['qris_payload' => $this->staticQris()]);

        $response = $this->get(route('store.qris', [$store->slug, 'amount' => 212000]));
        $response->assertOk();
        $this->assertStringContainsString('image/svg+xml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('<svg', $response->getContent());

        // Download QRIS dinamis sebagai PNG
        $downloadResponse = $this->get(route('store.qris', [$store->slug, 'amount' => 212000, 'download' => 1]));
        $downloadResponse->assertOk();
        $this->assertStringContainsString('image/png', $downloadResponse->headers->get('Content-Type'));
        $this->assertStringContainsString("attachment; filename=\"QRIS-{$store->slug}-Rp212000.png\"", $downloadResponse->headers->get('Content-Disposition'));

        $this->get(route('store.qris', [$store->slug, 'amount' => 0]))->assertNotFound();
        $this->get(route('store.qris', [$store->slug]))->assertNotFound();
    }

    public function test_checkout_menampilkan_qr_dinamis_dengan_total_produk(): void
    {
        [, $store, $product] = $this->createStore([
            'qr_image_path' => 'https://example.test/qr.png',
            'qris_payload' => $this->staticQris(),
        ]);

        $customer = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($customer)->get(route('store.checkout', $store->slug)."?product_id={$product->id}&quantity=2");

        $response->assertOk();
        $response->assertSee('amount=200000', false);
        $response->assertSee('QR sudah berisi nominal');
        $response->assertSee('Unduh QRIS Pembayaran');
        $response->assertSee('btnDownloadCheckoutQr', false);
    }

    public function test_master_ekspedisi_disimpan_dan_default_semua_aktif(): void
    {
        [$admin, $store] = $this->createStore();

        $this->assertNull($store->active_couriers);
        $this->assertContains('jne', $store->activeCourierCodes());
        $this->assertCount(count(\App\Support\Couriers::all()), $store->activeCourierCodes());

        $this->actingAs($admin)->get(route('admin.profile.edit', $store))
            ->assertOk()->assertSee('Master Ekspedisi');

        $this->actingAs($admin)->put(route('admin.profile.update', $store), [
            'name' => $store->name,
            'couriers_submitted' => '1',
            'active_couriers' => ['jne', 'sicepat', 'kurir-palsu'],
        ])->assertRedirect(route('admin.profile.edit', $store));

        $this->assertSame(['jne', 'sicepat'], $store->fresh()->activeCourierCodes());

        // Semua dimatikan
        $this->actingAs($admin)->put(route('admin.profile.update', $store), [
            'name' => $store->name,
            'couriers_submitted' => '1',
        ]);
        $this->assertSame([], $store->fresh()->activeCourierCodes());
    }

    public function test_cek_ongkir_hanya_memakai_ekspedisi_aktif(): void
    {
        config(['services.biteship.key' => 'biteship_test.fake']);
        [, $store, $product] = $this->createStore(['active_couriers' => ['jne']]);

        Http::fake([
            '*/rates/couriers' => Http::response([
                'pricing' => [
                    ['company' => 'jne', 'courier_name' => 'JNE', 'courier_code' => 'jne', 'courier_service_name' => 'Reguler', 'courier_service_code' => 'reg', 'price' => 12000, 'duration' => '1-2 hari'],
                    ['company' => 'sicepat', 'courier_name' => 'SiCepat', 'courier_code' => 'sicepat', 'courier_service_name' => 'Reguler', 'courier_service_code' => 'reg', 'price' => 9000, 'duration' => '1-2 hari'],
                ],
            ]),
        ]);

        $response = $this->postJson(route('store.shipping.rates', $store->slug), [
            'postal_code' => '40112',
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertOk();
        $rates = $response->json('rates');
        $this->assertCount(1, $rates);
        $this->assertSame('jne', $rates[0]['company']);

        Http::assertSent(fn ($request) => ($request['couriers'] ?? null) === 'jne');
    }

    public function test_cek_ongkir_tanpa_ekspedisi_aktif_tidak_memanggil_biteship(): void
    {
        config(['services.biteship.key' => 'biteship_test.fake']);
        [, $store, $product] = $this->createStore(['active_couriers' => []]);

        Http::fake();

        $response = $this->postJson(route('store.shipping.rates', $store->slug), [
            'postal_code' => '40112',
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertOk();
        $this->assertSame([], $response->json('rates'));
        Http::assertNothingSent();
    }

    public function test_checkout_menolak_ekspedisi_nonaktif(): void
    {
        [, $store, $product] = $this->createStore(['active_couriers' => ['jne']]);
        $customer = User::factory()->create(['role' => 'user']);

        $this->actingAs($customer)->postJson(route('cart.checkout', $store->slug), [
            'buyer_name' => 'Budi',
            'buyer_phone' => '08123',
            'buyer_address' => 'Jl. Mawar 1',
            'product_id' => $product->id,
            'quantity' => 1,
            'shipping_courier' => 'SiCepat',
            'shipping_service' => 'Reguler',
            'shipping_fee' => 9000,
        ])->assertStatus(422);

        $this->actingAs($customer)->postJson(route('cart.checkout', $store->slug), [
            'buyer_name' => 'Budi',
            'buyer_phone' => '08123',
            'buyer_address' => 'Jl. Mawar 1',
            'product_id' => $product->id,
            'quantity' => 1,
            'shipping_courier' => 'JNE',
            'shipping_service' => 'Reguler',
            'shipping_fee' => 12000,
        ])->assertOk()->assertJson(['success' => true]);
    }

    public function test_store_auto_extracts_qris_payload_if_missing(): void
    {
        $static = $this->staticQris();
        $png = (new QRCode(new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => false,
            'scale' => 8,
        ])))->render($static);
        $tmp = tempnam(sys_get_temp_dir(), 'test_qr_').'.png';
        file_put_contents($tmp, $png);

        [, $store] = $this->createStore([
            'qr_image_path' => $tmp,
            'qris_payload' => null,
        ]);

        $this->assertNull($store->getRawOriginal('qris_payload'));
        $this->assertTrue($store->hasDynamicQris());
        $this->assertSame($static, $store->qris_payload);

        @unlink($tmp);
    }
}
