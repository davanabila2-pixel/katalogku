@extends('layouts.app')

@section('content')
<nav class="breadcrumb" aria-label="Navigasi Halaman">
  <ol class="breadcrumb-list">
    <li class="breadcrumb-item">
      <a href="{{ $store->url() }}" class="breadcrumb-link" title="Katalog {{ $store->name }}">
        <svg class="breadcrumb-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/></svg>
        <span>{{ $store->name }}</span>
      </a>
    </li>
    <li class="breadcrumb-separator" aria-hidden="true">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
    </li>
    <li class="breadcrumb-item">
      <a href="{{ route('store.profile', $store->slug) }}" class="breadcrumb-link">Profil Saya</a>
    </li>
    <li class="breadcrumb-separator" aria-hidden="true">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
    </li>
    <li class="breadcrumb-item active" aria-current="page">
      <span class="breadcrumb-current">Alamat Saya</span>
    </li>
  </ol>
</nav>

<div class="user-account-container" style="max-width: 900px; margin: 0 auto; padding-bottom: 50px;">
  <div class="user-account-header">
    <div class="user-account-avatar-wrap">
      <img src="{{ $profile->avatarUrl() }}" alt="{{ $profile->name }}" class="user-account-avatar" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
      <div class="user-account-avatar-fallback" style="display:none;">{{ $profile->initial() }}</div>
    </div>
    <div class="user-account-heading">
      <div style="display: inline-flex; align-items: center; gap: 6px; margin-bottom: 4px;">
        <span style="font-size: 12px; font-weight: 700; color: #0284c7; background: #e0f2fe; padding: 2px 8px; border-radius: var(--r-full);">Pelanggan {{ $store->name }}</span>
      </div>
      <h1 class="user-account-title">Alamat Saya di {{ $store->name }}</h1>
      <p class="user-account-subtitle">Atur alamat pengiriman dan titik peta untuk cek ongkos kirim akurat via Biteship.</p>
    </div>
  </div>

  <div class="user-account-card" style="background:#ffffff;border:1px solid var(--hairline-soft);border-radius:16px;padding:28px;box-shadow:0 4px 16px rgba(0,0,0,0.03);">
    <form method="POST" action="{{ route('store.address.update', $store->slug) }}" id="customerAddressForm">
      @csrf
      @method('PUT')

      <input type="hidden" name="latitude" id="inputLat" value="{{ old('latitude', $profile->latitude) }}">
      <input type="hidden" name="longitude" id="inputLng" value="{{ old('longitude', $profile->longitude) }}">

      {{-- Pencarian Google Maps / Lokasi --}}
      <div class="field" style="margin-bottom: 16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
          <label class="label" for="gmapsAddressSearch" style="font-weight:700;font-size:13px;color:#334155;margin:0;">
            📍 Cari Alamat / Lokasi (Google Maps)
          </label>
          <button type="button" class="btn-auto-gps" id="btnCustomerGps" onclick="locateUserPosition()" style="padding:4px 10px;font-size:11.5px;">
            <span id="btnCustomerGpsIcon">📍</span>
            <span id="btnCustomerGpsText">Gunakan Lokasi Saat Ini (GPS)</span>
          </button>
        </div>
        <div style="position:relative;">
          <input class="input" type="text" id="gmapsAddressSearch" placeholder="Ketik nama jalan, perumahan, kelurahan, atau tempat..." style="width:100%;border-radius:10px;padding:10px 14px;border:1.5px solid #cbd5e1;font-size:14px;">
        </div>
        <div id="gpsStatusHint" class="gps-status-hint" style="display:none;margin-top:6px;"></div>
      </div>

      {{-- Kanvas Peta Interaktif --}}
      <div style="margin-bottom: 20px;">
        <div id="customerMapCanvas" style="width: 100%; height: 320px; border-radius: 12px; background: #e2e8f0; border: 1px solid #cbd5e1; position: relative; overflow: hidden;">
          <div id="mapLoadingNotice" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:13px;color:#64748b;font-weight:600;">
            Memuat peta lokasi...
          </div>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px;font-size:12px;color:#64748b;">
          <span>* Geser penanda pin di peta untuk menyesuaikan titik lokasi rumah Anda.</span>
          <span id="coordsDisplayBadge" style="font-family:monospace;background:#f1f5f9;padding:2px 8px;border-radius:6px;font-weight:600;">
            {{ $profile->hasCoordinates() ? "{$profile->latitude}, {$profile->longitude}" : "Titik belum ditentukan" }}
          </span>
        </div>
      </div>

      {{-- Form Input Alamat Rinci --}}
      <div class="field" style="margin-bottom: 16px;">
        <label class="label" for="address" style="font-weight:700;font-size:13px;color:#334155;margin-bottom:6px;display:block;">
          Alamat Pengiriman Lengkap <span style="color:#ef4444;">*</span>
        </label>
        <textarea class="textarea" id="address" name="address" rows="3" required placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan, patokan..." style="width:100%;border-radius:10px;padding:10px 14px;border:1.5px solid #cbd5e1;font-size:14px;font-family:inherit;">{{ old('address', $profile->address) }}</textarea>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:16px;">
        <div class="field">
          <label class="label" for="postal_code" style="font-weight:700;font-size:12.5px;color:#334155;margin-bottom:6px;display:block;">
            Kode Pos <span style="font-size:11px;color:#0284c7;font-weight:normal;">(Penting untuk Ongkir)</span>
          </label>
          <input class="input" type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $profile->postal_code) }}" placeholder="cth: 40115" maxlength="10" style="width:100%;border-radius:10px;padding:9px 12px;border:1.5px solid #cbd5e1;font-size:13.5px;">
        </div>

        <div class="field">
          <label class="label" for="city" style="font-weight:700;font-size:12.5px;color:#334155;margin-bottom:6px;display:block;">Kota / Kabupaten</label>
          <input class="input" type="text" id="city" name="city" value="{{ old('city', $profile->city) }}" placeholder="cth: Bandung" style="width:100%;border-radius:10px;padding:9px 12px;border:1.5px solid #cbd5e1;font-size:13.5px;">
        </div>

        <div class="field">
          <label class="label" for="district" style="font-weight:700;font-size:12.5px;color:#334155;margin-bottom:6px;display:block;">Kecamatan</label>
          <input class="input" type="text" id="district" name="district" value="{{ old('district', $profile->district) }}" placeholder="cth: Coblong" style="width:100%;border-radius:10px;padding:9px 12px;border:1.5px solid #cbd5e1;font-size:13.5px;">
        </div>
      </div>

      <div class="field" style="margin-bottom: 24px;">
        <label class="label" for="notes" style="font-weight:700;font-size:12.5px;color:#334155;margin-bottom:6px;display:block;">
          Patokan / Catatan Pengiriman (Opsional)
        </label>
        <input class="input" type="text" id="notes" name="notes" value="{{ old('notes', $profile->notes) }}" placeholder="cth: Rumah pagar putih depan lapangan, titipkan tetangga..." style="width:100%;border-radius:10px;padding:9px 12px;border:1.5px solid #cbd5e1;font-size:13.5px;">
      </div>

      <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; margin-bottom: 24px; font-size: 13px; color: #475569; line-height: 1.5; display:flex; gap:12px; align-items:flex-start;">
        <span style="font-size:18px;">🚚</span>
        <div>
          <strong>Integrasi Cek Ongkir Biteship:</strong> Titik koordinat dan kode pos yang Anda simpan akan otomatis digunakan di halaman checkout <strong>{{ $store->name }}</strong> untuk menghitung ongkos kirim kurir (JNE, SiCepat, J&T, dll) secara instan.
        </div>
      </div>

      <div style="display:flex;gap:12px;align-items:center;">
        <button type="submit" class="btn btn-primary" style="padding:11px 24px;border-radius:var(--r-full);font-weight:700;display:inline-flex;align-items:center;gap:8px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
          <span>Simpan Alamat Saya</span>
        </button>
        <a href="{{ route('store.profile', $store->slug) }}" class="btn btn-ghost" style="padding:11px 20px;border-radius:var(--r-full);">Ke Profil Saya</a>
        <a href="{{ $store->url() }}" class="btn btn-ghost" style="padding:11px 20px;border-radius:var(--r-full);">Katalog Toko</a>
      </div>
    </form>
  </div>
