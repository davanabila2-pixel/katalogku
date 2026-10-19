<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_bundle')->default(false)->after('category_id');
        });

        Schema::create('product_bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('item_product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['bundle_id', 'item_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_bundles');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_bundle');
        });
    }
};
