<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['store_id', 'slug']);
            $table->index(['store_id', 'parent_id']);
            $table->index(['store_id', 'is_active']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->string('image_path')->nullable();
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['store_id', 'slug']);
            $table->unique(['store_id', 'sku']);
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