</div>

{{-- Leaflet CSS & JS (Fallback peta interaktif universal) --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

@if(!empty($googleMapsApiKey))
  <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsApiKey }}&libraries=places"></script>
@endif

<script>
  let defaultLat = {{ $profile->latitude ?: -6.2088 }};
  let defaultLng = {{ $profile->longitude ?: 106.8456 }};
  const hasSavedCoords = {{ $profile->hasCoordinates() ? 'true' : 'false' }};
  const googleApiKey = @js($googleMapsApiKey);

  let gmap, gmarker, gautocomplete;
  let lmap, lmarker;

  document.addEventListener('DOMContentLoaded', function() {
    initInteractiveMap();
  });

  function updateCoordInputs(lat, lng) {
    document.getElementById('inputLat').value = lat.toFixed(7);
    document.getElementById('inputLng').value = lng.toFixed(7);
    document.getElementById('coordsDisplayBadge').textContent = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
  }

  function initInteractiveMap() {
    document.getElementById('mapLoadingNotice').style.display = 'none';

    if (googleApiKey && typeof google !== 'undefined' && google.maps) {
      // 1. Google Maps Engine
      const mapCenter = { lat: defaultLat, lng: defaultLng };
      gmap = new google.maps.Map(document.getElementById('customerMapCanvas'), {
        center: mapCenter,
        zoom: hasSavedCoords ? 16 : 13,
        mapTypeControl: false,
        streetViewControl: false,
      });

      gmarker = new google.maps.Marker({
        position: mapCenter,
        map: gmap,
        draggable: true,
        title: 'Geser pin ke lokasi rumah Anda'
      });

      gmarker.addListener('dragend', function() {
        const pos = gmarker.getPosition();
        updateCoordInputs(pos.lat(), pos.lng());
        reverseGeocodeGoogle(pos.lat(), pos.lng());
      });

      gmap.addListener('click', function(e) {
        gmarker.setPosition(e.latLng);
        updateCoordInputs(e.latLng.lat(), e.latLng.lng());
        reverseGeocodeGoogle(e.latLng.lat(), e.latLng.lng());
      });

      // Autocomplete Google Places
      const searchInput = document.getElementById('gmapsAddressSearch');
      gautocomplete = new google.maps.places.Autocomplete(searchInput, {
        componentRestrictions: { country: 'id' },
        fields: ['address_components', 'geometry', 'formatted_address', 'name']
      });

      gautocomplete.addListener('place_changed', function() {
        const place = gautocomplete.getPlace();
        if (!place.geometry || !place.geometry.location) return;

        const loc = place.geometry.location;
        gmap.setCenter(loc);
        gmap.setZoom(17);
        gmarker.setPosition(loc);
        updateCoordInputs(loc.lat(), loc.lng());

        if (place.formatted_address) {
          document.getElementById('address').value = place.formatted_address;
        }

        extractAddressComponents(place.address_components);
      });

    } else {
      // 2. Leaflet / OpenStreetMap Universal Engine
      lmap = L.map('customerMapCanvas').setView([defaultLat, defaultLng], hasSavedCoords ? 15 : 13);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
      }).addTo(lmap);

      lmarker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(lmap);

      lmarker.on('dragend', function() {
        const pos = lmarker.getLatLng();
        updateCoordInputs(pos.lat, pos.lng);
        reverseGeocodeOSM(pos.lat, pos.lng);
      });

      lmap.on('click', function(e) {
        lmarker.setLatLng(e.latlng);
        updateCoordInputs(e.latlng.lat, e.latlng.lng);
        reverseGeocodeOSM(e.latlng.lat, e.latlng.lng);
      });

      // Search input fallback via OSM Nominatim
      const searchInput = document.getElementById('gmapsAddressSearch');
      let searchTimeout = null;
      searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        if (query.length < 3) return;

        searchTimeout = setTimeout(async () => {
          try {
            const resp = await fetch(`https://nominatim.openstreetmap.org/search?format=json&countrycodes=id&q=${encodeURIComponent(query)}`);
            const data = await resp.json();
            if (data && data.length > 0) {
              const item = data[0];
              const lat = parseFloat(item.lat);
              const lon = parseFloat(item.lon);
              lmap.setView([lat, lon], 16);
              lmarker.setLatLng([lat, lon]);
              updateCoordInputs(lat, lon);
              reverseGeocodeOSM(lat, lon);
            }
          } catch(e) {}
        }, 700);
      });
    }
  }

  function extractAddressComponents(components) {
    if (!components) return;
    components.forEach(c => {
      if (c.types.includes('postal_code')) {
        document.getElementById('postal_code').value = c.long_name;
      }
      if (c.types.includes('administrative_division_level_2')) {
        document.getElementById('city').value = c.long_name.replace('Kota ', '').replace('Kabupaten ', '');
      }
      if (c.types.includes('administrative_division_level_3') || c.types.includes('sublocality_level_1')) {
        document.getElementById('district').value = c.long_name.replace('Kecamatan ', '');
      }
    });
  }

  function reverseGeocodeGoogle(lat, lng) {
    if (typeof google === 'undefined' || !google.maps) return;
    const geocoder = new google.maps.Geocoder();
    geocoder.geocode({ location: { lat, lng } }, function(results, status) {
      if (status === 'OK' && results[0]) {
        document.getElementById('address').value = results[0].formatted_address;
        extractAddressComponents(results[0].address_components);
      }
    });
  }

  async function reverseGeocodeOSM(lat, lng) {
    const hint = document.getElementById('gpsStatusHint');
    try {
      const resp = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`, {
        headers: { 'Accept': 'application/json' }
      });
      const data = await resp.json();
      if (data && data.display_name) {
        document.getElementById('address').value = data.display_name;
        if (data.address) {
          if (data.address.postcode) document.getElementById('postal_code').value = data.address.postcode;
          if (data.address.city || data.address.county) document.getElementById('city').value = data.address.city || data.address.county;
          if (data.address.suburb || data.address.town || data.address.village) document.getElementById('district').value = data.address.suburb || data.address.town || data.address.village;
        }
      }
    } catch (e) {}
  }

  function locateUserPosition() {
    const btn = document.getElementById('btnCustomerGps');
    const btnText = document.getElementById('btnCustomerGpsText');
    const hint = document.getElementById('gpsStatusHint');

    if (!navigator.geolocation) {
      alert('Peramban Anda tidak mendukung deteksi lokasi otomatis.');
      return;
    }

    btn.disabled = true;
    btnText.textContent = 'Mencari Titik GPS...';
    hint.className = 'gps-status-hint info';
    hint.textContent = 'Mengambil sinyal GPS perangkat Anda...';
    hint.style.display = 'block';

    navigator.geolocation.getCurrentPosition(
      function(pos) {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        updateCoordInputs(lat, lng);

        if (gmap && gmarker) {
          const loc = new google.maps.LatLng(lat, lng);
          gmap.setCenter(loc);
          gmap.setZoom(17);
          gmarker.setPosition(loc);
          reverseGeocodeGoogle(lat, lng);
        } else if (lmap && lmarker) {
          lmap.setView([lat, lng], 16);
          lmarker.setLatLng([lat, lng]);
          reverseGeocodeOSM(lat, lng);
        }

        hint.className = 'gps-status-hint success';
        hint.textContent = '✓ Lokasi Anda berhasil ditemukan pada peta.';
        btn.disabled = false;
        btnText.textContent = 'Gunakan Lokasi Saat Ini (GPS)';
      },
      function(err) {
        btn.disabled = false;
        btnText.textContent = 'Gunakan Lokasi Saat Ini (GPS)';
        hint.className = 'gps-status-hint warning';
        hint.textContent = 'Akses GPS ditolak atau tidak tersedia. Silakan cari alamat atau geser pin di peta.';
      },
      { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
    );
  }
</script>
@endsection

