<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('contact_information')->nullable();
            $table->text('address')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
        });

        Schema::create('store_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['store_id', 'user_id']);
            $table->index('is_primary');
        });

        Schema::create('store_slug_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('old_slug')->unique();
            $table->string('new_slug');
            $table->timestamps();
            $table->index('store_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_slug_histories');
        Schema::dropIfExists('store_user');
        Schema::dropIfExists('stores');
    }
};
