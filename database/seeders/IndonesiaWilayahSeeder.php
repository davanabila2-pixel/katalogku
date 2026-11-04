<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class IndonesiaWilayahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baseDir = database_path('data/wilayah');

        // 1. Seed Provinces
        $provFile = "{$baseDir}/provinces.csv";
        if (File::exists($provFile)) {
            $handle = fopen($provFile, 'r');
            $provinces = [];
            $now = now();
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) >= 2) {
                    $provinces[] = [
                        'id' => trim($row[0]),
                        'name' => trim($row[1]),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            fclose($handle);

            if (! empty($provinces)) {
                DB::table('indonesia_provinces')->insertOrIgnore($provinces);
            }
        }

        // 2. Seed Cities / Regencies
        $regFile = "{$baseDir}/regencies.csv";
        if (File::exists($regFile)) {
            $handle = fopen($regFile, 'r');
            $cities = [];
            $now = now();
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) >= 3) {
                    $cities[] = [
                        'id' => trim($row[0]),
                        'province_id' => trim($row[1]),
                        'name' => trim($row[2]),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if (count($cities) >= 500) {
                        DB::table('indonesia_cities')->insertOrIgnore($cities);
                        $cities = [];
                    }
                }
            }
            fclose($handle);

            if (! empty($cities)) {
                DB::table('indonesia_cities')->insertOrIgnore($cities);
            }
        }

        // 3. Seed Districts
        $distFile = "{$baseDir}/districts.csv";
        if (File::exists($distFile)) {
            $handle = fopen($distFile, 'r');
            $districts = [];
            $now = now();
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) >= 3) {
                    $districts[] = [
                        'id' => trim($row[0]),
                        'city_id' => trim($row[1]),
                        'name' => trim($row[2]),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if (count($districts) >= 500) {
                        DB::table('indonesia_districts')->insertOrIgnore($districts);
                        $districts = [];
                    }
                }
            }
            fclose($handle);

            if (! empty($districts)) {
                DB::table('indonesia_districts')->insertOrIgnore($districts);
            }
        }
    }
}

