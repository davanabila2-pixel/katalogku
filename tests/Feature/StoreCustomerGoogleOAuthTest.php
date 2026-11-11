<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\StoreCustomerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class StoreCustomerGoogleOAuthTest extends TestCase
{
    use RefreshDatabase;

    private function createSampleStore(string $name = 'Byvii Fashion', string $slug = 'byvii'): Store
    {
        return Store::create([
            'name' => $name,
            'slug' => $slug,
            'slogan' => "Slogan {$name}",
            'description' => "Deskripsi {$name}",
            'status' => 'active',
        ]);
    }

    private function mockGoogleUser(string $id, string $email, string $name): void
    {
        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn($id);
        $abstractUser->shouldReceive('getEmail')->andReturn($email);
        $abstractUser->shouldReceive('getName')->andReturn($name);

        $provider = Mockery::mock(SocialiteProvider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('redirectUrl')->andReturnSelf();
        $provider->shouldReceive('with')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_halaman_login_toko_menampilkan_tombol_google_oauth(): void
    {
        $store = $this->createSampleStore();

        $response = $this->get(route('store.login', $store->slug));

        $response->assertOk();
        $response->assertSee('Masuk dengan Google');
        $response->assertSee(route('store.login.google', $store->slug));
        $response->assertSee('store-auth-google-btn', false);
        $response->assertSee('store-auth-divider', false);
    }

    public function test_halaman_daftar_toko_menampilkan_tombol_google_oauth(): void
    {
        $store = $this->createSampleStore();

        $response = $this->get(route('store.register', $store->slug));

        $response->assertOk();
        $response->assertSee('Daftar Cepat dengan Google');
        $response->assertSee(route('store.login.google', $store->slug));
        $response->assertSee('store-auth-google-btn', false);
        $response->assertSee('store-auth-divider', false);
    }

    public function test_redirect_google_gagal_ramah_jika_kredensial_belum_dikonfigurasi(): void
    {
        $store = $this->createSampleStore();

        Config::set('services.google.client_id', null);
        Config::set('services.google.client_secret', null);

        $response = $this->get(route('store.login.google', $store->slug));

        $response->assertRedirect(route('store.login', $store->slug));
        $response->assertSessionHasErrors('email');
    }

    public function test_redirect_google_menyimpan_session_store_slug_dan_return_url(): void
    {
        $store = $this->createSampleStore();

        Config::set('services.google.client_id', 'test-client-id');
        Config::set('services.google.client_secret', 'test-client-secret');

        $provider = Mockery::mock(SocialiteProvider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('redirectUrl')->andReturnSelf();
        $provider->shouldReceive('with')->andReturnSelf();
        $provider->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $targetUrl = "/{$store->slug}/checkout?product_id=123&quantity=2";
        $response = $this->get(route('store.login.google', [
            'storeSlug' => $store->slug,
            'return_url' => $targetUrl,
        ]));

        $response->assertRedirect('https://accounts.google.com/o/oauth2/auth');
        $this->assertEquals($store->slug, session('customer_store_slug'));
        $this->assertEquals($targetUrl, session('customer_return_url'));
    }

    public function test_callback_google_berhasil_membuat_user_customer_baru_dan_profil_toko(): void
    {
        $store = $this->createSampleStore();

        $this->mockGoogleUser('google-sub-999', 'pelanggan.baru@gmail.com', 'Pelanggan Baru');

        $response = $this->withSession([
            'customer_store_slug' => $store->slug,
        ])->get(route('auth.google.callback'));

        $response->assertRedirect(route('store.show', $store->slug));
        $this->assertAuthenticated();

        $user = User::where('email', 'pelanggan.baru@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('google-sub-999', $user->google_id);
        $this->assertEquals('user', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->email_verified_at);

        $profile = StoreCustomerProfile::where('store_id', $store->id)
            ->where('user_id', $user->id)
            ->first();
        $this->assertNotNull($profile);
        $this->assertEquals('Pelanggan Baru', $profile->name);
    }

    public function test_callback_google_berhasil_login_user_yang_sudah_ada(): void
    {
        $store = $this->createSampleStore();

        $existingUser = User::create([
            'name' => 'User Lama',
            'email' => 'user.lama@gmail.com',
            'password' => bcrypt('password123'),
            'role' => 'user',
            'is_active' => true,
        ]);

        $this->mockGoogleUser('google-sub-888', 'user.lama@gmail.com', 'User Lama');

        $response = $this->withSession([
            'customer_store_slug' => $store->slug,
        ])->get(route('auth.google.callback'));

        $response->assertRedirect(route('store.show', $store->slug));
        $this->assertAuthenticatedAs($existingUser);

        $existingUser->refresh();
        $this->assertEquals('google-sub-888', $existingUser->google_id);
        $this->assertNotNull($existingUser->email_verified_at);

        $profile = StoreCustomerProfile::where('store_id', $store->id)
            ->where('user_id', $existingUser->id)
            ->first();
        $this->assertNotNull($profile);
    }

    public function test_callback_google_menolak_user_yang_dinonaktifkan(): void
    {
        $store = $this->createSampleStore();

        $deactivatedUser = User::create([
            'name' => 'User Nonaktif',
            'email' => 'banned@gmail.com',
            'password' => bcrypt('password123'),
            'role' => 'user',
            'is_active' => false,
        ]);

        $this->mockGoogleUser('google-banned', 'banned@gmail.com', 'User Nonaktif');

        $response = $this->withSession([
            'customer_store_slug' => $store->slug,
        ])->get(route('auth.google.callback'));

        $response->assertRedirect(route('store.login', $store->slug));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_callback_google_mengarahkan_ke_return_url_checkout(): void
    {
        $store = $this->createSampleStore();

        $this->mockGoogleUser('google-sub-777', 'checkout.user@gmail.com', 'Checkout Customer');

        $targetUrl = "/{$store->slug}/checkout?product_id=30030&quantity=1";

        $response = $this->withSession([
            'customer_store_slug' => $store->slug,
            'customer_return_url' => $targetUrl,
        ])->get(route('auth.google.callback'));

        $response->assertRedirect($targetUrl);
        $this->assertAuthenticated();
    }

    public function test_callback_google_via_rute_toko_langsung_berhasil(): void
    {
        $store = $this->createSampleStore();

        $this->mockGoogleUser('google-sub-666', 'direct.user@gmail.com', 'Direct Route User');

        $response = $this->get(route('store.login.google.callback', $store->slug));

        $response->assertRedirect(route('store.show', $store->slug));
        $this->assertAuthenticated();

        $user = User::where('email', 'direct.user@gmail.com')->first();
        $this->assertNotNull($user);
    }

    public function test_callback_google_menangani_kegagalan_autentikasi_secara_ramah(): void
    {
        $store = $this->createSampleStore();

        $provider = Mockery::mock(SocialiteProvider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('redirectUrl')->andReturnSelf();
        $provider->shouldReceive('user')->andThrow(new \Exception('Google OAuth failed'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->withSession([
            'customer_store_slug' => $store->slug,
        ])->get(route('auth.google.callback'));

        $response->assertRedirect(route('store.login', $store->slug));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_callback_google_tetap_berhasil_meskipun_session_hilang_menggunakan_state_payload(): void
    {
        $store = $this->createSampleStore();

        $this->mockGoogleUser('google-sub-state', 'state.user@gmail.com', 'State User');

        $statePayload = base64_encode(json_encode([
            'store_slug' => $store->slug,
            'return_url' => "/{$store->slug}/checkout?product_id=99",
            'sig' => sha1($store->slug . config('app.key')),
        ]));

        // Request callback TANPA session sama sekali (simulasi session cookie hilang saat redirect)
        $response = $this->get(route('auth.google.callback', ['state' => $statePayload]));

        $response->assertRedirect("/{$store->slug}/checkout?product_id=99");
        $this->assertAuthenticated();

        $user = User::where('email', 'state.user@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('user', $user->role);
    }

    public function test_redirect_uri_selalu_berupa_url_absolut_meskipun_config_berisi_path_relatif(): void
    {
        $store = $this->createSampleStore();

        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.google.redirect' => '/auth/google/callback',
        ]);

        $response = $this->get("https://katalogku.vercel.app/{$store->slug}/auth/google");

        $response->assertRedirect();
        $targetUrl = $response->headers->get('Location');
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/auth', $targetUrl);

        parse_str(parse_url($targetUrl, PHP_URL_QUERY) ?? '', $queryParams);
        $this->assertEquals('https://katalogku.vercel.app/auth/google/callback', $queryParams['redirect_uri'] ?? null);
    }
}

