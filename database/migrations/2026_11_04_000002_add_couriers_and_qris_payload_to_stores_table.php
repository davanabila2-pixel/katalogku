<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // Daftar kode ekspedisi aktif per toko. NULL = ikuti default (semua aktif).
            $table->json('active_couriers')->nullable();
            // Payload teks QRIS statis hasil baca gambar QR, dipakai membuat QRIS dinamis bernominal.
            $table->text('qris_payload')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['active_couriers', 'qris_payload']);
        });
    }
};

