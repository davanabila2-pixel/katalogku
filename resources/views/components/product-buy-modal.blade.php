<div id="productBuyModal" class="product-chat-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="buyModalTitle">
  <div class="product-chat-backdrop" onclick="closeProductBuy()"></div>
  <div class="product-buy-dialog">
    {{-- Header --}}
    <div class="product-buy-header">
      <div>
        <div class="product-buy-title" id="buyModalTitle">Beli Langsung via QR Toko</div>
        <div class="product-buy-store" id="buyModalStoreName">Toko</div>
      </div>
      <button type="button" class="product-chat-close-btn" onclick="closeProductBuy()" aria-label="Tutup dialog beli">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    {{-- Body: Form State --}}
    <div class="product-buy-body" id="buyModalFormBody">
      {{-- 1. Ringkasan Produk & Jumlah --}}
      <div class="buy-section-card">
        <div class="buy-product-row">
          <img id="buyModalProductImg" src="" alt="Produk" class="buy-product-thumb">
          <div class="buy-product-detail">
            <h4 id="buyModalProductName" class="buy-product-name">Nama Produk</h4>
            <div id="buyModalProductPrice" class="buy-product-price">Rp 0</div>
            <div id="buyModalStockHint" class="buy-stock-hint">Stok tersedia: 0</div>
          </div>
        </div>

        <div class="buy-qty-row">
          <label for="buyModalQty" class="buy-qty-label">Jumlah Pembelian:</label>
          <div class="buy-qty-stepper">
            <button type="button" class="stepper-btn" onclick="adjustBuyQty(-1)" aria-label="Kurangi jumlah">−</button>
            <input type="number" id="buyModalQty" class="stepper-input" value="1" min="1" oninput="onBuyQtyChanged(this.value)">
            <button type="button" class="stepper-btn" onclick="adjustBuyQty(1)" aria-label="Tambah jumlah">+</button>
          </div>
        </div>

        <div class="buy-total-banner">
          <span>Total Pembayaran:</span>
          <strong id="buyModalTotalPrice" class="buy-total-amount">Rp 0</strong>
        </div>
      </div>

      {{-- 2. Area QR Pembayaran Toko --}}
      <div class="buy-section-card buy-qr-card" id="buyModalQrSection">
        <div class="buy-card-heading">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M10 7H7v3M17 7h-3v3M10 17H7v-3M17 17h-3v-3"/></svg>
          <span>Pindai QRIS / QR Pembayaran Toko</span>
        </div>

        {{-- Jika toko punya QR --}}
        <div id="buyModalQrContent" style="display:none;text-align:center">
          <div class="qr-image-frame">
            <img id="buyModalQrImg" src="" alt="QR Pembayaran Toko" class="qr-code-img">
          </div>
          <div id="buyModalQrAccount" class="qr-account-badge">QRIS Toko</div>
          <div class="qr-instruction-steps">
            <ol>
              <li>Buka aplikasi m-Banking atau E-Wallet (BCA, Mandiri, BRI, BNI, GoPay, OVO, ShopeePay, Dana, dll).</li>
              <li>Scan kode QR di atas dan bayar pas sesuai total: <strong id="buyModalCopyTotal" style="color:#0f172a">Rp 0</strong></li>
              <li>Screenshot bukti pembayaran dan lampirkan pada form bukti di bawah.</li>
            </ol>
          </div>
        </div>

        {{-- Jika toko belum upload QR --}}
        <div id="buyModalNoQrAlert" class="buy-no-qr-alert" style="display:none">
          <div style="font-weight:700;font-size:13.5px;margin-bottom:4px">ℹ️ QRIS Toko Belum Diunggah</div>
          <div>Toko ini belum mengunggah gambar QRIS. Pesanan Anda tetap akan tercatat di sistem dan diteruskan ke WhatsApp toko untuk instruksi transfer manual.</div>
        </div>
      </div>

      {{-- 3. Data Pemesan & Alamat Otomatis --}}
      <div class="buy-section-card">
        <div class="buy-card-heading">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span>Data Pemesan & Pengiriman</span>
        </div>

        <div class="buy-field">
          <label for="buyBuyerName">Nama Lengkap <span style="color:var(--critical)">*</span></label>
          <input type="text" id="buyBuyerName" class="buy-input" placeholder="cth: Budi Santoso" required>
        </div>

        <div class="buy-field">
          <label for="buyBuyerPhone">No. WhatsApp Pembeli <span style="color:var(--critical)">*</span></label>
          <input type="tel" id="buyBuyerPhone" class="buy-input" placeholder="cth: 081234567890" required>
        </div>

        <div class="buy-field">
          <div class="buy-label-row">
            <label for="buyBuyerAddress">Alamat Pengiriman Lengkap <span style="color:var(--critical)">*</span></label>
            <button type="button" id="btnAutoGps" class="btn-auto-gps" onclick="autoDetectAddress()" title="Ambil alamat otomatis menggunakan GPS perangkat">
              <span id="btnAutoGpsIcon">📍</span>
              <span id="btnAutoGpsText">Gunakan Lokasi Saya</span>
            </button>
          </div>
          <textarea id="buyBuyerAddress" class="buy-textarea" rows="2" placeholder="Nama jalan, RT/RW, kelurahan, kecamatan, kota/kabupaten, kode pos..." required></textarea>
          <div id="gpsStatusMessage" class="gps-status-hint" style="display:none;"></div>
        </div>

        <div class="buy-field" style="margin-bottom:0">
          <label for="buyBuyerNotes">Catatan Tambahan (Opsional)</label>
          <input type="text" id="buyBuyerNotes" class="buy-input" placeholder="cth: Warna hitam, ukuran L, titipkan di pos satpam...">
        </div>
      </div>

      {{-- 4. Konfirmasi Bukti Pembayaran --}}
      <div class="buy-section-card">
        <div class="buy-card-heading">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <span>Unggah Bukti Transfer / QRIS</span>
          <span class="badge-optional">Disarankan</span>
        </div>

        <div class="proof-upload-area" id="proofUploadArea" onclick="document.getElementById('buyPaymentProofInput').click()">
          <input type="file" id="buyPaymentProofInput" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="onPaymentProofSelected(this)">
          
          {{-- Placeholder sebelum upload --}}
          <div id="proofPlaceholder" class="proof-placeholder">
            <div class="proof-icon-circle">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </div>
            <div class="proof-text-primary">Sentuh untuk pilih foto bukti transfer / QRIS</div>
            <div class="proof-text-muted">JPG, PNG, atau WebP (Maks. 5MB)</div>
          </div>

          {{-- Preview setelah upload --}}
          <div id="proofPreviewBox" class="proof-preview-box" style="display:none;">
            <img id="proofPreviewImg" src="" alt="Bukti Pembayaran" class="proof-thumb">
            <div class="proof-info">
              <div id="proofFileName" class="proof-filename">bukti-transfer.jpg</div>
              <div class="proof-status-tag">✓ Siap Dilampirkan</div>
              <button type="button" class="btn-remove-proof" onclick="removePaymentProof(event)">
                ✕ Hapus Bukti
              </button>
            </div>
          </div>
        </div>
      </div>

      {{-- Alert Error --}}
      <div id="buyErrorMessage" class="buy-error-banner" style="display:none;"></div>
    </div>

    {{-- Body: Success State --}}
    <div class="product-buy-body buy-success-body" id="buyModalSuccessBody" style="display:none;">
      <div class="buy-success-card">
        <div class="success-icon-badge">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <h3 class="success-heading">Pesanan Berhasil Dicatat!</h3>
        <p class="success-desc">Data pesanan Anda telah tersimpan di sistem toko. Lanjutkan ke WhatsApp toko untuk konfirmasi pemrosesan.</p>

        <div class="order-code-box">
          <div class="order-code-label">Nomor Pesanan:</div>
          <div class="order-code-value" id="successOrderCode">-</div>
        </div>

        <div class="success-summary-box">
          <div class="summary-line">
            <span>Total Pembayaran:</span>
            <strong id="successTotalPrice">Rp 0</strong>
          </div>
          <div class="summary-line">
            <span>Status Bukti:</span>
            <span id="successProofStatus" class="proof-badge-success">Terunggah</span>
          </div>
        </div>

        <a id="btnProceedWhatsApp" href="#" target="_blank" rel="noopener" class="btn-buy-confirm-wa" style="text-decoration:none;margin-top:14px;width:100%;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
          <span>Buka Chat WhatsApp Toko</span>
        </a>

        <button type="button" class="btn-secondary-close" onclick="closeProductBuy()">
          Tutup & Kembali ke Katalog
        </button>
      </div>
    </div>

    {{-- Footer: Submit Form --}}
    <div class="product-buy-footer" id="buyModalFooter">
      <button type="button" id="buyModalSubmitBtn" class="btn-buy-confirm-wa" onclick="submitBuyOrder()">
        <svg id="submitBtnIconWa" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
        <span id="buyModalSubmitText">Kirim Pesanan & Konfirmasi via WA</span>
        <svg id="submitBtnIconArrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </button>
    </div>
  </div>
