<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('qr_image_path')->nullable()->after('logo_path');
            $table->string('qr_account_name')->nullable()->after('qr_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['qr_image_path', 'qr_account_name']);
        });
    }
};

