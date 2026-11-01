<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductWhatsAppChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_clean_phone_number_normalizes_indonesian_numbers(): void
    {
        $store = Store::create([
            'name' => 'Toko Uji',
            'slug' => 'toko-uji',
            'status' => 'active',
            'contact_information' => '0812-3456-7890',
        ]);

        $this->assertSame('6281234567890', $store->cleanPhoneNumber());

        $store->contact_information = '+62 812 3456 7890';
        $this->assertSame('6281234567890', $store->cleanPhoneNumber());

        $store->contact_information = '81234567890';
        $this->assertSame('6281234567890', $store->cleanPhoneNumber());

        $store->contact_information = 'cs@tokouji.com';
        $this->assertNull($store->cleanPhoneNumber());
    }

    public function test_product_builds_whatsapp_message_with_details_and_description(): void
    {
        $store = Store::create([
            'name' => 'Toko Busana',
            'slug' => 'toko-busana',
            'status' => 'active',
            'contact_information' => '0812-9999-8888',
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Baju',
            'slug' => 'baju',
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Kemeja Flannel Premium',
            'slug' => 'kemeja-flannel-premium',
            'description' => 'Bahan katun adem dengan motif kotak-kotak klasik.',
            'selling_price' => 175000,
            'stock' => 10,
            'status' => 'active',
        ]);

        $msg = $product->buildWhatsAppMessage($store);

        $this->assertStringContainsString('Toko Busana', $msg);
        $this->assertStringContainsString('Kemeja Flannel Premium', $msg);
        $this->assertStringContainsString('Rp 175.000', $msg);
        $this->assertStringContainsString('Bahan katun adem', $msg);
        $this->assertStringContainsString('toko-busana/produk/kemeja-flannel-premium', $msg);

        $waUrl = $product->whatsappUrl($store);
        $this->assertNotNull($waUrl);
        $this->assertStringStartsWith('https://wa.me/6281299998888?text=', $waUrl);
    }

    public function test_product_detail_page_renders_whatsapp_chat_button_and_modal(): void
    {
        $store = Store::create([
            'name' => 'Toko Gadget',
            'slug' => 'toko-gadget',
            'status' => 'active',
            'contact_information' => '0812-7777-6666',
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Earphone Wireless Pro',
            'slug' => 'earphone-wireless-pro',
            'description' => 'Suara bass jernih dengan noise cancelling.',
            'selling_price' => 250000,
            'stock' => 5,
            'status' => 'active',
        ]);

        $response = $this->get(route('store.product', [$store->slug, $product->slug]));

        $response->assertStatus(200);
        $response->assertSee('btn-wa-chat', false);
        $response->assertSee('Tanya Penjual');
        $response->assertSee('btn-buy-now', false);
        $response->assertSee('Beli Sekarang via QR');
        $response->assertSee('productChatModal', false);
        $response->assertSee('productBuyModal', false);
        $response->assertSee('openProductChat', false);
        $response->assertSee('openProductBuy', false);
    }

    public function test_store_catalog_page_renders_ask_and_buy_buttons_on_product_cards(): void
    {
        $store = Store::create([
            'name' => 'Toko Sepatu',
            'slug' => 'toko-sepatu',
            'status' => 'active',
            'contact_information' => '0813-1111-2222',
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Sneakers Retro Vintage',
            'slug' => 'sneakers-retro-vintage',
            'description' => 'Sneakers kasual sol karet empuk.',
            'selling_price' => 320000,
            'stock' => 8,
            'status' => 'active',
        ]);

        $response = $this->get(route('store.show', $store->slug));

        $response->assertStatus(200);
        $response->assertSee('btn-card-ask-wa', false);
        $response->assertSee('btn-card-buy', false);
        $response->assertSee('Tanya');
        $response->assertSee('Beli');
        $response->assertSee('productChatModal', false);
        $response->assertSee('productBuyModal', false);
        $response->assertSee('store-footer-inner', false);
        $response->assertSeeText('Layanan & Kontak', false);
        $response->assertDontSeeText('Metode Transaksi', false);
        $response->assertDontSeeText('Pemesanan via WhatsApp', false);
        $response->assertSee($store->name);
        $response->assertDontSee('Katalog multi-toko — satu domain, satu database');
    }

    public function test_default_homepage_renders_default_platform_footer(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('default-footer-inner', false);
        $response->assertDontSee('store-footer-inner', false);
    }
}
