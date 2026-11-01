@extends('layouts.app')
@section('content')

{{-- Satu-satunya permukaan gelap di halaman ini: fokus tunggal untuk judul project. --}}
<div class="hero">
  <h1>Katalog penjualan multi-toko, dari satu aplikasi Laravel.</h1>
  <p>Satu domain, satu database. Tiap toko berdiri sendiri dengan katalog publik, kategori bertingkat, produk, batch pembelian, dan stok yang ditulis atomik.</p>
  <div class="hero-cta">
    <a class="btn btn-primary" href="{{ route('register') }}">Daftar sebagai Toko</a>
    <a class="btn btn-secondary" href="{{ route('stores.index') }}">Lihat semua toko</a>
  </div>
  <p class="hero-alt">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
  <ul class="hero-facts">
    <li>3 peran dengan batas berbeda</li>
    <li>Kategori sampai 3 tingkat</li>
    <li>114 test otomatis, 620 asersi</li>
  </ul>
</div>

{{-- Motif identitas halaman ini adalah pola route-nya, karena routing memang inti project. --}}
<section class="lp-split" aria-labelledby="lp-alamat">
  <div class="lp-split-aside">
    <h2 class="section-title" id="lp-alamat">Setiap toko punya alamatnya sendiri</h2>
    <p class="section-sub">Route sistem didaftarkan lebih dulu, lalu route toko diberi pola slug sendiri. Karena itu /login, /admin, dan /superadmin tidak pernah tertelan katalog.</p>
  </div>
  <div class="lp-split-main">
    <div class="lp-route">
      <code>/{slug-toko}</code>
      <p>Katalog publik satu toko. Kalau slug diganti, alamat lama di-redirect 301 ke alamat barunya.</p>
    </div>
    <div class="lp-route">
      <code>/{slug-toko}/kategori/{kategori}</code>
      <p>Kategori milik toko itu sendiri, bertingkat sampai tiga level dan dilarang berputar kembali ke induknya.</p>
    </div>
    <div class="lp-route">
      <code>/{slug-toko}/produk/{slug-produk}</code>
      <p>Halaman produk: nama, harga, sisa stok, isi paket, dan produk terkait.</p>
    </div>
  </div>
</section>

{{-- Pita abu lebar penuh: satu-satunya perubahan latar, supaya daftar peran tidak menyatu dengan bagian lain. --}}
<section class="lp-roles" aria-labelledby="lp-peran">
  <h2 class="section-title" id="lp-peran">Tiga peran dengan lingkup berbeda</h2>
  <p class="section-sub">Batasnya dipegang middleware dan method model, bukan halaman terpisah.</p>

  <div class="lp-role">
    <div class="lp-role-head">
      <h3>Superadmin</h3>
      <span class="lp-role-scope">Lintas toko</span>
    </div>
    <p>Membuat dan menonaktifkan toko, menautkan pengguna ke toko, serta meninjau kategori, produk, batch, stok, dan audit log di semua toko. Akun superadmin tidak pernah dibuat otomatis oleh login Google.</p>
  </div>

  <div class="lp-role">
    <div class="lp-role-head">
      <h3>Admin Toko</h3>
      <span class="lp-role-scope">Satu toko</span>
    </div>
    <p>Mengelola profil, kategori, produk, batch, stok, dan riwayat pergerakan milik tokonya sendiri. Batas toko diperiksa pada tiap request, jadi admin satu toko tidak bisa membaca atau mengubah data toko lain.</p>
  </div>

  <div class="lp-role">
    <div class="lp-role-head">
      <h3>Pengunjung</h3>
      <span class="lp-role-scope">Tanpa login</span>
    </div>
    <p>Menjelajahi katalog publik apa adanya: halaman toko, kategori, detail produk, daftar semua toko, sampai sitemap.xml untuk mesin pencari.</p>
  </div>
</section>

