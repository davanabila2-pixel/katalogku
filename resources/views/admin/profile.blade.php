@extends('layouts.dashboard')
@section('title', 'Profil Toko')
@section('subtitle', 'Kelola identitas resmi, alamat slug katalog, kontak, dan pengaturan visibilitas toko Anda.')

@section('header-actions')
<a class="btn btn-secondary btn-sm" href="{{ $store->url() }}" target="_blank" style="padding:8px 16px;font-size:13px">
  Buka Katalog Toko ↗
</a>
@endsection

@section('content')
<style>
  .profile-container {
    width: 100%;
  }

  .profile-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
  }

  /* Integrated Card Tabs */
  .profile-tabs {
    display: flex;
    gap: 0;
    background: #fafbfc;
    border-bottom: 1px solid #e2e8f0;
    padding: 0 16px;
    overflow-x: auto;
  }

  .profile-tab {
    appearance: none;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    padding: 13px 18px;
    font: inherit;
    font-size: 14px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    white-space: nowrap;
    transition: color 0.15s ease, border-color 0.15s ease;
  }

  .profile-tab:hover {
    color: #0f172a;
  }

  .profile-tab:focus-visible {
    outline: 2px solid #ff4d61;
    outline-offset: -2px;
  }

  .profile-tab[aria-selected="true"] {
    color: #ff4d61;
    border-bottom-color: #ff4d61;
    background: #ffffff;
  }

  /* Card Body & Panels */
  .profile-card-body {
    padding: 28px 28px 24px;
  }

  .tab-panel[hidden] {
    display: none;
  }

  .section-intro {
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f1f5f9;
  }

  .section-intro h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.2px;
  }

  .section-intro p {
    margin: 4px 0 0;
    font-size: 13px;
    color: #64748b;
    line-height: 1.4;
  }

  /* Logo Row */
  .logo-uploader-row {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    margin-top: 8px;
  }

  .logo-preview-thumb {
    width: 68px;
    height: 68px;
    border-radius: 10px;
    object-fit: cover;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
  }

  .logo-preview-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }

  .logo-placeholder-char {
    font-size: 24px;
    font-weight: 700;
    color: #94a3b8;
  }

  .logo-upload-meta {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
  }

  .file-picker-btn {
    display: inline-flex;
    align-items: center;
    padding: 7px 14px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    cursor: pointer;
    width: fit-content;
    transition: background 0.15s ease, border-color 0.15s ease;
  }

  .file-picker-btn:hover {
    background: #f8fafc;
    border-color: #94a3b8;
  }

  .file-picker-btn input[type="file"] {
    display: none;
  }

  .logo-hint-text {
    font-size: 12px;
    color: #64748b;
    line-height: 1.4;
  }

  /* Form Elements */
  .field-label-group {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 6px;
  }

  .field-hint-text {
    font-size: 12px;
    color: #64748b;
    margin-top: 5px;
  }

  .input-styled, .textarea-styled {
    width: 100%;
    padding: 10px 12px;
    font-size: 14px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #ffffff;
    color: #0f172a;
    font-family: inherit;
    box-sizing: border-box;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
  }

  .input-styled:focus, .textarea-styled:focus {
    outline: none;
    border-color: #ff4d61;
    box-shadow: 0 0 0 3px rgba(255, 77, 97, 0.12);
  }

  /* Slug Input */
  .slug-input-group {
    display: flex;
    align-items: stretch;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #ffffff;
    overflow: hidden;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
  }

  .slug-input-group:focus-within {
    border-color: #ff4d61;
    box-shadow: 0 0 0 3px rgba(255, 77, 97, 0.12);
  }

  .slug-domain-prefix {
    background: #f8fafc;
    border-right: 1px solid #e2e8f0;
    padding: 0 14px;
    display: flex;
    align-items: center;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    font-family: monospace;
    white-space: nowrap;
    user-select: none;
  }

  .slug-input-field {
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
    padding: 10px 12px !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    color: #0f172a !important;
    flex: 1;
    width: auto !important;
  }

  .info-callout {
    margin-top: 14px;
    background: #fff5f7;
    border: 1px solid #fecdd6;
    border-radius: 8px;
    padding: 12px 14px;
    font-size: 13px;
    color: #9f1239;
    line-height: 1.5;
  }

  /* Feature Toggle */
  .feature-toggle-row {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    cursor: pointer;
  }

  .feature-toggle-row:hover {
    background: #f1f5f9;
  }

  .custom-switch {
    position: relative;
    width: 40px;
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
    transform: translateX(18px);
  }

  /* Card Footer Actions */
  .profile-card-footer {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 28px;
    background: #fafbfc;
    border-top: 1px solid #e2e8f0;
  }

  .btn-save {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 9px 20px;
    font-size: 14px;
    font-weight: 600;
    color: #ffffff;
    background: #ff4d61;
    border: 1px solid #e03254;
    border-radius: 8px;
    cursor: pointer;
    line-height: 1.4;
    transition: background 0.15s ease, border-color 0.15s ease;
  }

  .btn-save:hover {
    background: #e63950;
    border-color: #c4263f;
  }

  .btn-save:focus-visible {
    outline: 2px solid #ff4d61;
    outline-offset: 2px;
  }

  .btn-save:disabled {
    background: #94a3b8;
    border-color: #94a3b8;
    cursor: not-allowed;
  }

  .btn-cancel {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 9px 18px;
    font-size: 14px;
    font-weight: 600;
    color: #334155;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    text-decoration: none;
    line-height: 1.4;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
  }

  .btn-cancel:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a;
  }

  .btn-cancel:focus-visible {
    outline: 2px solid #ff4d61;
    outline-offset: 2px;
  }

  @media (max-width: 767px) {
    .profile-card-body { padding: 20px 16px; }
    .profile-card-footer { padding: 14px 16px; }
    .logo-uploader-row { flex-direction: column; align-items: flex-start; }
  }
