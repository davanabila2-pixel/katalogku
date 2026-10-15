# Katalog — Sistem Katalog Penjualan Multi-Toko

Aplikasi katalog penjualan multi-toko: satu aplikasi Laravel, satu domain utama,
satu database MySQL. Setiap toko punya katalog publik sendiri berdasarkan slug
(`/{slug-toko}`), mengelola kategori, produk, batch pembelian, dan stoknya sendiri.

Desain mengikuti `DESIGN-meta.md` (canvas putih, CTA commerce cobalt `#0064E0`,
tombol pill, card rounded, fotografi produk sebagai fokus).

## Fitur

- 3 peran: **Superadmin** (lintas toko), **Admin Toko** (satu toko, isolasi IDOR),
  **Pengunjung** (katalog publik tanpa login).
- URL toko `/{slug-toko}`, kategori `/{slug}/kategori/{kategori}[/{sub}]`,
  produk `/{slug}/produk/{slug-produk}`. Route sistem (`/login`, `/admin`,
  `/superadmin`, `/api`, …) didefinisikan duluan + slug cadangan divalidasi.
- Kategori bertingkat per toko (maks 3 tingkat, anti-siklus, slug unik per toko).
- Produk (decimal untuk uang, status draft/aktif/nonaktif, arsip aman).
- Batch pembelian transaksional: total = Σ(qty × harga) + biaya tambahan
  (dialokasikan proporsional, tidak dihitung ganda), rollback penuh saat gagal.
- Stok: satu sumber kebenaran (`products.stock` + `stock_movements` ditulis atomik
  dengan row-lock dalam satu transaksi). HPP memakai **rata-rata tertimbang**
  (lihat `app/Services/HppService.php`); selisih harga jual − HPP disebut
  **estimasi margin** (bukan laba terealisasi).
- Login Google via Socialite (tidak pernah membuat Superadmin otomatis).
- Gambar via Google Drive API (`GoogleDriveService`) dengan fallback lokal bila
  kredensial belum dikonfigurasi. Hanya metadata + file ID yang disimpan di DB.
- Audit log untuk perubahan penting (tanpa password/token/secret).
- 37 automated test (114 asersi), database testing terpisah (sqlite `:memory:`).

## Persyaratan

- PHP ^8.2 (ekstensi: mbstring, openssl, pdo_mysql, tokenizer, xml, ctype, json, fileinfo)
- Composer 2, MySQL/MariaDB 8+, Node 20 (opsional — frontend memakai CSS vanilla,
  tidak perlu build)
- phpMyAdmin untuk administrasi database (opsional)

## Instalasi lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Buat database via phpMyAdmin (atau CLI):

```sql
CREATE DATABASE katalog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Sesuaikan `.env` (`DB_DATABASE=katalog`, dst), lalu:

```bash
php artisan migrate --force
php artisan storage:link
SUPERADMIN_EMAIL=superadmin@example.com SUPERADMIN_PASSWORD=yang-kuat \
  php artisan db:seed --force
php artisan serve
```

Buka `http://localhost:8000`. Login sebagai Superadmin, buat toko + tautkan admin.

> Demo lokal (opsional, JANGAN di production):
> `php artisan db:seed --class=Database\Seeders\DemoSeeder` → `/toko-1`, `/toko-2`.

## Konfigurasi `.env` penting

| Key | Keterangan |
|---|---|
| `DB_*` | Koneksi MySQL |
| `GOOGLE_CLIENT_ID/SECRET/REDIRECT_URI` | Login Google (lihat bawah) |
| `GOOGLE_DRIVE_*` | Storage gambar (lihat bawah) |
| `SUPERADMIN_*` | Dipakai seeder untuk Superadmin pertama |

## Google OAuth (login)

1. [Google Cloud Console](https://console.cloud.google.com) → buat project → OAuth consent screen (External).
2. Credentials → Create OAuth client ID (Web application).
3. Authorized redirect URI: `https://namadomain.com/auth/google/callback`
   (lokal: `http://localhost:8000/auth/google/callback`).
4. Isi `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`.
5. Scope login: `openid email profile` saja (minimum). Scope Drive dipisah (bawah).

Perilaku: email yang sudah terdaftar ditautkan (`google_id`); akun baru selalu
`role=admin` aktif; Superadmin TIDAK PERNAH dibuat otomatis; session diregenerasi.

## Google Drive (storage gambar)

1. Di project yang sama (atau terpisah), aktifkan **Google Drive API**.
2. Buat OAuth client (Desktop/Manual) → dapatkan **refresh token** dengan scope
   `https://www.googleapis.com/auth/drive.file` saja (minimum).
3. Buat folder root di Drive (mis. `Katalog/`) → catat folder ID dari URL.
4. Isi `GOOGLE_DRIVE_CLIENT_ID/SECRET/REFRESH_TOKEN/ROOT_FOLDER_ID`.

Struktur otomatis: `Katalog/ → store-{id}-{slug}/ → Produk/`.
Folder memakai **ID toko** sebagai identifier utama sehingga perubahan slug
tidak memutus relasi file. File katalog di-share `anyone:reader`;
token tidak pernah dikirim ke browser; ada fallback image bila gagal.

**Bila kredensial belum tersedia**: aplikasi tetap jalan — upload memakai disk
lokal (`provider=local`) dan bisa dimigrasi nanti tanpa merusak data.

## Menjalankan test

```bash
php artisan test
```

37 test, ±114 asersi: slug & routing, isolasi peran, kategori, batch & stok,
HPP, validasi Drive, dan kebocoran data sensitif ke publik.

## Deployment cPanel (ringkas)

1. Upload semua file kecuali `vendor/` dan `node_modules/`; jalankan
   `composer install --no-dev --optimize-autoloader` via SSH/terminal cPanel
   (atau upload `vendor/` dari lokal bila tanpa SSH).
2. Arahkan document root ke `public/`. Buat database + user via cPanel,
   import/isi `.env` production (`APP_ENV=production`, `APP_DEBUG=false`).
3. `php artisan migrate --force` (backup dulu; jangan migration destruktif
   tanpa persetujuan), `php artisan storage:link`
   (bila gagal, buat symlink `public/storage → storage/app/public` manual),
   `php artisan config:cache && php artisan route:cache`.
4. Permission: `storage/` dan `bootstrap/cache/` writable (755/775 sesuai handler).
5. Cron scheduler (opsional): `php artisan schedule:run` tiap menit.

## Troubleshooting

| Gejala | Solusi |
|---|---|
| Gambar 404 | Pastikan `storage:link` ada; cek `storage/app/public` writable |
| Login Google gagal | Cocokkan redirect URI persis (termasuk skema http/https) |
| Upload selalu lokal | Lengkapi `GOOGLE_DRIVE_*`; cek log `storage/logs` (tanpa secret) |
| 403 di area admin | Pastikan user ditautkan ke toko (`superadmin → Pengguna`) |
| Route toko menelan `/login` | Route sistem sudah didefinisikan duluan — jangan pindahkan blok katalog dinamis ke atas |

## Asumsi & default aman

- HPP: rata-rata tertimbang (terdokumentasi di `HppService`).
- Satu admin utama per toko ditegakkan di aplikasi (pivot `is_primary` unik
  secara logis); skema siap untuk anggota tim tambahan.
- Satu kategori per produk. Produk boleh tanpa kategori (`category_id` nullable
  konsisten di DB + validasi).
- Keranjang/checkout/pembayaran di luar cakupan tahap awal.
