<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Str;

class SlugService
{
    /** Slug yang dicadangkan untuk route sistem — tidak boleh dipakai toko. */
    public const RESERVED = [
        'login', 'logout', 'register', 'admin', 'superadmin', 'dashboard',
        'api', 'oauth', 'auth', 'toko', 'kategori', 'produk', 'storage', 'scan', 'barcode',
        'build', 'assets', 'css', 'js', 'images', 'favicon.ico',
        'password', 'email', 'profil', 'profile', 'cart', 'checkout', 'terjual',
        'search', 'cari', 'sitemap.xml', 'robots.txt', '.well-known',
    ];

    public static function make(string $name): string
    {
        $slug = Str::slug($name);
        if ($slug === '') {
            $slug = 'toko';
        }

        return substr($slug, 0, 100);
    }

    public static function isReserved(string $slug): bool
    {
        return in_array(strtolower($slug), self::RESERVED, true);
    }

    protected static function unique(string $base, callable $exists, ?int $ignoreId = null): string
    {
        $slug = $base;
        $counter = 1;
        while ($exists($slug, $ignoreId)) {
            $counter++;
            $slug = substr($base, 0, 90).'-'.$counter;
        }

        return $slug;
    }

    public static function uniqueStoreSlug(string $name, ?int $ignoreId = null): string
    {
        $base = self::make($name);

        return self::unique($base, function (string $slug, ?int $ignore) {
            if (self::isReserved($slug)) {
                return true;
            }

            $query = Store::withTrashed()->where('slug', $slug);
            if ($ignore) {
                $query->where('id', '!=', $ignore);
            }

            return $query->exists();
        }, $ignoreId);
    }

    public static function uniqueCategorySlug(int $storeId, string $name, ?int $ignoreId = null): string
    {
        $base = self::make($name);

        return self::unique($base, function (string $slug, ?int $ignore) use ($storeId) {
            $query = Category::withTrashed()->where('store_id', $storeId)->where('slug', $slug);
            if ($ignore) {
                $query->where('id', '!=', $ignore);
            }

            return $query->exists();
        }, $ignoreId);
    }

    public static function uniqueProductSlug(int $storeId, string $name, ?int $ignoreId = null): string
    {
        $base = self::make($name);

        return self::unique($base, function (string $slug, ?int $ignore) use ($storeId) {
            $query = Product::withTrashed()->where('store_id', $storeId)->where('slug', $slug);
            if ($ignore) {
                $query->where('id', '!=', $ignore);
            }

            return $query->exists();
        }, $ignoreId);
    }
}
