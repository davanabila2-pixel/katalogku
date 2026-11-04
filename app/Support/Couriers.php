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
            'jne' => ['name' => 'JNE', 'note' => 'Reguler, YES, OKE'],
            'sicepat' => ['name' => 'SiCepat', 'note' => 'Reguler, BEST'],
            'jnt' => ['name' => 'J&T Express', 'note' => 'EZ Reguler'],
            'tiki' => ['name' => 'TIKI', 'note' => 'Reguler, ONS'],
            'pos' => ['name' => 'POS Indonesia', 'note' => 'Pos Reguler, Kilat Khusus'],
            'anteraja' => ['name' => 'AnterAja', 'note' => 'Reguler, Next Day'],
            'gojek' => ['name' => 'GoSend', 'note' => 'Instant, Same Day'],
            'grab' => ['name' => 'GrabExpress', 'note' => 'Instant, Same Day'],
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

