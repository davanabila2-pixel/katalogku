<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah koordinat dan kode pos pada tabel stores
        Schema::table('stores', function (Blueprint $table) {
            $table->string('postal_code', 10)->nullable()->after('address');
            $table->decimal('latitude', 10, 8)->nullable()->after('postal_code');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('city', 100)->nullable()->after('longitude');
            $table->string('district', 100)->nullable()->after('city');
        });

        // 2. Tambah koordinat dan kode pos pada tabel store_customer_profiles
        Schema::table('store_customer_profiles', function (Blueprint $table) {
            $table->string('postal_code', 10)->nullable()->after('address');
            $table->decimal('latitude', 10, 8)->nullable()->after('postal_code');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('city', 100)->nullable()->after('longitude');
            $table->string('district', 100)->nullable()->after('city');
        });

        // 3. Tambah rincian ongkir & koordinat pengiriman pada tabel orders
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_courier', 50)->nullable()->after('buyer_notes');
            $table->string('shipping_service', 50)->nullable()->after('shipping_courier');
            $table->decimal('shipping_fee', 14, 2)->default(0)->after('shipping_service');
            $table->string('shipping_etd', 50)->nullable()->after('shipping_fee');
            $table->string('buyer_postal_code', 10)->nullable()->after('shipping_etd');
            $table->decimal('buyer_latitude', 10, 8)->nullable()->after('buyer_postal_code');
            $table->decimal('buyer_longitude', 11, 8)->nullable()->after('buyer_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_courier',
                'shipping_service',
                'shipping_fee',
                'shipping_etd',
                'buyer_postal_code',
                'buyer_latitude',
                'buyer_longitude',
            ]);
        });

        Schema::table('store_customer_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'postal_code',
                'latitude',
                'longitude',
                'city',
                'district',
            ]);
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'postal_code',
                'latitude',
                'longitude',
                'city',
                'district',
            ]);
        });
    }
};
