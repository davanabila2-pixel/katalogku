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

    {{-- Body --}}
    <div class="product-buy-body">
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
              <li>Simpan / tangkap layar (*screenshot*) bukti transfer untuk dilampirkan ke toko.</li>
            </ol>
          </div>
        </div>

        {{-- Jika toko belum upload QR --}}
        <div id="buyModalNoQrAlert" class="buy-no-qr-alert" style="display:none">
          <div style="font-size:18px;margin-bottom:4px">ℹ️ QR Statis Belum Tersedia</div>
          <div>Toko ini belum mengunggah gambar QRIS pembayaran. Anda tetap dapat melanjutkan pesanan langsung via WhatsApp untuk meminta rekening transfer resmi toko.</div>
        </div>
      </div>

      {{-- 3. Data Pemesan --}}
      <div class="buy-section-card">
        <div class="buy-card-heading">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span>Data Pemesan</span>
        </div>
        <div class="buy-field">
          <label for="buyBuyerName">Nama Lengkap <span style="color:var(--critical)">*</span></label>
          <input type="text" id="buyBuyerName" class="buy-input" placeholder="cth: Budi Santoso" required>
        </div>
        <div class="buy-field">
          <label for="buyBuyerPhone">No. WhatsApp Pembeli <span style="color:var(--critical)">*</span></label>
          <input type="tel" id="buyBuyerPhone" class="buy-input" placeholder="cth: 081234567890" required>
        </div>
        <div class="buy-field" style="margin-bottom:0">
          <label for="buyBuyerNotes">Alamat Pengiriman / Catatan Pesanan</label>
          <textarea id="buyBuyerNotes" class="buy-textarea" rows="2" placeholder="Alamat lengkap (nama jalan, kota, kodepos) atau catatan ukuran/warna..."></textarea>
        </div>
      </div>
    </div>

    {{-- Footer --}}
    <div class="product-buy-footer">
      <button type="button" id="buyModalSubmitBtn" class="btn-buy-confirm-wa" onclick="submitBuyOrder()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
        <span>Konfirmasi Pesanan ke WhatsApp</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </button>
    </div>
  </div>
</div>

<script>
(function() {
  let activeBuyProduct = null;
  let currentQty = 1;

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

  window.openProductBuy = function(data) {
    activeBuyProduct = data;
    currentQty = 1;

    const modal = document.getElementById('productBuyModal');
    if (!modal) return;

    // Set Store Info
    document.getElementById('buyModalStoreName').textContent = data.storeName || 'Toko';

    // Set Product Info
    const imgEl = document.getElementById('buyModalProductImg');
    imgEl.src = data.imageUrl || '';
    imgEl.alt = data.name || 'Produk';
    document.getElementById('buyModalProductName').textContent = data.name || '-';
    document.getElementById('buyModalProductPrice').textContent = data.price || 'Rp 0';

    const stock = Number(data.stock) || 1;
    document.getElementById('buyModalStockHint').textContent = 'Stok tersedia: ' + stock + ' unit';

    const qtyInput = document.getElementById('buyModalQty');
    qtyInput.value = 1;
    qtyInput.max = stock;

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

  window.submitBuyOrder = function() {
    if (!activeBuyProduct) return;

    const buyerName = (document.getElementById('buyBuyerName').value || '').trim();
    const buyerPhone = (document.getElementById('buyBuyerPhone').value || '').trim();
    const buyerNotes = (document.getElementById('buyBuyerNotes').value || '').trim();

    if (!buyerName) {
      alert('Silakan masukkan nama lengkap pemesan.');
      document.getElementById('buyBuyerName').focus();
      return;
    }

    if (!buyerPhone || buyerPhone.length < 8) {
      alert('Silakan masukkan nomor WhatsApp pemesan yang aktif.');
      document.getElementById('buyBuyerPhone').focus();
      return;
    }

    const storePhone = cleanPhone(activeBuyProduct.storePhone);
    if (!storePhone) {
      alert('Maaf, nomor WhatsApp toko belum tersedia.');
      return;
    }

    const unitPrice = Number(activeBuyProduct.rawPrice) || 0;
    const total = unitPrice * currentQty;
    const totalFormatted = formatRp(total);
    const hasQr = !!activeBuyProduct.storeQrUrl;

    const msg =
      `Halo ${activeBuyProduct.storeName || 'Toko'}, saya ingin melakukan pemesanan:\n\n` +
      `🛍️ *PESANAN BARU*\n` +
      `📦 *Produk*: ${activeBuyProduct.name}\n` +
      `🔢 *Jumlah*: ${currentQty} unit\n` +
      `💰 *Harga Satuan*: ${activeBuyProduct.price}\n` +
      `💵 *Total Pembayaran*: ${totalFormatted}\n` +
      `💳 *Metode*: ${hasQr ? 'QRIS / QR Statis Toko' : 'Transfer Bank / Konfirmasi Toko'}\n` +
      `🔗 *Link*: ${activeBuyProduct.url || window.location.href}\n\n` +
      `📋 *DATA PEMBELI*\n` +
      `👤 *Nama*: ${buyerName}\n` +
      `📱 *No. WA*: ${buyerPhone}\n` +
      `📍 *Alamat / Catatan*: ${buyerNotes || '-'}\n\n` +
      (hasQr
        ? `Saya sudah menyiapkan / menyelesaikan pembayaran via QRIS toko. Bukti transfer akan saya lampirkan di chat ini. Mohon segera diproses ya kak. Terima kasih!`
        : `Mohon info instruksi pembayaran / nomor rekening untuk memproses pesanan ini. Terima kasih!`);

    const waUrl = `https://wa.me/${storePhone}?text=${encodeURIComponent(msg)}`;
    window.open(waUrl, '_blank');
    closeProductBuy();
  };

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeProductBuy();
    }
  });
})();
</script>

