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
  .profile-form-container {
    width: 100%;
  }

  /* Layout dua kolom: identitas toko di kiri, pengaturan katalog di kanan */
  .profile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    gap: 24px;
    align-items: start;
  }

  .profile-col {
    display: flex;
    flex-direction: column;
    gap: 24px;
    min-width: 0;
  }

  /* Form Section Cards */
  .aesthetic-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: var(--r-xl);
    padding: 24px 28px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03), 0 4px 12px -2px rgba(0, 0, 0, 0.02);
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
  }

  .aesthetic-card:hover {
    box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.06);
    border-color: #cbd5e1;
  }

  .card-header-styled {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 1px solid #f1f5f9;
  }

  .card-title-group {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .card-icon-badge {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #f0f7ff;
    color: #0064e0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    border: 1px solid #e0f2fe;
    flex-shrink: 0;
  }

  .card-title-group h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.2px;
  }

  .card-title-group p {
    margin: 2px 0 0;
    font-size: 13px;
    color: #64748b;
  }

  /* Logo Upload Dropzone */
  .logo-upload-box {
    display: grid;
    grid-template-columns: 96px 1fr;
    gap: 20px;
    align-items: center;
    padding: 18px;
    background: #f8fafc;
    border: 1.5px dashed #cbd5e1;
    border-radius: var(--r-lg);
    transition: all 0.2s ease;
  }

  .logo-upload-box:hover {
    border-color: #0064e0;
    background: #f0f7ff;
  }

  .logo-preview-current {
    width: 90px;
    height: 90px;
    border-radius: 16px;
    object-fit: cover;
    background: #fff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
  }

  .logo-preview-current img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }

  .logo-preview-current .logo-placeholder-icon {
    font-size: 32px;
    color: #94a3b8;
  }

  .logo-upload-controls {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .logo-upload-button-wrap {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
  }

  .custom-file-input-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    cursor: pointer;
    transition: all 0.15s ease;
  }

  .custom-file-input-btn:hover {
    background: #f8fafc;
    border-color: #94a3b8;
  }

  .custom-file-input-btn input[type="file"] {
    display: none;
  }

  .logo-upload-hint {
    font-size: 12px;
    color: #64748b;
    line-height: 1.4;
  }

  .gdrive-badge-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8px;
    background: #e0f2fe;
    color: #0369a1;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
  }

  /* Slug Custom Input Group */
  .slug-input-group {
    display: flex;
    align-items: stretch;
    border: 1px solid #cbd5e1;
    border-radius: var(--r-lg);
    background: #fff;
    overflow: hidden;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
  }

  .slug-input-group:focus-within {
    border-color: #0064e0;
    box-shadow: 0 0 0 3px rgba(0, 100, 224, 0.12);
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
    padding: 12px 14px !important;
    font-size: 15px !important;
    font-weight: 600 !important;
    color: #0f172a !important;
    flex: 1;
    width: auto !important;
  }

  /* Form Label Enhancements */
  .field-label-styled {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 6px;
  }

  .field-hint-styled {
    font-size: 12px;
    color: #64748b;
    margin-top: 5px;
  }

  .input-styled, .textarea-styled {
    width: 100%;
    padding: 11px 14px;
    font-size: 14px;
    border: 1px solid #cbd5e1;
    border-radius: var(--r-lg);
    background: #ffffff;
    color: #0f172a;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    font-family: inherit;
    box-sizing: border-box;
  }

  .input-styled:focus, .textarea-styled:focus {
    outline: none;
    border-color: #0064e0;
    box-shadow: 0 0 0 3px rgba(0, 100, 224, 0.12);
  }

  /* Feature Toggle Card */
  .feature-toggle-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: var(--r-lg);
    padding: 16px 18px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .feature-toggle-card:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
  }

  .custom-switch {
    position: relative;
    width: 44px;
    height: 24px;
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
    transition: .25s;
    border-radius: 24px;
  }

  .switch-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .25s;
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
  }

  .custom-switch input:checked + .switch-slider {
    background-color: #0064e0;
  }

  .custom-switch input:checked + .switch-slider:before {
    transform: translateX(20px);
  }

  /* Form Action Toolbar */
  .form-actions-toolbar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0 40px;
    margin-top: 24px;
  }

  @media (max-width: 1279px) {
    .profile-col .form-row { grid-template-columns: minmax(0, 1fr); }
  }

  @media (max-width: 767px) {
    .profile-grid { grid-template-columns: minmax(0, 1fr); }
    .profile-grid, .profile-col { gap: 16px; }
  }