</div>

<script>
(function() {
  let activeBuyProduct = null;
  let currentQty = 1;
  let selectedProofFile = null;
  let isSubmitting = false;

  const authUser = {
    name: @js(auth()->check() ? auth()->user()->name : ''),
  };

  function formatRp(num) {
    return 'Rp ' + Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  }

  function cleanPhone(raw) {
    if (!raw) return null;
    let digits = String(raw).replace(/[^0-9]/g, '');
    if (digits.startsWith('0')) digits = '62' + digits.slice(1);
    else if (digits.startsWith('8')) digits = '62' + digits;
    return digits.length >= 9 ? digits : null;
  }

  // Restore saved buyer profile from localStorage or auth
  function restoreSavedBuyerData() {
    let saved = null;
    try {
      const raw = localStorage.getItem('katalog_buyer_profile');
      if (raw) saved = JSON.parse(raw);
    } catch (e) {}

    const storeCust = window.currentStoreCustomer || null;

    const nameInput = document.getElementById('buyBuyerName');
    const phoneInput = document.getElementById('buyBuyerPhone');
    const addressInput = document.getElementById('buyBuyerAddress');

    if (nameInput) {
      nameInput.value = (storeCust && storeCust.name) ? storeCust.name : ((saved && saved.name) ? saved.name : (authUser.name || ''));
    }
    if (phoneInput) {
      phoneInput.value = (storeCust && storeCust.phone) ? storeCust.phone : ((saved && saved.phone) ? saved.phone : '');
    }
    if (addressInput) {
      addressInput.value = (storeCust && storeCust.address) ? storeCust.address : ((saved && saved.address) ? saved.address : '');
    }
  }

  function saveBuyerProfileToStorage(name, phone, address) {
    try {
      localStorage.setItem('katalog_buyer_profile', JSON.stringify({
        name: name,
        phone: phone,
        address: address,
        saved_at: new Date().toISOString()
      }));
    } catch (e) {}
  }

  window.openProductBuy = function(data) {
    activeBuyProduct = data;
    currentQty = 1;
    selectedProofFile = null;
    isSubmitting = false;

    const modal = document.getElementById('productBuyModal');
    if (!modal) return;

    // Reset views
    document.getElementById('buyModalFormBody').style.display = 'flex';
    document.getElementById('buyModalSuccessBody').style.display = 'none';
    document.getElementById('buyModalFooter').style.display = 'block';
    hideError();

    // Reset proof preview
    resetPaymentProofUI();

    // Set Store Info
    document.getElementById('buyModalStoreName').textContent = data.storeName || 'Toko';

    // Set Product Info
    const imgEl = document.getElementById('buyModalProductImg');
    imgEl.src = data.imageUrl || '';
    imgEl.alt = data.name || 'Produk';
    document.getElementById('buyModalProductName').textContent = data.name || '-';
    document.getElementById('buyModalProductPrice').textContent = data.price || 'Rp 0';

    const stock = Number(data.stock) || 0;
    const qtyInput = document.getElementById('buyModalQty');
    const submitBtn = document.getElementById('buyModalSubmitBtn');
    const submitText = document.getElementById('buyModalSubmitText');

    if (stock <= 0) {
      document.getElementById('buyModalStockHint').innerHTML = '<span style="color:#dc2626;font-weight:700">⛔ Stok Habis — Tidak Dapat Dibayar</span>';
      qtyInput.value = 0;
      qtyInput.disabled = true;
      submitBtn.disabled = true;
      submitBtn.style.opacity = '0.6';
      submitBtn.style.cursor = 'not-allowed';
      submitText.textContent = '⛔ Stok Habis — Tidak Dapat Dibayar';
    } else {
      document.getElementById('buyModalStockHint').textContent = 'Stok tersedia: ' + stock + ' unit';
      qtyInput.value = 1;
      qtyInput.max = stock;
      qtyInput.disabled = false;
      submitBtn.disabled = false;
      submitBtn.style.opacity = '1';
      submitBtn.style.cursor = 'pointer';
      submitText.textContent = 'Kirim Pesanan & Konfirmasi via WA';
    }

    updateBuyTotal();

    // Set QR Section
    const qrContent = document.getElementById('buyModalQrContent');
    const noQrAlert = document.getElementById('buyModalNoQrAlert');

    if (data.storeQrUrl) {
      document.getElementById('buyModalQrImg').src = data.storeQrUrl;
      const accEl = document.getElementById('buyModalQrAccount');
      accEl.textContent = data.storeQrName ? data.storeQrName : (data.storeName + ' QRIS');
      qrContent.style.display = 'block';
      noQrAlert.style.display = 'none';
    } else {
      qrContent.style.display = 'none';
      noQrAlert.style.display = 'block';
    }

    // Prefill buyer profile
    restoreSavedBuyerData();

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  };

  window.closeProductBuy = function() {
    const modal = document.getElementById('productBuyModal');
    if (!modal) return;
    modal.style.display = 'none';
    document.body.style.overflow = '';
  };

  window.adjustBuyQty = function(delta) {
    if (!activeBuyProduct) return;
    const maxStock = Number(activeBuyProduct.stock) || 999;
    const newQty = Math.max(1, Math.min(maxStock, currentQty + delta));
    currentQty = newQty;
    document.getElementById('buyModalQty').value = currentQty;
    updateBuyTotal();
  };

  window.onBuyQtyChanged = function(val) {
    if (!activeBuyProduct) return;
    const maxStock = Number(activeBuyProduct.stock) || 999;
    let n = parseInt(val, 10);
    if (isNaN(n) || n < 1) n = 1;
    if (n > maxStock) n = maxStock;
    currentQty = n;
    document.getElementById('buyModalQty').value = currentQty;
    updateBuyTotal();
  };

  function updateBuyTotal() {
    if (!activeBuyProduct) return;
    const unitPrice = Number(activeBuyProduct.rawPrice) || 0;
    const total = unitPrice * currentQty;
    const formatted = formatRp(total);
    document.getElementById('buyModalTotalPrice').textContent = formatted;
    const copyEl = document.getElementById('buyModalCopyTotal');
    if (copyEl) copyEl.textContent = formatted;
  }

  // Geolocation & Reverse Geocoding
  window.autoDetectAddress = function() {
    const btn = document.getElementById('btnAutoGps');
    const btnText = document.getElementById('btnAutoGpsText');
    const btnIcon = document.getElementById('btnAutoGpsIcon');
    const statusHint = document.getElementById('gpsStatusMessage');
    const addressInput = document.getElementById('buyBuyerAddress');

    if (!navigator.geolocation) {
      alert('Peramban Anda tidak mendukung deteksi lokasi otomatis (GPS). Silakan masukkan alamat secara manual.');
      return;
    }

    btn.disabled = true;
    btnText.textContent = 'Mencari lokasi...';
    btnIcon.textContent = '⏳';
    statusHint.style.display = 'block';
    statusHint.className = 'gps-status-hint info';
    statusHint.textContent = 'Mengakses sensor GPS perangkat...';

    navigator.geolocation.getCurrentPosition(
      function(pos) {
        const lat = pos.coords.latitude;
        const lon = pos.coords.longitude;
        statusHint.textContent = 'Menerjemahkan koordinat ke alamat lengkap...';

        // Call OpenStreetMap Nominatim for Reverse Geocoding
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&addressdetails=1`, {
          headers: { 'Accept-Language': 'id,en' }
        })
        .then(function(res) {
          if (!res.ok) throw new Error('Gagal menghubungi layanan geocoding');
          return res.json();
        })
        .then(function(geo) {
          btn.disabled = false;
          btnText.textContent = 'Perbarui Lokasi';
          btnIcon.textContent = '📍';

          let resolvedAddr = '';
          if (geo && geo.display_name) {
            resolvedAddr = geo.display_name;
          } else if (geo && geo.address) {
            const a = geo.address;
            const parts = [
              a.road || a.pedestrian || a.suburb,
              a.village || a.neighbourhood,
              a.city_district || a.subdistrict,
              a.city || a.town || a.county,
              a.state,
              a.postcode
            ].filter(Boolean);
            resolvedAddr = parts.join(', ');
          }

          if (resolvedAddr) {
            addressInput.value = resolvedAddr;
            statusHint.className = 'gps-status-hint success';
            statusHint.textContent = '✓ Lokasi berhasil terdeteksi otomatis via GPS.';
          } else {
            addressInput.value = `Koordinat: ${lat.toFixed(6)}, ${lon.toFixed(6)} (Mohon lengkapi nama jalan/patokan)`;
            statusHint.className = 'gps-status-hint warning';
            statusHint.textContent = 'Koordinat didapat. Silakan lengkapi detail nama jalan.';
          }
        })
        .catch(function(err) {
          btn.disabled = false;
          btnText.textContent = 'Gunakan Lokasi Saya';
          btnIcon.textContent = '📍';
          addressInput.value = `Koordinat: ${lat.toFixed(6)}, ${lon.toFixed(6)} (Silakan lengkapi nama jalan/patokan)`;
          statusHint.className = 'gps-status-hint success';
          statusHint.textContent = 'Koordinat GPS tersimpan. Silakan tambahkan patokan alamat.';
        });
      },
      function(error) {
        btn.disabled = false;
        btnText.textContent = 'Gunakan Lokasi Saya';
        btnIcon.textContent = '📍';
        statusHint.className = 'gps-status-hint warning';

        if (error.code === error.PERMISSION_DENIED) {
          statusHint.textContent = 'Izin akses lokasi ditolak. Silakan ketik alamat pengiriman secara manual.';
        } else if (error.code === error.POSITION_UNAVAILABLE) {
          statusHint.textContent = 'Sinyal lokasi tidak tersedia saat ini. Silakan ketik alamat manual.';
        } else {
          statusHint.textContent = 'Waktu permintaan lokasi habis. Silakan ketik alamat manual.';
        }
      },
      { enableHighAccuracy: true, timeout: 12000, maximumAge: 60000 }
    );
  };

  // Payment Proof File Handlers
  window.onPaymentProofSelected = function(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];

    // Validate size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
      alert('Ukuran file bukti pembayaran terlalu besar (maksimal 5MB).');
      input.value = '';
      return;
    }

    selectedProofFile = file;

    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('proofPreviewImg').src = e.target.result;
      document.getElementById('proofFileName').textContent = file.name;
      document.getElementById('proofPlaceholder').style.display = 'none';
      document.getElementById('proofPreviewBox').style.display = 'flex';
    };
    reader.readAsDataURL(file);
  };

  window.removePaymentProof = function(e) {
    if (e) {
      e.stopPropagation();
      e.preventDefault();
    }
    resetPaymentProofUI();
  };

  function resetPaymentProofUI() {
    selectedProofFile = null;
    const input = document.getElementById('buyPaymentProofInput');
    if (input) input.value = '';
    const placeholder = document.getElementById('proofPlaceholder');
    const previewBox = document.getElementById('proofPreviewBox');
    if (placeholder) placeholder.style.display = 'block';
    if (previewBox) previewBox.style.display = 'none';
  }

  function showError(msg) {
    const el = document.getElementById('buyErrorMessage');
    if (!el) return;
    el.textContent = msg;
    el.style.display = 'block';
  }

  function hideError() {
    const el = document.getElementById('buyErrorMessage');
    if (el) el.style.display = 'none';
  }

  // Order Submission
  window.submitBuyOrder = function() {
    if (!activeBuyProduct || isSubmitting) return;

    hideError();

    if ((Number(activeBuyProduct.stock) || 0) <= 0) {
      showError('Maaf, stok produk ini sudah habis dan tidak dapat dibayar.');
      return;
    }

    const buyerName = (document.getElementById('buyBuyerName').value || '').trim();
    const buyerPhone = (document.getElementById('buyBuyerPhone').value || '').trim();
    const buyerAddress = (document.getElementById('buyBuyerAddress').value || '').trim();
    const buyerNotes = (document.getElementById('buyBuyerNotes').value || '').trim();

    if (!buyerName) {
      showError('Silakan masukkan nama lengkap pemesan.');
      document.getElementById('buyBuyerName').focus();
      return;
    }

    if (!buyerPhone || buyerPhone.length < 8) {
      showError('Silakan masukkan nomor WhatsApp pemesan yang aktif (minimal 8 digit).');
      document.getElementById('buyBuyerPhone').focus();
      return;
    }

    if (!buyerAddress || buyerAddress.length < 5) {
      showError('Silakan masukkan alamat pengiriman lengkap (atau klik tombol "Gunakan Lokasi Saya").');
      document.getElementById('buyBuyerAddress').focus();
      return;
    }

    const storeSlug = activeBuyProduct.storeSlug;
    if (!storeSlug) {
      showError('Gagal memproses pesanan: Slug toko tidak valid.');
      return;
    }

    // Set UI Submitting State
    isSubmitting = true;
    const submitBtn = document.getElementById('buyModalSubmitBtn');
    const submitText = document.getElementById('buyModalSubmitText');
    const origText = submitText.textContent;
    submitBtn.disabled = true;
    submitBtn.style.opacity = '0.75';
    submitText.textContent = 'Menyimpan Pesanan...';

    const formData = new FormData();
    formData.append('product_id', activeBuyProduct.id);
    formData.append('quantity', currentQty);
    formData.append('buyer_name', buyerName);
    formData.append('buyer_phone', buyerPhone);
    formData.append('buyer_address', buyerAddress);
    if (buyerNotes) {
      formData.append('buyer_notes', buyerNotes);
    }
    if (selectedProofFile) {
      formData.append('payment_proof', selectedProofFile);
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    fetch('/' + encodeURIComponent(storeSlug) + '/orders', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      },
      body: formData
    })
    .then(function(res) {
      return res.json().then(function(data) {
        return { status: res.status, ok: res.ok, data: data };
      });
    })
    .then(function(result) {
      isSubmitting = false;
      submitBtn.disabled = false;
      submitBtn.style.opacity = '1';
      submitText.textContent = origText;

      if (!result.ok || !result.data.success) {
        const errorMsg = (result.data && result.data.message) ? result.data.message : 'Terjadi kendala saat menyimpan pesanan. Silakan periksa kembali formulir.';
        showError(errorMsg);
        return;
      }

      // Save buyer profile for future orders
      saveBuyerProfileToStorage(buyerName, buyerPhone, buyerAddress);

      // Render Success View
      const data = result.data;
      document.getElementById('successOrderCode').textContent = data.order_code || '-';
      document.getElementById('successTotalPrice').textContent = data.total_price_formatted || formatRp(activeBuyProduct.rawPrice * currentQty);
      
      const proofStatusEl = document.getElementById('successProofStatus');
      if (data.has_proof) {
        proofStatusEl.textContent = '✓ Bukti Terunggah';
        proofStatusEl.className = 'proof-badge-success';
      } else {
        proofStatusEl.textContent = 'Menunggu Konfirmasi WA';
        proofStatusEl.className = 'proof-badge-pending';
      }

      const waBtn = document.getElementById('btnProceedWhatsApp');
      if (data.whatsapp_url) {
        waBtn.href = data.whatsapp_url;
        waBtn.style.display = 'inline-flex';
        // Auto open WhatsApp in new tab
        window.open(data.whatsapp_url, '_blank');
      } else {
        waBtn.style.display = 'none';
      }

      // Toggle views
      document.getElementById('buyModalFormBody').style.display = 'none';
      document.getElementById('buyModalFooter').style.display = 'none';
      document.getElementById('buyModalSuccessBody').style.display = 'flex';
    })
    .catch(function(err) {
      isSubmitting = false;
      submitBtn.disabled = false;
      submitBtn.style.opacity = '1';
      submitText.textContent = origText;
      showError('Terjadi kesalahan jaringan saat memproses pesanan. Pastikan koneksi internet Anda stabil.');
    });
  };

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeProductBuy();
    }
  });
})();
</script>
