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
        Schema::table('stores', function (Blueprint $table) {
            $table->string('province', 100)->nullable()->after('district');
        });

        Schema::table('store_customer_addresses', function (Blueprint $table) {
            $table->string('province', 100)->nullable()->after('district');
        });

        Schema::table('store_customer_profiles', function (Blueprint $table) {
            $table->string('province', 100)->nullable()->after('district');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('province');
        });

        Schema::table('store_customer_addresses', function (Blueprint $table) {
            $table->dropColumn('province');
        });

        Schema::table('store_customer_profiles', function (Blueprint $table) {
            $table->dropColumn('province');
        });
    }
};

