<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipping_rate_caches', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key', 64)->unique();
            $table->string('origin_postal', 20)->nullable()->index();
            $table->string('destination_postal', 20)->nullable()->index();
            $table->string('origin_city', 100)->nullable();
            $table->string('destination_city', 100)->nullable();
            $table->string('origin_district', 100)->nullable();
            $table->string('destination_district', 100)->nullable();
            $table->decimal('origin_lat', 10, 7)->nullable();
            $table->decimal('origin_lng', 10, 7)->nullable();
            $table->decimal('destination_lat', 10, 7)->nullable();
            $table->decimal('destination_lng', 10, 7)->nullable();
            $table->string('couriers', 255)->nullable();
            $table->unsignedInteger('weight_grams')->default(1000);
            $table->json('rates');
            $table->json('origin_meta')->nullable();
            $table->json('destination_meta')->nullable();
            $table->unsignedInteger('hit_count')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rate_caches');
    }
};
