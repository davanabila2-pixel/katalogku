<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('weight')->nullable()->default(null)->after('selling_price')
                ->comment('Berat produk dalam gram. Pada paket setelan (bundle), jika dikosongkan akan dihitung otomatis dari komponen.');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('weight');
        });
    }
};
