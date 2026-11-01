<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductGalleryTest extends TestCase
{
    use RefreshDatabase;

    private function createStoreWithProduct(): array
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $store = Store::create([
            'name' => 'Toko Galeri Keren',
            'slug' => 'toko-galeri-keren',
            'status' => 'active',
        ]);

        $admin->stores()->attach($store->id, ['is_primary' => true]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Fashion',
            'slug' => 'fashion',
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Kemeja Katun Putih',
            'slug' => 'kemeja-katun-putih',
            'selling_price' => 125000,
            'stock' => 10,
            'status' => 'active',
            'image_path' => 'https://example.com/cover.jpg',
        ]);

        return [$admin, $store, $product];
    }

    public function test_product_model_returns_multiple_gallery_urls(): void
    {
        [$admin, $store, $product] = $this->createStoreWithProduct();

        $this->assertFalse($product->hasMultipleImages());
        $this->assertSame(['https://example.com/cover.jpg'], $product->galleryUrls());

        // Tambahkan foto galeri tambahan
        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'https://example.com/gallery-angle-2.jpg',
            'position' => 1,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'https://example.com/gallery-angle-3.jpg',
            'position' => 2,
        ]);

        $product->refresh();
        $this->assertTrue($product->hasMultipleImages());
        $this->assertCount(3, $product->galleryUrls());
        $this->assertSame([
            'https://example.com/cover.jpg',
            'https://example.com/gallery-angle-2.jpg',
            'https://example.com/gallery-angle-3.jpg',
        ], $product->galleryUrls());
    }

    public function test_product_page_renders_gallery_thumbnails_and_lightbox(): void
    {
        [$admin, $store, $product] = $this->createStoreWithProduct();

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'https://example.com/gallery-detail.jpg',
            'position' => 1,
        ]);

        $response = $this->get($product->url($store));

        $response->assertStatus(200);
        $response->assertSee('Lihat Galeri Penuh');
        $response->assertSee('pdp-thumbnails-rail');
        $response->assertSee('gallery-lightbox-overlay');
        $response->assertSee('galleryActiveImage');
    }

    public function test_admin_can_upload_multiple_gallery_images(): void
    {
        Storage::fake('public');
        Storage::fake('google');

        [$admin, $store, $product] = $this->createStoreWithProduct();

        $file1 = UploadedFile::fake()->image('angle1.jpg', 600, 600);
        $file2 = UploadedFile::fake()->image('angle2.jpg', 600, 600);

        $response = $this->actingAs($admin)->put(route('admin.products.update', [$store, $product]), [
            'name' => 'Kemeja Katun Putih Edit',
            'selling_price' => 135000,
            'status' => 'active',
            'gallery_images' => [$file1, $file2],
        ]);

        $response->assertRedirect(route('admin.products.index', $store));

        $this->assertDatabaseCount('product_images', 2);
        $this->assertSame(2, $product->images()->count());
    }

    public function test_admin_can_delete_gallery_image(): void
    {
        [$admin, $store, $product] = $this->createStoreWithProduct();

        $image = ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'https://example.com/to-delete.jpg',
            'position' => 1,
        ]);

        $response = $this->actingAs($admin)->deleteJson(route('admin.products.images.destroy', [$store, $product, $image]));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
    }
}

