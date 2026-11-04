/**
 * Wilayah Indonesia Cascading Dropdown Helper (Select2 & Native Support)
 * Menghubungkan dropdown Provinsi -> Kota/Kabupaten -> Kecamatan
 * Menggunakan data lokal dari /api/wilayah
 */
(function(window) {
  function initWilayahDropdown(config) {
    const provinceSelect = document.getElementById(config.provinceSelectId);
    const citySelect = document.getElementById(config.citySelectId);
    const districtSelect = document.getElementById(config.districtSelectId);

    const provinceInput = config.provinceInputId ? document.getElementById(config.provinceInputId) : null;
    const cityInput = config.cityInputId ? document.getElementById(config.cityInputId) : null;
    const districtInput = config.districtInputId ? document.getElementById(config.districtInputId) : null;

    if (!provinceSelect || !citySelect || !districtSelect) {
      return null;
    }

    const hasSelect2 = typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.select2 !== 'undefined';
    const $ = window.jQuery;

    // Inisialisasi Select2 jika library jQuery & Select2 tersedia di halaman
    function setupSelect2(el, placeholderText) {
      if (!hasSelect2 || !el) return;
      const opts = {
        placeholder: placeholderText,
        allowClear: true,
        width: '100%',
        dropdownAutoWidth: true,
      };
      if (config.dropdownParent) {
        opts.dropdownParent = $(config.dropdownParent);
      }
      $(el).select2(opts);
    }

    function syncSelect2(el) {
      if (hasSelect2 && el) {
        $(el).trigger('change.select2');
      }
    }

    setupSelect2(provinceSelect, '-- Pilih Provinsi --');
    setupSelect2(citySelect, '-- Pilih Kota / Kabupaten --');
    setupSelect2(districtSelect, '-- Pilih Kecamatan --');

    let provincesData = [];
    let citiesData = [];
    let districtsData = [];

    // Helper untuk mengosongkan select
    function resetSelect(selectEl, placeholder) {
      selectEl.innerHTML = `<option value="">${placeholder}</option>`;
      selectEl.disabled = true;
      syncSelect2(selectEl);
    }

    // Load Daftar Provinsi
    async function loadProvinces() {
      try {
        const res = await fetch('/api/wilayah/provinces');
        if (!res.ok) return;
        provincesData = await res.json();

        provinceSelect.innerHTML = '<option value="">-- Pilih Provinsi --</option>';
        provincesData.forEach(p => {
          const opt = document.createElement('option');
          opt.value = p.id;
          opt.textContent = p.name;
          opt.dataset.name = p.name;
          provinceSelect.appendChild(opt);
        });
        provinceSelect.disabled = false;
        syncSelect2(provinceSelect);
      } catch (e) {
        console.warn('Gagal memuat provinsi:', e);
      }
    }

    // Load Daftar Kota berdasarkan ID Provinsi
    async function loadCities(provinceId, selectedCityId = null) {
      if (!provinceId) {
        resetSelect(citySelect, '-- Pilih Kota / Kabupaten --');
        resetSelect(districtSelect, '-- Pilih Kecamatan --');
        if (cityInput) cityInput.value = '';
        if (districtInput) districtInput.value = '';
        return;
      }

      citySelect.innerHTML = '<option value="">Memuat kota...</option>';
      citySelect.disabled = true;
      syncSelect2(citySelect);
      resetSelect(districtSelect, '-- Pilih Kecamatan --');

      try {
        const res = await fetch(`/api/wilayah/cities?province_id=${encodeURIComponent(provinceId)}`);
        if (!res.ok) return;
        citiesData = await res.json();

        citySelect.innerHTML = '<option value="">-- Pilih Kota / Kabupaten --</option>';
        citiesData.forEach(c => {
          const opt = document.createElement('option');
          opt.value = c.id;
          opt.textContent = c.name;
          opt.dataset.name = c.name;
          if (selectedCityId && (c.id === selectedCityId || String(c.id) === String(selectedCityId))) {
            opt.selected = true;
          }
          citySelect.appendChild(opt);
        });
        citySelect.disabled = false;
        syncSelect2(citySelect);

        if (selectedCityId) {
          const selOpt = citySelect.options[citySelect.selectedIndex];
          if (cityInput && selOpt) cityInput.value = selOpt.dataset.name || selOpt.textContent;
        }
      } catch (e) {
        console.warn('Gagal memuat kota:', e);
        resetSelect(citySelect, '-- Pilih Kota / Kabupaten --');
      }
    }

    // Load Daftar Kecamatan berdasarkan ID Kota
    async function loadDistricts(cityId, selectedDistrictId = null) {
      if (!cityId) {
        resetSelect(districtSelect, '-- Pilih Kecamatan --');
        if (districtInput) districtInput.value = '';
        return;
      }

      districtSelect.innerHTML = '<option value="">Memuat kecamatan...</option>';
      districtSelect.disabled = true;
      syncSelect2(districtSelect);

      try {
        const res = await fetch(`/api/wilayah/districts?city_id=${encodeURIComponent(cityId)}`);
        if (!res.ok) return;
        districtsData = await res.json();

        districtSelect.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
        districtsData.forEach(d => {
          const opt = document.createElement('option');
          opt.value = d.id;
          opt.textContent = d.name;
          opt.dataset.name = d.name;
          if (selectedDistrictId && (d.id === selectedDistrictId || String(d.id) === String(selectedDistrictId))) {
            opt.selected = true;
          }
          districtSelect.appendChild(opt);
        });
        districtSelect.disabled = false;
        syncSelect2(districtSelect);

        if (selectedDistrictId) {
          const selOpt = districtSelect.options[districtSelect.selectedIndex];
          if (districtInput && selOpt) districtInput.value = selOpt.dataset.name || selOpt.textContent;
        }
      } catch (e) {
        console.warn('Gagal memuat kecamatan:', e);
        resetSelect(districtSelect, '-- Pilih Kecamatan --');
      }
    }

    // Handlers
    function onProvinceChanged() {
      const selectedOpt = provinceSelect.options[provinceSelect.selectedIndex];
      const provinceName = selectedOpt && selectedOpt.value ? (selectedOpt.dataset.name || selectedOpt.textContent) : '';
      if (provinceInput) provinceInput.value = provinceName;

      loadCities(provinceSelect.value);
    }

    function onCityChanged() {
      const selectedOpt = citySelect.options[citySelect.selectedIndex];
      const cityName = selectedOpt && selectedOpt.value ? (selectedOpt.dataset.name || selectedOpt.textContent) : '';
      if (cityInput) cityInput.value = cityName;

      loadDistricts(citySelect.value);
    }

    function onDistrictChanged() {
      const selectedOpt = districtSelect.options[districtSelect.selectedIndex];
      const districtName = selectedOpt && selectedOpt.value ? (selectedOpt.dataset.name || selectedOpt.textContent) : '';
      if (districtInput) districtInput.value = districtName;
    }

    // Pasang Event Listeners (kompatibel dengan Select2 dan Native)
    if (hasSelect2) {
      $(provinceSelect).on('change', onProvinceChanged);
      $(citySelect).on('change', onCityChanged);
      $(districtSelect).on('change', onDistrictChanged);
    } else {
      provinceSelect.addEventListener('change', onProvinceChanged);
      citySelect.addEventListener('change', onCityChanged);
      districtSelect.addEventListener('change', onDistrictChanged);
    }

    // Sinkronisasi otomatis dari nama teks (cth: "Bandung", "Bandung Wetan")
    async function resolveAndSelect(cityText, districtText, provinceText = '') {
      if (!cityText && !districtText && !provinceText) return;

      try {
        const url = `/api/wilayah/resolve?city=${encodeURIComponent(cityText || '')}&district=${encodeURIComponent(districtText || '')}&province=${encodeURIComponent(provinceText || '')}`;
        const res = await fetch(url);
        if (!res.ok) return;
        const resolved = await res.json();

        if (resolved.province && resolved.province.id) {
          provinceSelect.value = resolved.province.id;
          if (provinceInput) provinceInput.value = resolved.province.name;
          syncSelect2(provinceSelect);

          await loadCities(resolved.province.id, resolved.city ? resolved.city.id : null);

          if (resolved.city && resolved.city.id) {
            citySelect.value = resolved.city.id;
            if (cityInput) cityInput.value = resolved.city.name;
            syncSelect2(citySelect);

            await loadDistricts(resolved.city.id, resolved.district ? resolved.district.id : null);

            if (resolved.district && resolved.district.id) {
              districtSelect.value = resolved.district.id;
              if (districtInput) districtInput.value = resolved.district.name;
              syncSelect2(districtSelect);
            }
          }
        }
      } catch (e) {
        console.warn('Gagal resolve wilayah:', e);
      }
    }

    // Reset dropdowns ke awal
    function reset() {
      provinceSelect.value = '';
      syncSelect2(provinceSelect);
      resetSelect(citySelect, '-- Pilih Kota / Kabupaten --');
      resetSelect(districtSelect, '-- Pilih Kecamatan --');
      if (provinceInput) provinceInput.value = '';
      if (cityInput) cityInput.value = '';
      if (districtInput) districtInput.value = '';
    }

    // Inisialisasi awal
    (async function init() {
      await loadProvinces();

      const initProv = config.initialProvince || (provinceInput ? provinceInput.value : '');
      const initCity = config.initialCity || (cityInput ? cityInput.value : '');
      const initDist = config.initialDistrict || (districtInput ? districtInput.value : '');

      if (initCity || initDist || initProv) {
        await resolveAndSelect(initCity, initDist, initProv);
      }
    })();

    return {
      resolveAndSelect,
      loadProvinces,
      loadCities,
      loadDistricts,
      reset,
      setFromText: function(cityName, districtName, provinceName = '') {
        return resolveAndSelect(cityName, districtName, provinceName);
      }
    };
  }

  window.initWilayahDropdown = initWilayahDropdown;
})(window);
