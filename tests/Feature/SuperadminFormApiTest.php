<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tes endpoint API untuk form superadmin (toko & pengguna):
 * data form + simpan/perbarui tanpa reload halaman.
 */
class SuperadminFormApiTest extends TestCase
{
    use RefreshDatabase;

    protected function makeSuperadmin(): User
    {
        return User::create(['name' => 'Super Form', 'email' => 'super-form@toko.com', 'role' => 'superadmin', 'is_active' => true]);
    }

    public function test_superadmin_membuat_toko_lewat_api_dengan_admin_utama(): void
    {
        $superadmin = $this->makeSuperadmin();

        $response = $this->actingAs($superadmin)->postJson(route('api.superadmin.stores.store'), [
            'name' => 'Toko Cerah',
            'status' => 'active',
            'admin_email' => 'admin@cerah.com',
            'admin_name' => 'Admin Cerah',
        ]);

        $response->assertCreated();

        $store = Store::where('slug', 'toko-cerah')->first();
        $this->assertNotNull($store);
        $this->assertStringContainsString('/toko-cerah', $response->json('message'));

        $admin = User::where('email', 'admin@cerah.com')->first();
        $this->assertNotNull($admin);
        $this->assertSame('admin', $admin->role);
        $this->assertDatabaseHas('store_user', ['store_id' => $store->id, 'user_id' => $admin->id, 'is_primary' => true]);
    }

    public function test_form_data_toko_memuat_nilai_dan_admin_utama(): void
    {
        $superadmin = $this->makeSuperadmin();
        $admin = User::create(['name' => 'Admin Satu', 'email' => 'admin-satu@toko.com', 'role' => 'admin', 'is_active' => true]);
        $store = Store::create(['name' => 'Toko Satu', 'slug' => 'toko-satu-form', 'status' => 'active']);
        $store->users()->attach($admin->id, ['is_primary' => true]);

        $this->actingAs($superadmin)->getJson(route('api.superadmin.stores.form_data', $store))
            ->assertOk()
            ->assertJsonPath('store.id', $store->id)
            ->assertJsonPath('store.name', 'Toko Satu')
            ->assertJsonPath('admin_email', 'admin-satu@toko.com')
            ->assertJsonStructure(['store' => ['id', 'name', 'slug', 'description', 'contact_information', 'address', 'status', 'update_url'], 'admin_email']);
    }

    public function test_superadmin_memperbarui_toko_lewat_api(): void
    {
        $superadmin = $this->makeSuperadmin();
        $store = Store::create(['name' => 'Toko Lama', 'slug' => 'toko-lama-form', 'status' => 'active']);

        $response = $this->actingAs($superadmin)->putJson(route('api.superadmin.stores.update', $store), [
            'name' => 'Toko Baru',
            'slug' => 'toko-baru-form',
            'status' => 'inactive',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('301', $response->json('message'));

        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'name' => 'Toko Baru',
            'slug' => 'toko-baru-form',
            'status' => 'inactive',
        ]);
    }

    public function test_form_data_pengguna_memuat_daftar_toko_dan_toko_utama(): void
    {
        $superadmin = $this->makeSuperadmin();
        $admin = User::create(['name' => 'Admin Dua', 'email' => 'admin-dua@toko.com', 'role' => 'admin', 'is_active' => true]);
        $storeA = Store::create(['name' => 'Toko A', 'slug' => 'toko-a-form', 'status' => 'active']);
        $storeB = Store::create(['name' => 'Toko B', 'slug' => 'toko-b-form', 'status' => 'active']);
        $admin->stores()->attach($storeA->id, ['is_primary' => true]);
        $admin->stores()->attach($storeB->id, ['is_primary' => false]);

        $this->actingAs($superadmin)->getJson(route('api.superadmin.users.form_data', $admin))
            ->assertOk()
            ->assertJsonPath('user.store_ids', [$storeA->id, $storeB->id])
            ->assertJsonPath('user.primary_store_id', $storeA->id)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'role', 'is_active', 'store_ids', 'primary_store_id', 'update_url'],
                'stores' => [['id', 'name']],
            ]);
    }

    public function test_superadmin_memperbarui_pengguna_lewat_api(): void
    {
        $superadmin = $this->makeSuperadmin();
        $admin = User::create(['name' => 'Admin Ubah', 'email' => 'admin-ubah@toko.com', 'role' => 'admin', 'is_active' => true]);
        $storeA = Store::create(['name' => 'Toko UA', 'slug' => 'toko-ua-form', 'status' => 'active']);
        $storeB = Store::create(['name' => 'Toko UB', 'slug' => 'toko-ub-form', 'status' => 'active']);

        $this->actingAs($superadmin)->putJson(route('api.superadmin.users.update', $admin), [
            'name' => 'Admin Diubah',
            'role' => 'admin',
            'is_active' => 1,
            'stores' => [$storeA->id, $storeB->id],
            'primary_store' => $storeB->id,
        ])->assertOk()->assertJsonPath('message', 'Pengguna diperbarui.');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'name' => 'Admin Diubah', 'role' => 'admin', 'is_active' => true]);
        $this->assertDatabaseHas('store_user', ['user_id' => $admin->id, 'store_id' => $storeB->id, 'is_primary' => true]);
        $this->assertDatabaseHas('store_user', ['user_id' => $admin->id, 'store_id' => $storeA->id, 'is_primary' => false]);

        // Diubah menjadi superadmin: seluruh relasi toko dilepas.
        $this->actingAs($superadmin)->putJson(route('api.superadmin.users.update', $admin), [
            'name' => 'Admin Diubah',
            'role' => 'superadmin',
            'is_active' => 1,
        ])->assertOk();

        $this->assertDatabaseCount('store_user', 0);
    }

    public function test_superadmin_tidak_bisa_menurunkan_akun_sendiri_lewat_api(): void
    {
        $superadmin = $this->makeSuperadmin();

        $this->actingAs($superadmin)->putJson(route('api.superadmin.users.update', $superadmin), [
            'name' => $superadmin->name,
            'role' => 'admin',
            'is_active' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors(['role']);
    }

    public function test_admin_biasa_ditolak_di_api_form_superadmin(): void
    {
        $admin = User::create(['name' => 'Admin Biasa', 'email' => 'admin-biasa-form@toko.com', 'role' => 'admin', 'is_active' => true]);
        $store = Store::create(['name' => 'Toko Biasa', 'slug' => 'toko-biasa-form', 'status' => 'active']);

        $this->actingAs($admin)->postJson(route('api.superadmin.stores.store'), ['name' => 'X', 'status' => 'active'])->assertForbidden();
        $this->actingAs($admin)->getJson(route('api.superadmin.stores.form_data', $store))->assertForbidden();
        $this->actingAs($admin)->getJson(route('api.superadmin.users.form_data', $admin))->assertForbidden();
        $this->actingAs($admin)->putJson(route('api.superadmin.users.update', $admin), ['name' => 'X', 'role' => 'admin'])->assertForbidden();
    }
}