</style>

<div class="profile-container">
  <form method="POST" action="{{ route('admin.profile.update', $store) }}" enctype="multipart/form-data" id="store-profile-form" data-update-url="{{ route('api.profile.update', $store) }}">
    @csrf
    @method('PUT')

    <div class="alert alert-error" id="profile-form-error" hidden style="margin-bottom:16px"></div>

    <div class="profile-card">
      <!-- Tabs Header -->
      <div class="profile-tabs" role="tablist" aria-label="Bagian profil toko">
        <button type="button" class="profile-tab" role="tab" id="tab-identitas" aria-controls="panel-identitas" aria-selected="true">Identitas</button>
        <button type="button" class="profile-tab" role="tab" id="tab-url" aria-controls="panel-url" aria-selected="false" tabindex="-1">Alamat URL</button>
        <button type="button" class="profile-tab" role="tab" id="tab-kontak" aria-controls="panel-kontak" aria-selected="false" tabindex="-1">Kontak</button>
        <button type="button" class="profile-tab" role="tab" id="tab-pembayaran" aria-controls="panel-pembayaran" aria-selected="false" tabindex="-1">Pembayaran / QR</button>
        <button type="button" class="profile-tab" role="tab" id="tab-katalog" aria-controls="panel-katalog" aria-selected="false" tabindex="-1">Katalog</button>
      </div>

      <!-- Card Body: Tab Panels -->
      <div class="profile-card-body">
        <!-- Panel 1: Identitas -->
        <div class="tab-panel" role="tabpanel" id="panel-identitas" aria-labelledby="tab-identitas">
          <div class="section-intro">
            <h3>Identitas & Branding Toko</h3>
            <p>Atur nama resmi, logo brand, dan deskripsi singkat toko Anda.</p>
          </div>

          <div class="field">
            <div class="field-label-group">
              <label>Logo Toko</label>
              <span id="selected-file-name" style="font-size:12px;color:#64748b;font-weight:normal">
                {{ $store->logo_path ? 'Logo aktif' : 'Belum ada logo' }}
              </span>
            </div>

            <div class="logo-uploader-row">
              <div class="logo-preview-thumb" id="logo-preview-box">
                @if($store->logoUrl())
                  <img src="{{ $store->logoUrl() }}" alt="{{ $store->name }}" id="logo-preview-img">
                @else
                  <span class="logo-placeholder-char" id="logo-preview-placeholder">{{ strtoupper(substr($store->name, 0, 1)) }}</span>
                @endif
              </div>

              <div class="logo-upload-meta">
                <label class="file-picker-btn">
                  Pilih Gambar Logo
                  <input type="file" name="logo" id="logo-file-input" accept="image/png,image/jpeg,image/webp,image/gif">
                </label>
                <div class="logo-hint-text">
                  Format didukung: PNG, JPG, WebP (Maks. 5 MB). Diunggah otomatis ke Google Drive Storage toko.
                </div>
              </div>
            </div>
          </div>

          <div class="field" style="margin-top:20px">
            <div class="field-label-group">
              <label for="input-store-name">Nama Resmi Toko <span style="color:var(--critical)">*</span></label>
              <small style="color:#64748b;font-weight:normal" id="name-counter">0 / 100</small>
            </div>
            <input class="input-styled" type="text" id="input-store-name" name="name" value="{{ old('name', $store->name) }}" required maxlength="100" placeholder="cth: Kemeja Vintage Official">
            <div class="field-hint-text">Nama toko tampil pada navbar, header katalog, dan label barcode.</div>
          </div>

          <div class="field" style="margin-top:20px;margin-bottom:0">
            <div class="field-label-group">
              <label for="input-store-desc">Deskripsi & Slogan Toko</label>
              <small style="color:#64748b;font-weight:normal" id="desc-counter">0 / 2000</small>
            </div>
            <textarea class="textarea-styled" id="input-store-desc" name="description" rows="3" maxlength="2000" placeholder="Jelaskan jenis produk dan keunggulan toko Anda...">{{ old('description', $store->description) }}</textarea>
            <div class="field-hint-text">Deskripsi membantu pelanggan mengenali tokomu di halaman katalog.</div>
          </div>
        </div>

        <!-- Panel 2: Alamat URL -->
        <div class="tab-panel" role="tabpanel" id="panel-url" aria-labelledby="tab-url" hidden>
          <div class="section-intro">
            <h3>Alamat URL & Slug Katalog</h3>
            <p>Tautan unik untuk mengakses etalase katalog online toko Anda.</p>
          </div>

          <div class="field" style="margin-bottom:0">
            <div class="field-label-group">
              <label for="input-store-slug">Slug URL Toko</label>
              <span style="font-size:12px;color:#ff4d61;font-weight:600">301 Auto-Redirect</span>
            </div>

            <div class="slug-input-group">
              <span class="slug-domain-prefix">{{ url('/') }}/</span>
              <input class="input-styled slug-input-field" type="text" id="input-store-slug" name="slug" value="{{ old('slug', $store->slug) }}" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" placeholder="{{ $store->slug }}">
            </div>

            <div class="info-callout">
              <strong>Info Pengalihan (Redirect):</strong> Jika slug diubah, URL lama toko secara otomatis dialihkan (301 Permanent Redirect) ke URL baru sehingga tautan lama tetap bisa diakses tanpa error 404.
            </div>
          </div>
        </div>

        <!-- Panel 3: Kontak & Lokasi -->
        <div class="tab-panel" role="tabpanel" id="panel-kontak" aria-labelledby="tab-kontak" hidden>
          <div class="section-intro">
            <h3>Kontak & Lokasi Toko</h3>
            <p>Informasi layanan pelanggan dan alamat fisik gerai toko.</p>
          </div>

          <div class="field">
            <div class="field-label-group">
              <label for="input-store-contact">Kontak CS / WhatsApp</label>
            </div>
            <input class="input-styled" type="text" id="input-store-contact" name="contact_information" value="{{ old('contact_information', $store->contact_information) }}" placeholder="cth: 0812-3456-7890 / cs@tokomu.com">
            <div class="field-hint-text">Nomor WhatsApp atau email untuk pertanyaan calon pembeli.</div>
          </div>

          <div class="field" style="margin-top:20px;margin-bottom:0">
            <div class="field-label-group">
              <label for="input-store-address">Alamat Fisik Toko</label>
            </div>
            <textarea class="textarea-styled" id="input-store-address" name="address" rows="3" placeholder="cth: Jl. Malioboro No. 45, Yogyakarta">{{ old('address', $store->address) }}</textarea>
            <div class="field-hint-text">Alamat gerai atau gudang pengambilan barang.</div>
          </div>
        </div>

        <!-- Panel 4: Pembayaran & QR Statis -->
        <div class="tab-panel" role="tabpanel" id="panel-pembayaran" aria-labelledby="tab-pembayaran" hidden>
          <div class="section-intro">
            <h3>QRIS & Pembayaran Statis Toko</h3>
            <p>Unggah gambar QRIS atau QR pembayaran statis toko Anda untuk mekanisme beli langsung oleh pelanggan.</p>
          </div>

          <div class="field">
            <div class="field-label-group">
              <label>Gambar QRIS / QR Pembayaran</label>
              <span id="selected-qr-file-name" style="font-size:12px;color:#64748b;font-weight:normal">
                {{ $store->qr_image_path ? 'QR aktif' : 'Belum ada QR' }}
              </span>
            </div>

            <div class="logo-uploader-row">
              <div class="logo-preview-thumb" id="qr-preview-box" style="border-radius:12px;background:#f8fafc;display:flex;align-items:center;justify-content:center;overflow:hidden">
                @if($store->qrImageUrl())
                  <img src="{{ $store->qrImageUrl() }}" alt="QR Toko" id="qr-preview-img" style="width:100%;height:100%;object-fit:contain">
                @else
                  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M10 7H7v3M17 7h-3v3M10 17H7v-3M17 17h-3v-3"/></svg>
                @endif
              </div>

              <div class="logo-upload-meta">
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                  <label class="file-picker-btn">
                    Pilih Gambar QR
                    <input type="file" name="qr_image" id="qr-file-input" accept="image/png,image/jpeg,image/webp,image/gif">
                  </label>
                  @if($store->qr_image_path)
                    <label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--critical);cursor:pointer;margin-left:8px">
                      <input type="checkbox" name="remove_qr" value="1"> Hapus QR
                    </label>
                  @endif
                </div>
                <div class="logo-hint-text">
                  Format didukung: PNG, JPG, WebP (Maks. 5 MB). Pastikan gambar QR kontras dan jelas agar mudah discan oleh aplikasi e-wallet / mobile banking pembeli.
                </div>
              </div>
            </div>
          </div>

          <div class="field" style="margin-top:20px;margin-bottom:0">
            <div class="field-label-group">
              <label for="input-store-qr-name">Nama Merchant / Rekening QRIS (Opsional)</label>
            </div>
            <input class="input-styled" type="text" id="input-store-qr-name" name="qr_account_name" value="{{ old('qr_account_name', $store->qr_account_name) }}" placeholder="cth: QRIS Toko Byvii / BCA 1234567890 a/n Byvii Official">
            <div class="field-hint-text">Ditampilkan di atas QR code pada saat pelanggan melakukan checkout agar mereka dapat memverifikasi nama penerima transfer.</div>
          </div>
        </div>

        <!-- Panel 5: Katalog & Visibilitas -->
        <div class="tab-panel" role="tabpanel" id="panel-katalog" aria-labelledby="tab-katalog" hidden>
          <div class="section-intro">
            <h3>Pengaturan Katalog & Visibilitas</h3>
            <p>Atur preferensi tampilan produk pada etalase katalog toko.</p>
          </div>

          <label class="feature-toggle-row">
            <div class="custom-switch">
              <input type="checkbox" name="hide_component_products" value="1" {{ old('hide_component_products', $store->hide_component_products) ? 'checked' : '' }}>
              <span class="switch-slider"></span>
            </div>
            <div>
              <strong style="font-size:14px;color:#0f172a;display:block">Sembunyikan Otomatis Produk Satuan Komponen Setelan</strong>
              <p style="font-size:13px;color:#64748b;margin:4px 0 0;line-height:1.5">
                Jika diaktifkan, produk tunggal yang telah digabungkan ke dalam Paket Setelan (Bundle) akan disembunyikan otomatis dari katalog umum agar etalase toko lebih fokus pada paket set.
              </p>
            </div>
          </label>
        </div>
      </div>

      <!-- Integrated Card Footer -->
      <div class="profile-card-footer">
        <button class="btn-save" type="submit">
          Simpan Perubahan
        </button>
        <a class="btn-cancel" href="{{ route('admin.dashboard', $store) }}">
          Batal
        </a>
      </div>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var nameInput = document.getElementById('input-store-name');
  var descInput = document.getElementById('input-store-desc');
  var fileInput = document.getElementById('logo-file-input');
  var nameCounter = document.getElementById('name-counter');
  var descCounter = document.getElementById('desc-counter');

  function updateCounters() {
    if (nameInput && nameCounter) nameCounter.innerText = nameInput.value.length + ' / 100';
    if (descInput && descCounter) descCounter.innerText = descInput.value.length + ' / 2000';
  }

  if (nameInput) nameInput.addEventListener('input', updateCounters);
  if (descInput) descInput.addEventListener('input', updateCounters);

  // Client-Side Instant Image Preview
  if (fileInput) {
    fileInput.addEventListener('change', function (e) {
      var file = e.target.files[0];
      if (!file) return;

      var fileNameEl = document.getElementById('selected-file-name');
      if (fileNameEl) fileNameEl.innerText = file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';

      var objectUrl = URL.createObjectURL(file);
      var previewBox = document.getElementById('logo-preview-box');
      if (previewBox) {
        previewBox.innerHTML = '<img src="' + objectUrl + '" style="width:100%;height:100%;object-fit:cover">';
      }
    });
  }

  // QR Preview
  var qrFileInput = document.getElementById('qr-file-input');
  if (qrFileInput) {
    qrFileInput.addEventListener('change', function (e) {
      var file = e.target.files[0];
      if (!file) return;

      var qrFileNameEl = document.getElementById('selected-qr-file-name');
      if (qrFileNameEl) qrFileNameEl.innerText = file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';

      var objectUrl = URL.createObjectURL(file);
      var qrPreviewBox = document.getElementById('qr-preview-box');
      if (qrPreviewBox) {
        qrPreviewBox.innerHTML = '<img src="' + objectUrl + '" style="width:100%;height:100%;object-fit:contain">';
      }
    });
  }

  updateCounters();

  // ---- Tab ----
  var tabs = Array.prototype.slice.call(document.querySelectorAll('.profile-tab'));
  function selectTab(tab, focus) {
    tabs.forEach(function (t) {
      var active = t === tab;
      t.setAttribute('aria-selected', active ? 'true' : 'false');
      t.tabIndex = active ? 0 : -1;
      document.getElementById(t.getAttribute('aria-controls')).hidden = !active;
    });
    if (focus) tab.focus();
  }
  tabs.forEach(function (tab, i) {
    tab.addEventListener('click', function () { selectTab(tab, false); });
    tab.addEventListener('keydown', function (e) {
      var next = null;
      if (e.key === 'ArrowRight') next = tabs[(i + 1) % tabs.length];
      if (e.key === 'ArrowLeft') next = tabs[(i - 1 + tabs.length) % tabs.length];
      if (e.key === 'Home') next = tabs[0];
      if (e.key === 'End') next = tabs[tabs.length - 1];
      if (next) { e.preventDefault(); selectTab(next, true); }
    });
  });
  var profileForm = document.getElementById('store-profile-form');
  if (profileForm) {
    profileForm.addEventListener('invalid', function (e) {
      var panel = e.target.closest('.tab-panel');
      if (panel && panel.hidden) {
        selectTab(document.getElementById(panel.getAttribute('aria-labelledby')), false);
      }
    }, true);
  }

  // ---- Simpan profil tanpa reload halaman (endpoint API JSON) ----
  var form = document.getElementById('store-profile-form');
  if (!form || !window.AdminApi || !window.AdminUI) return;

  var submitButton = form.querySelector('button[type="submit"]');
  var submitLabel = submitButton ? submitButton.innerHTML : '';
  var errorBox = document.getElementById('profile-form-error');

  function showErrors(error) {
    var messages = [];
    if (error && error.body && error.body.errors) {
      Object.keys(error.body.errors).forEach(function (key) {
        (error.body.errors[key] || []).forEach(function (message) { messages.push(message); });
      });
    }
    if (!messages.length) { messages.push((error && error.message) || 'Gagal menyimpan profil.'); }
    if (errorBox) {
      errorBox.innerHTML = messages.map(AdminUI.esc).join('<br>');
      errorBox.hidden = false;
      errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
      AdminUI.toast(messages[0], 'error');
    }
  }

  function updateBranding(store) {
    if (!store) return;

    document.querySelectorAll('.dash-brand-name').forEach(function (el) { el.textContent = store.name; });
    document.querySelectorAll('.dash-brand-initial').forEach(function (el) { el.textContent = store.name.charAt(0).toUpperCase(); });
    if (store.logo_url) {
      document.querySelectorAll('.dash-brand-logo').forEach(function (img) { img.src = store.logo_url; });
    }

    var slugInput = document.getElementById('input-store-slug');
    if (slugInput && store.slug) { slugInput.value = store.slug; }
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    if (errorBox) errorBox.hidden = true;

    var payload = new FormData(form);
    payload.append('_method', 'PUT');

    if (submitButton) {
      submitButton.disabled = true;
      submitButton.innerHTML = 'Menyimpan…';
    }

    AdminApi.request(form.getAttribute('data-update-url'), { method: 'POST', body: payload })
      .then(function (json) {
        AdminUI.toast(json.message, 'success');
        updateBranding(json.store);
      })
      .catch(showErrors)
      .then(function () {
        if (submitButton) {
          submitButton.disabled = false;
          submitButton.innerHTML = submitLabel;
        }
      });
  });
});
</script>
@endsection
