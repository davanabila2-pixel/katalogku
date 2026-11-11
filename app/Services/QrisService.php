<?php

namespace App\Services;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * Mengubah QRIS statis toko menjadi QRIS dinamis yang membawa nominal (EMV tag 54),
 * sehingga saat discan aplikasi bank / e-wallet langsung menampilkan total pembayaran.
 */
class QrisService
{
    /**
     * Baca teks payload QRIS dari file gambar QR. Mengembalikan null bila tidak terbaca / bukan QRIS.
     */
    public function extractFromImage(string $path): ?string
    {
        try {
            $result = (new QRCode(new QROptions(['readerUseImagickIfAvailable' => false])))->readFromFile($path);
            $payload = trim((string) $result);
        } catch (\Throwable $e) {
            return null;
        }

        return $this->normalize($payload);
    }

    /**
     * Validasi & rapikan payload QRIS. Null bila tidak valid.
     */
    public function normalize(?string $payload): ?string
    {
        $payload = trim((string) $payload);

        if ($payload === '' || ! str_starts_with($payload, '000201') || ! $this->hasValidCrc($payload)) {
            return null;
        }

        return $this->hasQrisStructure($payload) ? $payload : null;
    }

    /**
     * QRIS minimal memuat tag 00 (format), 58 (negara), 59 (nama merchant) dan satu tag merchant account (26-51).
     */
    private function hasQrisStructure(string $payload): bool
    {
        $tags = $this->parse($payload);
        if ($tags === null) {
            return false;
        }

        $ids = array_column($tags, 0);
        $hasMerchantAccount = count(array_filter($ids, fn ($id) => $id >= '26' && $id <= '51')) > 0;

        return in_array('00', $ids, true) && in_array('58', $ids, true)
            && in_array('59', $ids, true) && $hasMerchantAccount;
    }

    /**
     * Buat payload QRIS dinamis dengan nominal tertentu.
     */
    public function makeDynamic(string $staticPayload, float $amount): ?string
    {
        $tags = $this->hasQrisStructure($staticPayload) ? $this->parse($staticPayload) : null;
        if ($tags === null || $amount <= 0) {
            return null;
        }

        $formatted = floor($amount) == $amount
            ? (string) (int) $amount
            : number_format($amount, 2, '.', '');

        $tags = array_values(array_filter($tags, fn ($t) => ! in_array($t[0], ['54', '63'], true)));

        foreach ($tags as &$tag) {
            if ($tag[0] === '01') {
                $tag[1] = '12'; // 11 = statis, 12 = dinamis
            }
        }
        unset($tag);

        $tags[] = ['54', $formatted];
        usort($tags, fn ($a, $b) => strcmp($a[0], $b[0]));

        $body = '';
        foreach ($tags as [$id, $value]) {
            $body .= $id.str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT).$value;
        }

        $body .= '6304';

        return $body.$this->crc16($body);
    }

    /**
     * Render payload menjadi SVG QR code.
     */
    public function renderSvg(string $payload): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
        ]);

        return (string) (new QRCode($options))->render($payload);
    }

    /**
     * Render payload menjadi PNG QR code resolusi tinggi (mudah dibaca dari galeri m-Banking / e-Wallet).
     */
    public function renderPng(string $payload): string
    {
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
            'quietzoneSize' => 4,
            'scale' => 12,
            'outputBase64' => false,
        ]);

        return (string) (new QRCode($options))->render($payload);
    }

    /**
     * @return array<int, array{0: string, 1: string}>|null
     */
    private function parse(string $payload): ?array
    {
        $tags = [];
        $pos = 0;
        $len = strlen($payload);

        while ($pos < $len) {
            if ($pos + 4 > $len) {
                return null;
            }

            $id = substr($payload, $pos, 2);
            $size = substr($payload, $pos + 2, 2);

            if (! ctype_digit($id) || ! ctype_digit($size)) {
                return null;
            }

            $size = (int) $size;
            if ($pos + 4 + $size > $len) {
                return null;
            }

            $tags[] = [$id, substr($payload, $pos + 4, $size)];
            $pos += 4 + $size;
        }

        return $tags;
    }

    private function hasValidCrc(string $payload): bool
    {
        if (strlen($payload) < 8 || substr($payload, -8, 4) !== '6304') {
            return false;
        }

        return strtoupper(substr($payload, -4)) === $this->crc16(substr($payload, 0, -4));
    }

    /**
     * CRC-16/CCITT-FALSE (poly 0x1021, init 0xFFFF) sesuai spesifikasi EMVCo QR.
     */
    private function crc16(string $data): string
    {
        $crc = 0xFFFF;

        for ($i = 0, $n = strlen($data); $i < $n; $i++) {
            $crc ^= ord($data[$i]) << 8;
            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
                $crc &= 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
