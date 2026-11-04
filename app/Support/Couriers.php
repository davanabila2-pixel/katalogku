<?php

namespace App\Support;

/**
 * Master ekspedisi yang didukung. Kode mengikuti kode kurir Biteship.
 */
class Couriers
{
    /**
     * @return array<string, array{name: string, note: string}>
     */
    public static function all(): array
    {
        return [
            'jne' => ['name' => 'JNE Express', 'note' => 'Reguler, YES, OKE, JTR (Kargo)', 'badge' => 'Populer', 'color' => '#dc2626'],
            'sicepat' => ['name' => 'SiCepat Ekspres', 'note' => 'Reguler, BEST, GOKIL (Kargo)', 'badge' => 'Populer', 'color' => '#e11d48'],
            'jnt' => ['name' => 'J&T Express', 'note' => 'EZ (Reguler), J&T Super', 'badge' => 'Populer', 'color' => '#ef4444'],
            'tiki' => ['name' => 'TIKI', 'note' => 'Reguler, ONS, ECO, Trucking', 'badge' => 'Standar', 'color' => '#2563eb'],
            'pos' => ['name' => 'POS Indonesia', 'note' => 'Pos Reguler, Kilat Khusus, Nextday', 'badge' => 'BUMN', 'color' => '#ea580c'],
            'anteraja' => ['name' => 'AnterAja', 'note' => 'Reguler, Next Day, Same Day', 'badge' => 'Standar', 'color' => '#8b5cf6'],
            'wahana' => ['name' => 'Wahana Express', 'note' => 'Ekonomis, Layanan Normal', 'badge' => 'Hemat', 'color' => '#059669'],
            'ninja' => ['name' => 'Ninja Xpress', 'note' => 'Reguler Standar', 'badge' => 'Standar', 'color' => '#991b1b'],
            'lion' => ['name' => 'Lion Parcel', 'note' => 'REGPACK, BOSSPACK, JAGOPACK', 'badge' => 'Standar', 'color' => '#b91c1c'],
            'ide' => ['name' => 'IDexpress', 'note' => 'Reguler, iDlite (Kecil)', 'badge' => 'Hemat', 'color' => '#d97706'],
            'gojek' => ['name' => 'GoSend (Gojek)', 'note' => 'Instant, Same Day', 'badge' => 'Instant', 'color' => '#16a34a'],
            'grab' => ['name' => 'GrabExpress (Grab)', 'note' => 'Instant, Same Day', 'badge' => 'Instant', 'color' => '#15803d'],
        ];
    }

    /**
     * @return string[]
     */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    /**
     * Saring input agar hanya berisi kode ekspedisi yang dikenal.
     *
     * @param  mixed  $codes
     * @return string[]
     */
    public static function sanitize($codes): array
    {
        if (! is_array($codes)) {
            return [];
        }

        return array_values(array_intersect(self::codes(), array_map('strtolower', array_map('strval', $codes))));
    }
}

