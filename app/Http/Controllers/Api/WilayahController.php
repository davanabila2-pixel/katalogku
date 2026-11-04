<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IndonesiaCity;
use App\Models\IndonesiaDistrict;
use App\Models\IndonesiaProvince;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WilayahController extends Controller
{
    /**
     * Dapatkan daftar seluruh provinsi di Indonesia.
     */
    public function provinces(Request $request): JsonResponse
    {
        $query = IndonesiaProvince::query()->orderBy('name', 'asc');

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where('name', 'like', "%{$q}%");
        }

        return response()->json($query->get(['id', 'name']));
    }

    /**
     * Dapatkan daftar kota/kabupaten berdasarkan ID provinsi atau pencarian nama.
     */
    public function cities(Request $request): JsonResponse
    {
        $query = IndonesiaCity::query()->orderBy('name', 'asc');

        if ($request->filled('province_id')) {
            $query->where('province_id', $request->input('province_id'));
        }

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where('name', 'like', "%{$q}%");
        }

        return response()->json($query->get(['id', 'province_id', 'name']));
    }

    /**
     * Dapatkan daftar kecamatan berdasarkan ID kota atau pencarian nama.
     */
    public function districts(Request $request): JsonResponse
    {
        $query = IndonesiaDistrict::query()->orderBy('name', 'asc');

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->input('city_id'));
        }

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where('name', 'like', "%{$q}%");
        }

        return response()->json($query->get(['id', 'city_id', 'name']));
    }

    /**
     * Cari ID provinsi, kota, dan kecamatan berdasarkan teks nama (reverse search).
     * Sangat berguna saat membuka form edit alamat yang sudah ada nama teksnya.
     */
    public function resolve(Request $request): JsonResponse
    {
        $cityName = trim($request->input('city', ''));
        $districtName = trim($request->input('district', ''));
        $provinceName = trim($request->input('province', ''));

        $province = null;
        $city = null;
        $district = null;

        $cleanCity = preg_replace('/^(kota|kabupaten|kab\.)\s+/i', '', $cityName);

        // 1. Jika nama kecamatan diberikan, cari kecamatan yang cocok
        if ($districtName) {
            $districtQuery = IndonesiaDistrict::where('name', 'like', "%{$districtName}%");
            if ($cleanCity) {
                $districtQuery->whereHas('city', function ($q) use ($cleanCity) {
                    $q->where('name', 'like', "%{$cleanCity}%");
                });
            }
            $district = $districtQuery->first();
            if ($district) {
                $city = $district->city;
                $province = $city?->province;
            }
        }

        // 2. Jika kota belum ditemukan dari kecamatan, cari kota langsung
        if (! $city && $cityName) {
            if (stripos($cityName, 'kota') !== false) {
                $city = IndonesiaCity::where('name', 'like', "KOTA {$cleanCity}%")->first();
            } elseif (stripos($cityName, 'kab') !== false) {
                $city = IndonesiaCity::where('name', 'like', "KABUPATEN {$cleanCity}%")->first();
            }

            if (! $city) {
                $city = IndonesiaCity::where('name', 'like', "%{$cleanCity}%")->first();
            }

            if ($city) {
                $province = $city->province;
            }
        }

        // 3. Fallback provinsi jika belum didapat
        if (! $province && $provinceName) {
            $province = IndonesiaProvince::where('name', 'like', "%{$provinceName}%")->first();
        }

        return response()->json([
            'province' => $province ? ['id' => $province->id, 'name' => $province->name] : null,
            'city' => $city ? ['id' => $city->id, 'name' => $city->name, 'province_id' => $city->province_id] : null,
            'district' => $district ? ['id' => $district->id, 'name' => $district->name, 'city_id' => $district->city_id] : null,
        ]);
    }
}
