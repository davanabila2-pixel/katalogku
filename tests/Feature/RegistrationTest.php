<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function validUser(array $over = []): array
    {
        return array_merge([
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $over);
    }

    public function test_daftar_sebagai_user_hanya_membuat_akun(): void
    {
        $this->post('/register', $this->validUser(['account_type' => 'user']))
            ->assertRedirect(route('admin.nostore'));

        $user = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertSame('admin', $user->role);
        $this->assertEquals(0, $user->stores()->count());
        $this->assertAuthenticatedAs($user);
    }

    public function test_daftar_sebagai_toko_membuat_toko_dan_admin_utama(): void
    {
        $this->post('/register', $this->validUser([
            'account_type' => 'toko',
            'store_name' => 'Thrift Premium',
            'store_description' => 'Preloved pilihan.',
        ]))->assertRedirect();

        $user = User::where('email', 'budi@example.com')->firstOrFail();
        $store = Store::where('slug', 'thrift-premium')->firstOrFail();

        $this->assertTrue($user->isPrimaryOf($store->id));
        $this->assertSame('active', $store->status);
        $this->assertAuthenticatedAs($user);

        // Dashboard tokonya bisa dibuka
        $this->actingAs($user)->get(route('admin.dashboard', $store))->assertOk();
        // Katalog publiknya hidup
        $this->get('/thrift-premium')->assertOk()->assertSee('Thrift Premium');
    }

    public function test_daftar_toko_tanpa_nama_toko_ditolak(): void
    {
        $this->post('/register', $this->validUser(['account_type' => 'toko']))
            ->assertSessionHasErrors('store_name');

        $this->assertDatabaseMissing('users', ['email' => 'budi@example.com']);
        $this->assertEquals(0, Store::count());
    }

    public function test_nama_toko_kembar_mendapat_slug_unik(): void
    {
        Store::create(['name' => 'Toko 1', 'status' => 'active']);

        $this->post('/register', $this->validUser([
            'account_type' => 'toko', 'store_name' => 'Toko 1',
        ]))->assertRedirect();

        $store = Store::where('slug', 'toko-1-2')->firstOrFail();
        $this->get('/toko-1-2')->assertOk();
        $this->assertTrue(User::where('email', 'budi@example.com')->first()->isPrimaryOf($store->id));
    }

    public function test_nama_toko_terlarang_mendapat_slug_aman(): void
    {
        $this->post('/register', $this->validUser([
            'account_type' => 'toko', 'store_name' => 'Login',
        ]))->assertRedirect();

        $store = Store::latest('id')->firstOrFail();
        $this->assertNotSame('login', $store->slug);
        $this->post('/logout');
        $this->get('/login')->assertOk()->assertSee('Masuk');
    }

    public function test_user_login_dapat_membuat_toko_sendiri(): void
    {
        $user = User::create(['name' => 'Cici', 'email' => 'cici@example.com', 'role' => 'admin', 'is_active' => true]);

        $this->actingAs($user)->post(route('admin.stores.store'), [
            'store_name' => 'Fashion Byvii',
        ])->assertRedirect();

        $store = Store::where('slug', 'fashion-byvii')->firstOrFail();
        $this->assertTrue($user->fresh()->isPrimaryOf($store->id));
        $this->actingAs($user)->get(route('admin.dashboard', $store))->assertOk();
    }

    public function test_guest_tidak_dapat_membuat_toko(): void
    {
        $this->post(route('admin.stores.store'), ['store_name' => 'X'])->assertRedirect('/login');
        $this->assertEquals(0, Store::count());
    }
}
