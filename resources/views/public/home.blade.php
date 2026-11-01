@extends('layouts.app')
@section('content')

{{-- Satu-satunya bidang warna pekat di halaman ini: fokus tunggal untuk ajakan mendaftar. --}}
<div class="hero hero-accent">
  <h1>Toko Anda punya katalog sendiri.<br><span class="lp-light">Cukup satu tautan.</span></h1>
  <p>Masukkan barang, harga, dan stoknya ke Katalog. Pelanggan tinggal membuka tautannya, tanpa menginstal aplikasi dan tanpa membuat akun.</p>
  <div class="hero-cta">
    <a class="btn btn-primary" href="{{ route('register') }}">Daftar dan buka katalog saya</a>
    <a class="btn btn-secondary" href="{{ route('stores.index') }}">Lihat katalog yang sudah jalan</a>
  </div>
  <p class="hero-alt">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
  <ul class="hero-facts">
    <li>Tanpa instal aplikasi</li>
    <li>Pelanggan tidak perlu daftar</li>
    <li>Stok tercatat tiap kali berubah</li>
  </ul>
</div>

<section class="lp-split" aria-labelledby="lp-dapat">
  <div class="lp-split-aside">
    <h2 class="lp-title" id="lp-dapat">Yang langsung Anda dapat</h2>
    <p class="lp-lead">Pilih Toko saat mendaftar, isi nama toko, dan katalognya sudah hidup. Isinya menyusul dari satu halaman yang sama.</p>
  </div>
  <div class="lp-split-main">
    <div class="lp-row">
      <div class="lp-row-key">
        <code>/toko-saya</code>
        <span class="lp-row-note">contoh alamat katalog</span>
      </div>
      <p>Katalog Anda punya alamat sendiri, tinggal ditempel ke WhatsApp atau bio Instagram. Alamatnya boleh diganti kapan saja, dan tautan lama tetap mengarah ke tempatnya.</p>
    </div>
    <div class="lp-row">
      <div class="lp-row-key"><span class="lp-row-label">Barang tertata</span></div>
      <p>Kategori boleh bertingkat sampai tiga tingkat, jadi toko dengan banyak barang tetap enak dijelajahi. Barang yang belum siap ditampilkan disimpan sebagai draf dulu.</p>
    </div>
    <div class="lp-row">
      <div class="lp-row-key"><span class="lp-row-label">Harga selalu terbaru</span></div>
      <p>Ubah harga atau stok di satu tempat, lalu semua orang yang membuka katalog langsung melihat versi barunya. Tidak ada lagi harga lama yang masih tertinggal di percakapan.</p>
    </div>
    <div class="lp-row">
      <div class="lp-row-key"><span class="lp-row-label">Harga pokok jelas</span></div>
      <p>Setiap barang masuk dicatat beserta biaya kirim dan biaya lainnya, lalu harganya dihitung rata-rata tertimbang. Selisihnya dengan harga jual langsung kelihatan.</p>
    </div>
  </div>
</section>

{{-- Pita surface-soft selebar layar: satu-satunya perubahan latar, supaya isi halaman tidak monoton. --}}
<section class="lp-menu-band" aria-labelledby="lp-halaman">
  <div class="lp-menu-head">
    <h2 class="lp-title" id="lp-halaman">Semua urusan toko di satu halaman</h2>
    <p class="lp-lead">Masuk lewat email atau akun Google, lalu kerjakan semuanya dari browser. Tidak ada yang perlu diinstal, bisa dari laptop maupun HP.</p>
  </div>
  <div class="lp-menu">
    <div class="lp-menu-item">
      <h3>Dashboard Utama</h3>
      <p>Ringkasan produk, kategori, batch, dan stok dalam satu layar, termasuk barang yang stoknya menipis.</p>
    </div>
    <div class="lp-menu-item">
      <h3>Profil Toko</h3>
      <p>Nama, logo, deskripsi, alamat katalog, dan kontak yang tampil di halaman depan.</p>
    </div>
    <div class="lp-menu-item">
      <h3>Kategori</h3>
      <p>Susun barang ke dalam tiga tingkat, ubah urutannya, dan sembunyikan yang belum dipakai.</p>
    </div>
    <div class="lp-menu-item">
      <h3>Daftar Produk</h3>
      <p>Foto, harga, stok, dan statusnya: tampil, draf, atau nonaktif.</p>
    </div>
    <div class="lp-menu-item">
      <h3>Batch Pembelian</h3>
      <p>Catat barang masuk sekalian biaya tambahannya, biar harga pokok tidak meleset.</p>
    </div>
    <div class="lp-menu-item">
      <h3>Stok dan Riwayat</h3>
      <p>Sisa barang, penyesuaiannya, dan siapa yang mengubah kapan.</p>
    </div>
    <div class="lp-menu-item">
      <h3>Aktivitas</h3>
      <p>Jejak perubahan terakhir yang terjadi di toko Anda.</p>
    </div>
    <div class="lp-menu-item">
      <h3>Lihat Katalog</h3>
      <p>Buka halaman publik yang dilihat pelanggan, langsung dari dashboard.</p>
    </div>
  </div>
</section>

<section class="lp-trust-wrap" aria-labelledby="lp-aman">
  <h2 class="lp-title" id="lp-aman">Aman dipegang lebih dari satu orang</h2>
  <p class="lp-lead">Toko Anda tidak bergantung pada satu akun.</p>
  <div class="lp-trust">
    <div class="lp-trust-item">
      <h3>Hanya tokonya</h3>
      <p>Orang yang mengurus satu toko hanya bisa membaca dan mengubah milik tokonya sendiri. Toko lain tertutup rapat.</p>
    </div>
    <div class="lp-trust-item">
      <h3>Perubahan tercatat</h3>
      <p>Setiap perubahan penting masuk ke riwayat, tanpa menyimpan kata sandi maupun kunci akses.</p>
    </div>
    <div class="lp-trust-item">
      <h3>Ada yang mengawasi</h3>
      <p>Superadmin menautkan orang baru ke tokonya dan bisa melihat seluruh toko sekaligus bila diperlukan.</p>
    </div>
  </div>
</section>

<section class="lp-close">
  <div class="lp-close-card">
    <h2>Buka katalog toko Anda sekarang</h2>
    <p class="lp-close-body">Mendaftar sebagai Toko langsung membuat akun, toko baru, dan alamat katalognya dalam satu langkah. Setelah itu tinggal isi barangnya, lalu bagikan tautannya.</p>
    <div class="hero-cta">
      <a class="btn btn-primary" href="{{ route('register') }}">Daftar sebagai Toko</a>
      <a class="btn btn-secondary" href="{{ route('login') }}">Masuk</a>
    </div>
    <p class="lp-close-note">Masih ingin melihat-lihat dulu? Semua katalog terbuka lewat <a href="{{ route('stores.index') }}">daftar semua toko</a>.</p>
  </div>
</section>

@endsection
