@extends('layouts.app')
@section('hide-appbar-footer', 'true')

@section('content')
<div class="lk-page">
  <div class="lk-avatar">
    @if($store->logoUrl())
      <img src="{{ $store->logoUrl() }}" alt="Logo {{ $store->name }}">
    @else
      <span aria-hidden="true">{{ mb_strtoupper(mb_substr($store->name, 0, 1)) }}</span>
    @endif
  </div>

  <h1 class="lk-name">{{ $store->name }}</h1>

  {{-- Tipografi Slogan Menggantikan Deskripsi Toko --}}
  @if($store->slogan)
    <p class="lk-slogan">{{ $store->slogan }}</p>
  @elseif($store->description)
    <p class="lk-slogan">{{ \Illuminate\Support\Str::limit($store->description, 120) }}</p>
  @endif

  <ul class="lk-list">
    {{-- Tautan Utama: Pintasan Langsung ke Katalog Toko --}}
    <li>
      <a class="lk-item lk-item-catalog" href="{{ $store->url() }}">
        <div style="display:flex;align-items:center;gap:10px">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>
          </svg>
          <span>Katalog Produk & Belanja</span>
        </div>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <polyline points="9 18 15 12 9 6"/>
        </svg>
      </a>
    </li>

    {{-- Daftar Tautan Eksternal Toko yang Dikelola Admin --}}
    @foreach($links as $link)
      <li>
        <a class="lk-item" href="{{ $link->safeUrl() }}" target="_blank" rel="noopener noreferrer">
          <span>{{ $link->title }}</span>
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M15 3h6v6"/>
            <path d="M10 14 21 3"/>
            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
          </svg>
        </a>
      </li>
    @endforeach
  </ul>

  <div class="lk-share">
    <button class="btn-lk-copy" type="button" id="lk-copy">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
        <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
      </svg>
      <span>Salin tautan halaman ini</span>
    </button>
  </div>
  <p class="lk-share-note" id="lk-copy-note" role="status" aria-live="polite"></p>

  <div class="lk-branding">
    <span>Didukung oleh</span>
    <a href="{{ route('home') }}">Katalog</a>
  </div>
</div>

<script>
(function () {
    var button = document.getElementById('lk-copy');
    if (!button) return;
    var note = document.getElementById('lk-copy-note');

    function done() {
        note.textContent = 'Alamat tautan sudah disalin.';
        setTimeout(function() {
            note.textContent = '';
        }, 3000);
    }

    button.addEventListener('click', function () {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(window.location.href).then(done, function () {
                note.textContent = 'Belum bisa menyalin otomatis, salin manual dari bilah alamat.';
            });
        } else {
            note.textContent = 'Belum bisa menyalin otomatis, salin manual dari bilah alamat.';
        }
    });
})();
</script>
@endsection
