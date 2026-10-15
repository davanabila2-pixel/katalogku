<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('batch_code')->unique();
            $table->date('purchase_date');
            $table->string('supplier_name')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('additional_cost', 15, 2)->default(0);
            $table->enum('status', ['active', 'cancelled'])->default('active');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index('store_id');
            $table->index('purchase_date');
        });

        Schema::create('purchase_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_purchase_price', 15, 2);
            $table->decimal('allocated_additional_cost', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('purchase_batch_id');
            $table->index('product_id');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('movement_type', ['in', 'out', 'adjustment', 'cancel']);
            $table->integer('quantity');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['product_id', 'created_at']);
            $table->index('store_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('purchase_batch_items');
        Schema::dropIfExists('purchase_batches');
    }
};
