<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('label', 50)->default('Alamat Utama');
            $table->string('recipient_name', 100);
            $table->string('phone', 30)->nullable();
            $table->text('address');
            $table->string('postal_code', 10)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['store_id', 'user_id']);
        });

        // Migrasikan alamat yang sudah ada dari store_customer_profiles ke store_customer_addresses
        $existingProfiles = DB::table('store_customer_profiles')
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->get();

        foreach ($existingProfiles as $profile) {
            DB::table('store_customer_addresses')->insert([
                'store_id' => $profile->store_id,
                'user_id' => $profile->user_id,
                'label' => 'Alamat Utama',
                'recipient_name' => $profile->name ?: 'Pelanggan',
                'phone' => $profile->phone ?: '',
                'address' => $profile->address,
                'postal_code' => $profile->postal_code,
                'city' => $profile->city,
                'district' => $profile->district,
                'latitude' => $profile->latitude,
                'longitude' => $profile->longitude,
                'notes' => $profile->notes,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('store_customer_addresses');
    }
};

