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
        Schema::create('indonesia_provinces', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('name', 100)->index();
            $table->timestamps();
        });

        Schema::create('indonesia_cities', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('province_id', 10)->index();
            $table->string('name', 100)->index();
            $table->timestamps();
        });

        Schema::create('indonesia_districts', function (Blueprint $table) {
            $table->string('id', 15)->primary();
            $table->string('city_id', 10)->index();
            $table->string('name', 100)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indonesia_districts');
        Schema::dropIfExists('indonesia_cities');
        Schema::dropIfExists('indonesia_provinces');
    }
};

