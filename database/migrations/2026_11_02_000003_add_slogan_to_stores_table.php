<?php

use App\Models\Store;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('slogan', 255)->nullable()->after('name');
        });

        // Pisahkan slogan dan deskripsi untuk data toko Byvii yang sudah ada
        try {
            $byvii = Store::where('slug', 'byvii')->first();
            if ($byvii && ! empty($byvii->description) && str_contains($byvii->description, 'Secondhand, First Choice.')) {
                $cleanedDesc = trim(str_replace('Secondhand, First Choice.', '', $byvii->description));
                $byvii->update([
                    'slogan' => 'Secondhand, First Choice.',
                    'description' => $cleanedDesc,
                ]);
            }
        } catch (\Throwable $e) {
            // Ignore if in test environment without seeded store
        }
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('slogan');
        });
    }
};
