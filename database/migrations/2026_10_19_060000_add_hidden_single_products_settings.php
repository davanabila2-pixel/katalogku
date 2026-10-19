<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_hidden_from_catalog')->default(false)->after('is_bundle');
            $table->index(['store_id', 'is_hidden_from_catalog']);
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->boolean('hide_component_products')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('hide_component_products');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['store_id', 'is_hidden_from_catalog']);
            $table->dropColumn('is_hidden_from_catalog');
        });
    }
};