</style>

<div class="profile-form-container">
  <form method="POST" action="{{ route('admin.profile.update', $store) }}" enctype="multipart/form-data" id="store-profile-form" data-update-url="{{ route('api.profile.update', $store) }}">
    @csrf
    @method('PUT')

    <div class="alert alert-error" id="profile-form-error" hidden style="margin-bottom:16px"></div>

    <div class="profile-grid">
      <div class="profile-col">
        <!-- Card 1: Identitas & Logo Toko -->
        <div class="aesthetic-card">
          <div class="card-header-styled">
            <div class="card-title-group">
              <div class="card-icon-badge">✨</div>
              <div>
                <h3>Identitas & Branding Toko</h3>
                <p>Atur nama, logo brand, dan deskripsi singkat toko Anda.</p>
              </div>
            </div>
            <span class="gdrive-badge-pill">☁️ Google Drive Storage</span>
          </div>

          <!-- Logo Upload Dropzone -->
          <div class="field">
            <div class="field-label-styled">
              <span>Logo Toko</span>
              <small style="color:#64748b">Format: PNG, JPG, WebP (Maks. 5 MB)</small>
            </div>

            <div class="logo-upload-box">
              <div class="logo-preview-current" id="logo-preview-box">
                @if($store->logoUrl())
                  <img src="{{ $store->logoUrl() }}" alt="{{ $store->name }}" id="logo-preview-img">
                @else
                  <div class="logo-placeholder-icon" id="logo-preview-placeholder">🏪</div>
                @endif
              </div>

              <div class="logo-upload-controls">
                <div class="logo-upload-button-wrap">
                  <label class="custom-file-input-btn">
                    📁 Pilih Gambar Logo
                    <input type="file" name="logo" id="logo-file-input" accept="image/png,image/jpeg,image/webp,image/gif">
                  </label>
                  <span id="selected-file-name" style="font-size:12px;color:#64748b">
                    {{ $store->logo_path ? 'Logo saat ini aktif' : 'Belum ada logo dipilih' }}
                  </span>
                </div>
                <div class="logo-upload-hint">
                  Logo akan otomatis diunggah langsung ke folder Google Drive toko dan ditampilkan di seluruh katalog publik.
                </div>
              </div>
            </div>
          </div>

          <!-- Nama Toko -->
          <div class="field" style="margin-top:20px">
            <div class="field-label-styled">
              <label for="input-store-name">Nama Resmi Toko <span style="color:var(--critical)">*</span></label>
              <small style="color:#64748b" id="name-counter">0 / 100</small>
            </div>
            <input class="input-styled" type="text" id="input-store-name" name="name" value="{{ old('name', $store->name) }}" required maxlength="100" placeholder="cth: Kemeja Vintage Official">
            <div class="field-hint-styled">Nama toko akan tampil pada navbar, header katalog, dan label barcode retail.</div>
          </div>

          <!-- Deskripsi Toko -->
          <div class="field" style="margin-top:20px;margin-bottom:0">
            <div class="field-label-styled">
              <label for="input-store-desc">Deskripsi & Slogan Toko</label>
              <small style="color:#64748b" id="desc-counter">0 / 2000</small>
            </div>
            <textarea class="textarea-styled" id="input-store-desc" name="description" rows="3" maxlength="2000" placeholder="Jelaskan jenis produk dan keunggulan toko Anda...">{{ old('description', $store->description) }}</textarea>
            <div class="field-hint-styled">Deskripsi ini membantu pelanggan mengenali tokomu dan digunakan sebagai informasi pengenal toko di Google.</div>
          </div>
        </div>
      </div>

      <div class="profile-col">
        <!-- Card 2: Alamat URL & Domain Katalog -->
        <div class="aesthetic-card">
          <div class="card-header-styled">
            <div class="card-title-group">
              <div class="card-icon-badge">🔗</div>
              <div>
                <h3>Alamat URL & Slug Katalog</h3>
                <p>Tautan unik untuk mengakses etalase katalog online tokomu.</p>
              </div>
            </div>
            <span class="badge badge-neutral" style="font-size:11px">SEO Ready</span>
          </div>

          <div class="field">
            <div class="field-label-styled">
              <label for="input-store-slug">Slug URL Toko</label>
              <span style="font-size:11px;color:#0284c7;font-weight:600">301 Auto-Redirect</span>
            </div>

            <div class="slug-input-group">
              <span class="slug-domain-prefix">{{ url('/') }}/</span>
              <input class="input-styled slug-input-field" type="text" id="input-store-slug" name="slug" value="{{ old('slug', $store->slug) }}" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" placeholder="{{ $store->slug }}">
            </div>

            <div style="margin-top:12px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px 14px;font-size:12px;color:#0369a1;line-height:1.5">
              💡 <strong>Info Penting SEO:</strong> Jika slug diubah, URL lama toko secara otomatis dialihkan (301 Permanent Redirect) ke URL baru. Pelanggan yang membuka tautan lama tidak akan menemui halaman error 404.
            </div>
          </div>
        </div>

        <!-- Card 3: Kontak & Lokasi Fisik -->
        <div class="aesthetic-card">
          <div class="card-header-styled">
            <div class="card-title-group">
              <div class="card-icon-badge">📍</div>
              <div>
                <h3>Kontak & Lokasi Toko</h3>
                <p>Informasi layanan pelanggan dan alamat fisik gerai toko.</p>
              </div>
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <div class="field-label-styled">
                <label for="input-store-contact">Kontak CS / WhatsApp</label>
              </div>
              <input class="input-styled" type="text" id="input-store-contact" name="contact_information" value="{{ old('contact_information', $store->contact_information) }}" placeholder="cth: 0812-3456-7890 / cs@tokomu.com">
              <div class="field-hint-styled">Nomor WhatsApp atau email untuk pertanyaan calon pembeli.</div>
            </div>

            <div class="field">
              <div class="field-label-styled">
                <label for="input-store-address">Alamat Fisik Toko</label>
              </div>
              <textarea class="textarea-styled" id="input-store-address" name="address" rows="2" style="min-height:44px" placeholder="cth: Jl. Malioboro No. 45, Yogyakarta">{{ old('address', $store->address) }}</textarea>
              <div class="field-hint-styled">Alamat gerai atau gudang pengambilan barang.</div>
            </div>
          </div>
        </div>

        <!-- Card 4: Pengaturan Katalog & Visibilitas -->
        <div class="aesthetic-card">
          <div class="card-header-styled">
            <div class="card-title-group">
              <div class="card-icon-badge">⚙️</div>
              <div>
                <h3>Pengaturan Katalog & Visibilitas</h3>
                <p>Atur preferensi tampilan produk paket setelan (bundle) pada katalog toko.</p>
              </div>
            </div>
          </div>

          <label class="feature-toggle-card">
            <div class="custom-switch">
              <input type="checkbox" name="hide_component_products" value="1" {{ old('hide_component_products', $store->hide_component_products) ? 'checked' : '' }}>
              <span class="switch-slider"></span>
            </div>
            <div>
              <strong style="font-size:14px;color:#0f172a;display:block">Sembunyikan Otomatis Produk Satuan Komponen Setelan</strong>
              <p style="font-size:13px;color:#64748b;margin:4px 0 0;line-height:1.5">
                Jika diaktifkan, semua produk tunggal yang telah digabungkan ke dalam Paket Setelan (Bundle) akan disembunyikan otomatis dari katalog umum agar etalase toko terlihat lebih rapi dan fokus pada paket set.
              </p>
            </div>
          </label>
        </div>
      </div>
    </div>

    <!-- Form Actions Toolbar -->
    <div class="form-actions-toolbar">
      <button class="btn btn-buy" type="submit" style="padding:14px 36px;font-size:15px;box-shadow:0 4px 14px rgba(0, 100, 224, 0.3)">
        💾 Simpan Perubahan Profil
      </button>
      <a class="btn btn-ghost" href="{{ route('admin.dashboard', $store) }}" style="padding:12px 24px">
        Batal
      </a>
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

  updateCounters();

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
      submitButton.innerHTML = '⏳ Menyimpan…';
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
