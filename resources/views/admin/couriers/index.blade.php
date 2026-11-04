@extends('layouts.dashboard')
@section('title', 'Master Ekspedisi & Cek Ongkir')
@section('subtitle', $store->name)

@section('content')
<style>
  .courier-settings-container {
    max-width: 1060px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 24px;
  }

  .stat-grid-3 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
  }

  .stat-card-modern {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
  }

  .stat-icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
  }

  .stat-content {
    flex: 1;
    min-width: 0;
  }

  .stat-label {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
  }

  .stat-value {
    font-size: 17px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .stat-sub {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 2px;
  }

  .settings-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,0.03);
  }

  .settings-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid #f1f5f9;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
  }

  .settings-card-title {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .settings-card-desc {
    margin: 4px 0 0;
    font-size: 13px;
    color: #64748b;
    line-height: 1.4;
  }

  .settings-card-body {
    padding: 24px;
  }

  .courier-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
    gap: 14px;
  }

  .courier-card-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 14px 16px;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.15s ease;
  }

  .courier-card-item:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
  }

  .courier-card-item.is-active {
    border-color: #ff4d61;
    background: #fffbfa;
    box-shadow: 0 2px 8px rgba(255, 77, 97, 0.08);
  }

  .custom-switch {
    position: relative;
    width: 38px;
    height: 22px;
    flex-shrink: 0;
    margin-top: 2px;
  }

  .custom-switch input {
    opacity: 0;
    width: 0;
    height: 0;
  }

  .switch-slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #cbd5e1;
    transition: .2s;
    border-radius: 22px;
  }

  .switch-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .2s;
    border-radius: 50%;
  }

  .custom-switch input:checked + .switch-slider {
    background-color: #ff4d61;
  }

  .custom-switch input:checked + .switch-slider:before {
    transform: translateX(16px);
  }

  .courier-info {
    flex: 1;
    min-width: 0;
  }

  .courier-header-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
  }

  .courier-name {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
  }

  .badge-tag {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    padding: 2px 6px;
    border-radius: 4px;
    letter-spacing: 0.3px;
  }

  .badge-populer { background: #fee2e2; color: #991b1b; }
  .badge-instant { background: #dcfce7; color: #166534; }
  .badge-standar { background: #e0f2fe; color: #075985; }
  .badge-hemat   { background: #fef3c7; color: #92400e; }
  .badge-bumn    { background: #ffedd5; color: #9a3412; }

  .courier-services {
    font-size: 12px;
    color: #64748b;
    margin: 0;
    line-height: 1.4;
  }

  .input-styled, .textarea-styled {
    width: 100%;
    border-radius: 10px;
    padding: 10px 14px;
    border: 1.5px solid #cbd5e1;
    font-size: 13.5px;
    background: #ffffff;
    box-sizing: border-box;
    transition: border-color 0.15s ease;
  }

  .input-styled:focus, .textarea-styled:focus {
    border-color: #ff4d61;
    outline: none;
    box-shadow: 0 0 0 3px rgba(255, 77, 97, 0.12);
  }

  .btn-save-main {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 11px 24px;
    font-size: 14px;
    font-weight: 700;
    color: #ffffff;
    background: #ff4d61;
    border: 1px solid #e03254;
    border-radius: 30px;
    cursor: pointer;
    transition: all 0.15s ease;
    box-shadow: 0 2px 6px rgba(255, 77, 97, 0.25);
  }

  .btn-save-main:hover {
    background: #e63950;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(255, 77, 97, 0.3);
  }

  .quick-actions-bar {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }

  .btn-action-pill {
    font-size: 11.5px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 20px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s ease;
  }

  .btn-action-pill:hover {
    border-color: #94a3b8;
    background: #f1f5f9;
    color: #0f172a;
  }

  /* Table Rate Result */
  .rates-result-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 14px;
    font-size: 13px;
  }

  .rates-result-table th {
    background: #f8fafc;
    padding: 10px 14px;
    text-align: left;
    font-weight: 700;
    color: #475569;
    border-bottom: 2px solid #e2e8f0;
  }

  .rates-result-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
  }

  .rates-result-table tr:hover td {
    background: #f8fafc;
  }
</style>

<div class="courier-settings-container">

  {{-- 1. Kartu Ringkasan Status --}}
  <div class="stat-grid-3">
    <div class="stat-card-modern">
      <div class="stat-icon-wrap" style="background:#fee2e2;color:#dc2626;">🚚</div>
      <div class="stat-content">
        <div class="stat-label">Ekspedisi Aktif Toko</div>
        <div class="stat-value">
          <span id="activeCountDisplay">{{ count($activeCodes) }}</span>
          <span style="font-size:13px;color:#64748b;font-weight:normal;">/ {{ count($allCouriers) }} Ekspedisi</span>
        </div>
        <div class="stat-sub">Muncul saat pelanggan checkout</div>
      </div>
    </div>

    <div class="stat-card-modern">
      <div class="stat-icon-wrap" style="background:#e0f2fe;color:#0284c7;">📍</div>
      <div class="stat-content">
        <div class="stat-label">Titik Asal Pengiriman</div>
        <div class="stat-value">
          @if($store->postal_code || ($store->latitude && $store->longitude))
            <span style="color:#059669;display:inline-flex;align-items:center;gap:4px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              Siap Digunakan
            </span>
          @else
            <span style="color:#dc2626;">Belum Lengkap</span>
          @endif
        </div>
        <div class="stat-sub">
          {{ $store->district ? $store->district . ', ' : '' }}{{ $store->city ?: 'Alamat belum diatur' }}
          {{ $store->postal_code ? "({$store->postal_code})" : '' }}
        </div>
      </div>
    </div>

    <div class="stat-card-modern">
      <div class="stat-icon-wrap" style="background:#f0fdf4;color:#16a34a;">💾</div>
      <div class="stat-content">
        <div class="stat-label">Database Cache Ongkir</div>
        <div class="stat-value">
          <span>{{ $cacheCount }} Rute</span>
          <span style="font-size:12px;background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:20px;font-weight:600;">
            {{ $cacheHits }} Hit Hemat
          </span>
        </div>
        <div class="stat-sub">Menghemat kuota & biaya API Biteship</div>
      </div>
    </div>
  </div>

  {{-- 2. Form Master Ekspedisi & Titik Asal Toko --}}
  <form method="POST" action="{{ route('admin.couriers.update', $store) }}">
    @csrf
    @method('PUT')

    {{-- CARD: Pilihan Ekspedisi --}}
    <div class="settings-card" style="margin-bottom:24px;">
      <div class="settings-card-header">
        <div>
          <h2 class="settings-card-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ff4d61" stroke-width="2"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
            Pilihan Ekspedisi Aktif (Master Kurir)
          </h2>
          <p class="settings-card-desc">
            Aktifkan ekspedisi yang didukung untuk toko Anda. Ekspedisi yang aktif akan langsung muncul pada pilihan kurir saat pembeli menghitung ongkos kirim.
          </p>
        </div>
        <div class="quick-actions-bar">
          <button type="button" class="btn-action-pill" id="btnSelectAllCouriers">Pilih Semua</button>
          <button type="button" class="btn-action-pill" id="btnSelectRecommended">Rekomendasi (JNE, SiCepat, J&T)</button>
          <button type="button" class="btn-action-pill" id="btnDeselectAllCouriers">Nonaktifkan Semua</button>
        </div>
      </div>

      <div class="settings-card-body">
        <div class="courier-grid">
          @foreach($allCouriers as $code => $courier)
            @php($isActive = in_array($code, $activeCodes, true))
            <label class="courier-card-item {{ $isActive ? 'is-active' : '' }}" id="courier-item-{{ $code }}">
              <div class="custom-switch">
                <input type="checkbox" name="active_couriers[]" value="{{ $code }}" {{ $isActive ? 'checked' : '' }} class="courier-checkbox">
                <span class="switch-slider"></span>
              </div>
              <div class="courier-info">
                <div class="courier-header-row">
                  <span class="courier-name">{{ $courier['name'] }}</span>
                  <span class="badge-tag badge-{{ strtolower($courier['badge'] ?? 'standar') }}">{{ $courier['badge'] ?? 'Standar' }}</span>
                </div>
                <p class="courier-services">{{ $courier['note'] ?? '' }}</p>
              </div>
            </label>
          @endforeach
        </div>

        <div style="background:#f8fafc;border:1px dashed #cbd5e1;border-radius:10px;padding:12px 16px;margin-top:18px;display:flex;align-items:center;gap:10px;font-size:12.5px;color:#475569;">
          <span style="font-size:16px;">💡</span>
          <div>
            <strong>Catatan Tambahan:</strong> Layanan <em>"Ambil Sendiri di Toko / Ongkir Manual via WhatsApp"</em> selalu otomatis tersedia sebagai opsi alternatif bagi pembeli.
          </div>
        </div>
      </div>
    </div>

    {{-- CARD: Titik Asal Pengiriman Toko --}}
    <div class="settings-card" style="margin-bottom:24px;">
      <div class="settings-card-header">
        <div>
          <h2 class="settings-card-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
            Alamat Asal Pengiriman Toko (Origin Point)
          </h2>
          <p class="settings-card-desc">
            Titik alamat gudang atau gerai fisik toko Anda. Biteship membutuhkan minimal <strong>Kode Pos</strong> atau <strong>Titik Koordinat GPS</strong> untuk menghitung tarif pengiriman akurat.
          </p>
        </div>
        <div>
          <button type="button" class="btn-action-pill" id="btnAutoGps" style="color:#0284c7;border-color:#bae6fd;background:#f0f9ff;">
            📍 Deteksi GPS Lokasi Saya
          </button>
        </div>
      </div>

      <div class="settings-card-body">
        <div style="margin-bottom:16px;">
          <label style="font-weight:600;font-size:13px;color:#334155;display:block;margin-bottom:6px;">
            Alamat Fisik Lengkap Toko / Gudang
          </label>
          <textarea class="textarea-styled" name="address" rows="3" placeholder="Contoh: Jl. Riau No. 10, Ruko Sentra Busana Blok B-2">{{ old('address', $store->address) }}</textarea>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:14px;margin-bottom:16px;">
          <div>
            <label style="font-weight:600;font-size:12.5px;color:#334155;display:block;margin-bottom:6px;" for="select-courier-province">
              Provinsi Asal <span style="font-size:11px;color:#0284c7;">(Master Wilayah)</span>
            </label>
            <select id="select-courier-province" class="select" style="width:100%;">
              <option value="">-- Pilih Provinsi --</option>
            </select>
            <input type="hidden" id="input-courier-province" name="province" value="{{ old('province', $store->province) }}">
          </div>

          <div>
            <label style="font-weight:600;font-size:12.5px;color:#334155;display:block;margin-bottom:6px;" for="select-courier-city">
              Kota / Kabupaten Asal
            </label>
            <select id="select-courier-city" class="select" style="width:100%;" disabled>
              <option value="">-- Pilih Kota / Kabupaten --</option>
            </select>
            <input type="hidden" id="input-courier-city" name="city" value="{{ old('city', $store->city) }}">
          </div>

          <div>
            <label style="font-weight:600;font-size:12.5px;color:#334155;display:block;margin-bottom:6px;" for="select-courier-district">
              Kecamatan Asal
            </label>
            <select id="select-courier-district" class="select" style="width:100%;" disabled>
              <option value="">-- Pilih Kecamatan --</option>
            </select>
            <input type="hidden" id="input-courier-district" name="district" value="{{ old('district', $store->district) }}">
          </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:14px;">
          <div>
            <label style="font-weight:600;font-size:12.5px;color:#334155;display:block;margin-bottom:6px;">
              Kode Pos Toko <span style="color:#ff4d61;">* (Wajib Biteship)</span>
            </label>
            <input type="text" class="input-styled" name="postal_code" id="inputPostalCode" value="{{ old('postal_code', $store->postal_code) }}" placeholder="Contoh: 40115" maxlength="10">
          </div>

          <div>
            <label style="font-weight:600;font-size:12.5px;color:#334155;display:block;margin-bottom:6px;">
              Titik Latitude GPS
            </label>
            <input type="text" class="input-styled" name="latitude" id="inputLat" value="{{ old('latitude', $store->latitude) }}" placeholder="Contoh: -6.902481">
          </div>

          <div>
            <label style="font-weight:600;font-size:12.5px;color:#334155;display:block;margin-bottom:6px;">
              Titik Longitude GPS
            </label>
            <input type="text" class="input-styled" name="longitude" id="inputLng" value="{{ old('longitude', $store->longitude) }}" placeholder="Contoh: 107.618622">
          </div>
        </div>
      </div>

      <div style="padding:16px 24px;background:#fafbfc;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end;">
        <button type="submit" class="btn-save-main">
          💾 Simpan Pengaturan Ekspedisi & Asal Pengiriman
        </button>
      </div>
    </div>
  </form>

  {{-- 3. CARD: Simulator & Live Test Cek Ongkir --}}
  <div class="settings-card">
    <div class="settings-card-header">
      <div>
        <h2 class="settings-card-title">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
          Uji Coba Cek Ongkir Langsung (Simulator Ekspedisi)
        </h2>
        <p class="settings-card-desc">
          Lakukan pengetesan kalkulasi tarif pengiriman dari titik asal toko Anda ke alamat tujuan pelanggan secara instan.
        </p>
      </div>
    </div>

    <div class="settings-card-body">
      <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:14px;margin-bottom:16px;">
        <div>
          <label style="font-weight:600;font-size:12.5px;color:#334155;display:block;margin-bottom:6px;" for="select-sim-province">
            Provinsi Tujuan
          </label>
          <select id="select-sim-province" class="select" style="width:100%;">
            <option value="">-- Pilih Provinsi Tujuan --</option>
          </select>
          <input type="hidden" id="input-sim-province">
        </div>

        <div>
          <label style="font-weight:600;font-size:12.5px;color:#334155;display:block;margin-bottom:6px;" for="select-sim-city">
            Kota / Kabupaten Tujuan
          </label>
          <select id="select-sim-city" class="select" style="width:100%;" disabled>
            <option value="">-- Pilih Kota Tujuan --</option>
          </select>
          <input type="hidden" id="input-sim-city">
        </div>

        <div>
          <label style="font-weight:600;font-size:12.5px;color:#334155;display:block;margin-bottom:6px;" for="select-sim-district">
            Kecamatan Tujuan
          </label>
          <select id="select-sim-district" class="select" style="width:100%;" disabled>
            <option value="">-- Pilih Kecamatan Tujuan --</option>
          </select>
          <input type="hidden" id="input-sim-district">
        </div>

        <div>
          <label style="font-weight:600;font-size:12.5px;color:#334155;display:block;margin-bottom:6px;">
            Kode Pos Tujuan
          </label>
          <input type="text" class="input-styled" id="simPostal" placeholder="Contoh: 12950" value="12950">
        </div>

        <div>
          <label style="font-weight:600;font-size:12.5px;color:#334155;display:block;margin-bottom:6px;">
            Berat Paket (Gram)
          </label>
          <input type="number" class="input-styled" id="simWeight" value="1000" min="100" step="100">
        </div>
      </div>

      <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
        <button type="button" class="btn btn-primary btn-sm" id="btnRunSimulation" style="padding:10px 20px;font-size:13px;border-radius:20px;">
          ⚡ Hitung Tarif Ongkir Uji Coba
        </button>
        <span id="simLoadingIndicator" style="display:none;font-size:13px;color:#64748b;">
          <span class="skeleton" style="width:20px;height:20px;display:inline-block;vertical-align:middle;border-radius:50%;"></span>
          Menghubungkan layanan cek ongkir...
        </span>
      </div>

      <div id="simResultContainer" style="margin-top:20px;display:none;"></div>
    </div>
  </div>

  {{-- 4. CARD: Manajemen Cache DB Ongkir Toko --}}
  <div class="settings-card">
    <div class="settings-card-header">
      <div>
        <h2 class="settings-card-title">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21h5v-5"/></svg>
          Mekanisme Cache Offline DB & Pembersihan
        </h2>
        <p class="settings-card-desc">
          Sistem secara otomatis menyimpan hasil perhitungan ongkir di database lokal (berlaku 1 hari). Setiap panggilan rute yang sama akan diambil dari database lokal sehingga hemat kuota API Biteship.
        </p>
      </div>
      <div>
        <form method="POST" action="{{ route('admin.couriers.clear_cache', $store) }}" onsubmit="return confirm('Apakah Anda yakin ingin membersihkan semua cache tarif ongkir toko ini?');">
          @csrf
          <button type="submit" class="btn-action-pill" style="color:#b91c1c;border-color:#fecdd3;background:#fff1f2;">
            🗑️ Bersihkan Cache Ongkir Toko
          </button>
        </form>
      </div>
    </div>
  </div>

</div>

{{-- Library Select2 & Wilayah Helper --}}
<link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/wilayah-select2.css') }}">
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
<script src="{{ asset('js/wilayah-dropdown.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  'use strict';

  // 1. Inisialisasi Master Wilayah untuk Titik Asal Toko
  initWilayahDropdown({
    provinceSelectId: 'select-courier-province',
    citySelectId: 'select-courier-city',
    districtSelectId: 'select-courier-district',
    provinceInputId: 'input-courier-province',
    cityInputId: 'input-courier-city',
    districtInputId: 'input-courier-district',
    initialProvince: @json(old('province', $store->province ?? '')),
    initialCity: @json(old('city', $store->city ?? '')),
    initialDistrict: @json(old('district', $store->district ?? ''))
  });

  // 2. Inisialisasi Master Wilayah untuk Simulator
  initWilayahDropdown({
    provinceSelectId: 'select-sim-province',
    citySelectId: 'select-sim-city',
    districtSelectId: 'select-sim-district',
    provinceInputId: 'input-sim-province',
    cityInputId: 'input-sim-city',
    districtInputId: 'input-sim-district',
    initialProvince: 'DKI Jakarta',
    initialCity: 'Jakarta Selatan',
    initialDistrict: 'Setiabudi'
  });

  // 3. Interaksi Checklist Ekspedisi & Tombol Cepat
  var checkboxes = document.querySelectorAll('.courier-checkbox');
  var counterDisplay = document.getElementById('activeCountDisplay');

  function updateActiveVisuals() {
    var count = 0;
    checkboxes.forEach(function(cb) {
      var card = document.getElementById('courier-item-' + cb.value);
      if (cb.checked) {
        count++;
        if (card) card.classList.add('is-active');
      } else {
        if (card) card.classList.remove('is-active');
      }
    });
    if (counterDisplay) {
      counterDisplay.textContent = count;
    }
  }

  checkboxes.forEach(function(cb) {
    cb.addEventListener('change', updateActiveVisuals);
  });

  document.getElementById('btnSelectAllCouriers').addEventListener('click', function() {
    checkboxes.forEach(function(cb) { cb.checked = true; });
    updateActiveVisuals();
  });

  document.getElementById('btnDeselectAllCouriers').addEventListener('click', function() {
    checkboxes.forEach(function(cb) { cb.checked = false; });
    updateActiveVisuals();
  });

  document.getElementById('btnSelectRecommended').addEventListener('click', function() {
    var rec = ['jne', 'sicepat', 'jnt'];
    checkboxes.forEach(function(cb) {
      cb.checked = rec.indexOf(cb.value) !== -1;
    });
    updateActiveVisuals();
  });

  // 4. Deteksi GPS Otomatis
  var btnGps = document.getElementById('btnAutoGps');
  if (btnGps && navigator.geolocation) {
    btnGps.addEventListener('click', function() {
      btnGps.disabled = true;
      btnGps.textContent = '⏳ Mencari sinyal GPS...';

      navigator.geolocation.getCurrentPosition(function(pos) {
        document.getElementById('inputLat').value = pos.coords.latitude.toFixed(6);
        document.getElementById('inputLng').value = pos.coords.longitude.toFixed(6);
        btnGps.textContent = '✅ Koordinat Diperbarui';
        setTimeout(function() {
          btnGps.disabled = false;
          btnGps.textContent = '📍 Deteksi GPS Lokasi Saya';
        }, 3000);
      }, function(err) {
        alert('Gagal mendeteksi lokasi GPS: ' + err.message);
        btnGps.disabled = false;
        btnGps.textContent = '📍 Deteksi GPS Lokasi Saya';
      }, { enableHighAccuracy: true, timeout: 10000 });
    });
  }

  // 5. Eksekusi Simulasi Cek Ongkir Live
  var btnSim = document.getElementById('btnRunSimulation');
  var simIndicator = document.getElementById('simLoadingIndicator');
  var simResultBox = document.getElementById('simResultContainer');

  btnSim.addEventListener('click', function() {
    var postal = document.getElementById('simPostal').value.trim();
    var weight = document.getElementById('simWeight').value;
    var prov = document.getElementById('input-sim-province').value;
    var city = document.getElementById('input-sim-city').value;
    var dist = document.getElementById('input-sim-district').value;

    btnSim.disabled = true;
    simIndicator.style.display = 'inline-flex';
    simResultBox.style.display = 'none';

    fetch(@json(route('admin.couriers.simulate', $store)), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        destination_postal: postal,
        destination_province: prov,
        destination_city: city,
        destination_district: dist,
        weight_grams: weight
      })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
      btnSim.disabled = false;
      simIndicator.style.display = 'none';
      simResultBox.style.display = 'block';

      if (!data.success) {
        simResultBox.innerHTML = '<div class="alert alert-error">' + (data.message || 'Gagal menghitung tarif.') + '</div>';
        return;
      }

      var cacheBadge = data.cached
        ? '<span style="background:#dcfce7;color:#15803d;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:700;">💾 Database Cache (0 Biaya API)</span>'
        : '<span style="background:#e0f2fe;color:#0284c7;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:700;">⚡ Live API Biteship</span>';

      var html = '<div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;">'
        + '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px;">'
        + '  <strong style="font-size:14px;color:#0f172a;">Hasil Kalkulasi Tarif Ongkir (' + (data.rates ? data.rates.length : 0) + ' Layanan Ditemukan):</strong>'
        + '  ' + cacheBadge
        + '</div>';

      if (!data.rates || data.rates.length === 0) {
        html += '<p style="color:#64748b;font-size:13px;margin:0;">Tidak ada layanan ekspedisi yang tersedia untuk rute ini.</p>';
      } else {
        html += '<div style="overflow-x:auto;"><table class="rates-result-table">'
          + '<thead><tr>'
          + '<th>Ekspedisi</th>'
          + '<th>Layanan</th>'
          + '<th>Estimasi Waktu</th>'
          + '<th>Tarif Pengiriman</th>'
          + '</tr></thead><tbody>';

        data.rates.forEach(function(rate) {
          var formattedPrice = 'Rp ' + Number(rate.price || 0).toLocaleString('id-ID');
          var etd = rate.etd || rate.duration || '—';
          if (etd && !etd.toLowerCase().includes('hari') && !etd.toLowerCase().includes('jam')) {
            etd += ' hari';
          }

          html += '<tr>'
            + '<td><strong>' + (rate.courier_name || rate.company || '').toUpperCase() + '</strong></td>'
            + '<td>' + (rate.service_name || rate.courier_service_name || 'Reguler') + '</td>'
            + '<td><span style="color:#64748b;">' + etd + '</span></td>'
            + '<td><strong style="color:#059669;font-size:14px;">' + formattedPrice + '</strong></td>'
            + '</tr>';
        });

        html += '</tbody></table></div>';
      }

      html += '</div>';
      simResultBox.innerHTML = html;
    })
    .catch(function(err) {
      btnSim.disabled = false;
      simIndicator.style.display = 'none';
      simResultBox.style.display = 'block';
      simResultBox.innerHTML = '<div class="alert alert-error">Terjadi kendala saat simulasi: ' + err.message + '</div>';
    });
  });
});
</script>
@endsection