<section class="lp-specs" aria-labelledby="lp-isi">
  <h2 class="section-title" id="lp-isi">Yang sudah ada di dalamnya</h2>
  <p class="section-sub">Daftar ini mengikuti kode yang berjalan di repository ini, bukan rencana.</p>

  <div class="lp-spec-group">
    <h3>Katalog</h3>
    <dl class="lp-spec">
      <div class="lp-spec-row">
        <dt>Kategori</dt>
        <dd>Bertingkat per toko, maksimum tiga tingkat, bebas siklus, dengan slug unik di dalam satu toko.</dd>
      </div>
      <div class="lp-spec-row">
        <dt>Produk</dt>
        <dd>Angka desimal untuk uang, status draft, aktif, atau nonaktif, dan arsip yang tidak menghapus riwayat.</dd>
      </div>
      <div class="lp-spec-row">
        <dt>Batch pembelian</dt>
        <dd>Total dihitung dari jumlah dikali harga, ditambah biaya tambahan yang dialokasikan secara proporsional ke tiap barang. Seluruh transaksi dibatalkan kalau ada yang gagal.</dd>
      </div>
    </dl>
  </div>

  <div class="lp-spec-group">
    <h3>Stok dan biaya</h3>
    <dl class="lp-spec">
      <div class="lp-spec-row">
        <dt>Persediaan</dt>
        <dd>Kolom stok produk dan tabel pergerakan stok ditulis dalam satu transaksi dengan row-lock, jadi tidak ada dua pihak yang menimpa angka yang sama.</dd>
      </div>
      <div class="lp-spec-row">
        <dt>HPP</dt>
        <dd>Hitungan rata-rata tertimbang lewat HppService. Selisih harga jual dikurangi HPP disebut estimasi margin, bukan laba yang sudah terjadi.</dd>
      </div>
      <div class="lp-spec-row">
        <dt>Riwayat</dt>
        <dd>Setiap penyesuaian stok tercatat sebagai pergerakan tersendiri, lengkap dengan pelakunya.</dd>
      </div>
    </dl>
  </div>

  <div class="lp-spec-group">
    <h3>Akun dan media</h3>
    <dl class="lp-spec">
      <div class="lp-spec-row">
        <dt>Login Google</dt>
        <dd>Lewat Socialite, dan tidak pernah membuat akun superadmin dengan sendirinya.</dd>
      </div>
      <div class="lp-spec-row">
        <dt>Gambar</dt>
        <dd>Disimpan lewat Google Drive API dengan fallback lokal kalau kredensial belum diisi. Database hanya menyimpan metadata dan file ID-nya.</dd>
      </div>
      <div class="lp-spec-row">
        <dt>Audit log</dt>
        <dd>Perubahan penting tercatat tanpa menyimpan password, token, atau secret.</dd>
      </div>
      <div class="lp-spec-row">
        <dt>Alamat toko</dt>
        <dd>Slug lama dicatat sebagai riwayat supaya tautan yang sudah tersebar tetap sampai ke tempatnya.</dd>
      </div>
    </dl>
  </div>

  <div class="lp-spec-group">
    <h3>Kualitas</h3>
    <dl class="lp-spec">
      <div class="lp-spec-row">
        <dt>Test otomatis</dt>
        <dd>114 test dengan 620 asersi, dijalankan terpisah di SQLite in memory.</dd>
      </div>
      <div class="lp-spec-row">
        <dt>Desain</dt>
        <dd>Mengikuti DESIGN-meta.md: kanvas putih, tombol pill, CTA hitam di halaman marketing, dan biru cobalt hanya untuk aksi beli.</dd>
      </div>
    </dl>
  </div>
</section>

<section class="lp-install" aria-labelledby="lp-jalan">
  <div class="lp-install-head">
    <h2 class="section-title" id="lp-jalan">Menjalankannya di mesin sendiri</h2>
    <p class="section-sub">Butuh PHP 8.2, Composer 2, dan MySQL 8. Node bersifat opsional karena CSS di public/css ditulis tangan tanpa build.</p>
  </div>
  <div class="lp-install-grid">
    <pre class="lp-code"><code>composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan storage:link
SUPERADMIN_EMAIL=admin@example.com SUPERADMIN_PASSWORD=rahasia \
  php artisan db:seed --force
php artisan serve</code></pre>
    <div class="lp-notes">
      <p>Database dibuat dulu dengan karakter set utf8mb4, lalu nilai di .env disesuaikan sebelum migrate dijalankan.</p>
      <p>Ada dua pilihan saat mendaftar. <strong>Toko</strong> langsung membuat akun, toko, dan katalog publiknya, dan Anda jadi admin utamanya. <strong>Pengguna</strong> membuat akun saja, tokonya menyusul dari halaman admin atau dari superadmin.</p>
      <p>Contoh katalog sudah tersedia lewat seeder demo kalau ingin melihat halaman toko langsung terisi.</p>
    </div>
  </div>
</section>

<section class="lp-close">
  <div class="lp-close-card">
    <h2>Buka katalog pertamanya sekarang</h2>
    <p class="lp-close-body">Mendaftar sebagai Toko membuat akun, toko baru, dan alamat katalog publiknya dalam satu langkah. Setelah itu semua isi katalog dikelola dari dashboard toko.</p>
    <div class="hero-cta">
      <a class="btn btn-primary" href="{{ route('register') }}">Daftar sebagai Toko</a>
      <a class="btn btn-secondary" href="{{ route('login') }}">Masuk</a>
    </div>
    <p class="lp-close-note">Lebih suka melihat-lihat dulu? Semua katalog publik terbuka lewat <a href="{{ route('stores.index') }}">daftar semua toko</a>.</p>
  </div>
</section>

@endsection
