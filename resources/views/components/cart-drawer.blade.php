{{-- Drawer & Checkout Keranjang Pintar --}}
<div id="cartDrawerOverlay" class="cart-drawer-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="cartDrawerTitle">
  <div class="cart-drawer-backdrop" onclick="closeCartDrawer()"></div>
  <aside class="cart-drawer-panel">
    {{-- Header --}}
    <div class="cart-drawer-header">
      <div class="d-flex align-items-center gap-2">
        <div class="cart-drawer-icon-wrap">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
          </svg>
        </div>
        <div>
          <h3 class="cart-drawer-title" id="cartDrawerTitle">Keranjang Pintar</h3>
          <div class="cart-drawer-subtitle" id="cartDrawerItemCount">0 barang tersimpan</div>
        </div>
      </div>
      <button type="button" class="cart-drawer-close" onclick="closeCartDrawer()" aria-label="Tutup keranjang">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    {{-- Body --}}
    <div class="cart-drawer-body" id="cartDrawerBody">
      {{-- Loading State --}}
      <div id="cartLoadingState" class="cart-empty-state" style="display:none;">
        <div class="cart-spinner"></div>
        <p style="margin-top:12px;font-size:13px;color:var(--steel)">Memuat keranjang belanja...</p>
      </div>

      {{-- Empty State --}}
      <div id="cartEmptyState" class="cart-empty-state" style="display:none;">
        <div class="cart-empty-icon">🛒</div>
        <h4 style="font-weight:700;color:var(--ink-deep);margin:0 0 6px">Keranjang Masih Kosong</h4>
        <p style="font-size:13px;color:var(--steel);margin:0 0 16px;max-width:240px;line-height:1.4">Jelajahi produk pilihan di katalog dan tambahkan ke keranjang belanja Anda.</p>
        <button type="button" class="btn btn-primary btn-sm" onclick="closeCartDrawer()">Mulai Belanja</button>
      </div>

      {{-- Populated Store Groups Container --}}
      <div id="cartStoresContainer" class="cart-stores-list"></div>
    </div>

    {{-- Footer Summary --}}
    <div class="cart-drawer-footer" id="cartDrawerFooter" style="display:none;">
      <div class="cart-grand-total-row">
        <span>Total Semua Toko:</span>
        <strong id="cartGrandTotalText" class="cart-grand-total-amount">Rp 0</strong>
      </div>
      <div class="cart-footer-hint">Pembayaran dilakukan terpisah per masing-masing toko sesuai instruksi QRIS / transfer toko.</div>
    </div>
  </aside>
</div>

{{-- Modal Checkout Keranjang per Toko --}}
<div id="cartCheckoutModal" class="product-chat-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="cartCheckoutModalTitle">
  <div class="product-chat-backdrop" onclick="closeStoreCartCheckout()"></div>
  <div class="product-buy-dialog">
    {{-- Header --}}
    <div class="product-buy-header">
      <div>
        <div class="product-buy-title" id="cartCheckoutModalTitle">Checkout Pesanan Keranjang</div>
        <div class="product-buy-store" id="cartCheckoutStoreName">Toko</div>
      </div>
      <button type="button" class="product-chat-close-btn" onclick="closeStoreCartCheckout()" aria-label="Tutup checkout">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    {{-- Form Body --}}
    <div class="product-buy-body" id="cartCheckoutFormBody">
      {{-- 1. Ringkasan Barang Toko Ini --}}
      <div class="buy-section-card">
        <div class="buy-card-heading">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          <span>Daftar Barang yang Dipesan</span>
        </div>
        <div id="cartCheckoutItemsList" class="checkout-items-mini-list"></div>
        <div class="buy-total-banner">
          <span>Total Pembayaran Toko:</span>
          <strong id="cartCheckoutTotalPrice" class="buy-total-amount">Rp 0</strong>
        </div>
      </div>

      {{-- 2. Area QR Pembayaran Toko --}}
      <div class="buy-section-card buy-qr-card">
        <div class="buy-card-heading">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M10 7H7v3M17 7h-3v3M10 17H7v-3M17 17h-3v-3"/></svg>
          <span>Pindai QRIS / QR Pembayaran Toko</span>
        </div>

        <div id="cartCheckoutQrContent" style="display:none;text-align:center">
          <div class="qr-image-frame">
            <img id="cartCheckoutQrImg" src="" alt="QR Pembayaran Toko" class="qr-code-img">
          </div>
          <div id="cartCheckoutQrAccount" class="qr-account-badge">QRIS Toko</div>
          <div class="qr-instruction-steps">
            <ol>
              <li>Buka aplikasi m-Banking atau E-Wallet (BCA, Mandiri, BRI, GoPay, OVO, ShopeePay, Dana, dll).</li>
              <li>Scan kode QR di atas dan bayar pas sesuai total: <strong id="cartCheckoutCopyTotal" style="color:#0f172a">Rp 0</strong></li>
              <li>Screenshot bukti pembayaran dan lampirkan di form bawah.</li>
            </ol>
          </div>
        </div>

        <div id="cartCheckoutNoQrAlert" class="buy-no-qr-alert" style="display:none">
          <div style="font-weight:700;font-size:13.5px;margin-bottom:4px">ℹ️ QRIS Toko Belum Diunggah</div>
          <div>Toko ini belum mengunggah gambar QRIS. Pesanan Anda tetap akan tercatat di sistem dan diteruskan ke WhatsApp toko untuk instruksi transfer.</div>
        </div>
      </div>

      {{-- 3. Data Pemesan & Alamat Otomatis --}}
      <div class="buy-section-card">
        <div class="buy-card-heading">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span>Data Pemesan & Pengiriman</span>
        </div>

        <div class="buy-field">
          <label for="cartBuyerName">Nama Lengkap <span style="color:var(--critical)">*</span></label>
          <input type="text" id="cartBuyerName" class="buy-input" placeholder="cth: Budi Santoso" required>
        </div>

        <div class="buy-field">
          <label for="cartBuyerPhone">No. WhatsApp Pembeli <span style="color:var(--critical)">*</span></label>
          <input type="tel" id="cartBuyerPhone" class="buy-input" placeholder="cth: 081234567890" required>
        </div>

        <div class="buy-field">
          <div class="buy-label-row">
            <label for="cartBuyerAddress">Alamat Pengiriman Lengkap <span style="color:var(--critical)">*</span></label>
            <button type="button" id="btnCartAutoGps" class="btn-auto-gps" onclick="autoDetectCartAddress()" title="Ambil alamat otomatis menggunakan GPS perangkat">
              <span id="btnCartAutoGpsIcon">📍</span>
              <span id="btnCartAutoGpsText">Gunakan Lokasi Saya</span>
            </button>
          </div>
          <textarea id="cartBuyerAddress" class="buy-textarea" rows="2" placeholder="Nama jalan, RT/RW, kelurahan, kecamatan, kota/kabupaten, kode pos..." required></textarea>
          <div id="cartGpsStatusMessage" class="gps-status-hint" style="display:none;"></div>
        </div>

        <div class="buy-field" style="margin-bottom:0">
          <label for="cartBuyerNotes">Catatan Tambahan (Opsional)</label>
          <input type="text" id="cartBuyerNotes" class="buy-input" placeholder="cth: Titipkan di pos satpam, bungkus kado...">
        </div>
      </div>

      {{-- 4. Unggah Bukti Pembayaran --}}
      <div class="buy-section-card">
        <div class="buy-card-heading">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <span>Unggah Bukti Transfer / QRIS</span>
          <span class="badge-optional">Disarankan</span>
        </div>

        <div class="proof-upload-area" onclick="document.getElementById('cartPaymentProofInput').click()">
          <input type="file" id="cartPaymentProofInput" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="onCartPaymentProofSelected(this)">
          
          <div id="cartProofPlaceholder" class="proof-placeholder">
            <div class="proof-icon-circle">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </div>
            <div class="proof-text-primary">Sentuh untuk pilih foto bukti transfer / QRIS</div>
            <div class="proof-text-muted">JPG, PNG, atau WebP (Maks. 5MB)</div>
          </div>

          <div id="cartProofPreviewBox" class="proof-preview-box" style="display:none;">
            <img id="cartProofPreviewImg" src="" alt="Bukti Pembayaran" class="proof-thumb">
            <div class="proof-info">
              <div id="cartProofFileName" class="proof-filename">bukti-transfer.jpg</div>
              <div class="proof-status-tag">✓ Siap Dilampirkan</div>
              <button type="button" class="btn-remove-proof" onclick="removeCartPaymentProof(event)">
                ✕ Hapus Bukti
              </button>
            </div>
          </div>
        </div>
      </div>

      {{-- Alert Error --}}
      <div id="cartCheckoutErrorMessage" class="buy-error-banner" style="display:none;"></div>
    </div>

    {{-- Success State --}}
    <div class="product-buy-body buy-success-body" id="cartCheckoutSuccessBody" style="display:none;">
      <div class="buy-success-card">
        <div class="success-icon-badge">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <h3 class="success-heading">Pesanan Keranjang Berhasil Dicatat!</h3>
        <p class="success-desc">Data pesanan Anda telah tersimpan di sistem toko. Lanjutkan ke WhatsApp toko untuk konfirmasi pemrosesan.</p>

        <div class="order-code-box">
          <div class="order-code-label">Nomor Pesanan:</div>
          <div class="order-code-value" id="cartSuccessOrderCode">-</div>
        </div>

        <div class="success-summary-box">
          <div class="summary-line">
            <span>Total Pembayaran:</span>
            <strong id="cartSuccessTotalPrice">Rp 0</strong>
          </div>
          <div class="summary-line">
            <span>Status Bukti:</span>
            <span id="cartSuccessProofStatus" class="proof-badge-success">Terunggah</span>
          </div>
        </div>

        <a id="btnCartProceedWhatsApp" href="#" target="_blank" rel="noopener" class="btn-buy-confirm-wa" style="text-decoration:none;margin-top:14px;width:100%;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
          <span>Buka Chat WhatsApp Toko</span>
        </a>

        <button type="button" class="btn-secondary-close" onclick="closeStoreCartCheckout()">
          Tutup & Kembali
        </button>
      </div>
    </div>

    {{-- Footer --}}
    <div class="product-buy-footer" id="cartCheckoutFooter">
      <button type="button" id="cartCheckoutSubmitBtn" class="btn-buy-confirm-wa" onclick="submitStoreCartCheckout()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
        <span id="cartCheckoutSubmitText">Kirim Pesanan Toko Ini & Konfirmasi WA</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </button>
    </div>
  </div>
</div>

{{-- Toast Feedback Notification --}}
<div id="cartToastNotification" class="cart-toast" style="display:none;">
  <span class="cart-toast-icon">✓</span>
  <span class="cart-toast-text" id="cartToastText">Produk ditambahkan ke keranjang!</span>
</div>

{{-- Floating Cart Button --}}
<button type="button" class="floating-cart-btn" id="floatingCartBtn" onclick="openCartDrawer()" aria-label="Buka Keranjang Belanja" style="display:none;">
  <span class="floating-cart-icon">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
    </svg>
    <span class="floating-cart-badge" data-cart-count>0</span>
  </span>
  <span class="floating-cart-text">Keranjang</span>
</button>

<script>
(function() {
  let cachedCart = null;
  let activeCheckoutStore = null;
  let selectedCartProofFile = null;
  let isCartSubmitting = false;

  const authUser = {
    name: @js(auth()->check() ? auth()->user()->name : ''),
  };

  function formatRp(num) {
    return 'Rp ' + Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  }

  function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }

  // Toast
  function showCartToast(message) {
    const toast = document.getElementById('cartToastNotification');
    const toastText = document.getElementById('cartToastText');
    if (!toast || !toastText) return;

    toastText.textContent = message;
    toast.style.display = 'inline-flex';
    toast.classList.add('show');

    setTimeout(function() {
      toast.classList.remove('show');
      setTimeout(function() {
        toast.style.display = 'none';
      }, 250);
    }, 2800);
  }

  // Update Cart Badges across Topbar and Floating button
  function updateCartBadges(count) {
    const totalCount = parseInt(count, 10) || 0;
    const badgeEls = document.querySelectorAll('[data-cart-count]');
    badgeEls.forEach(function(el) {
      el.textContent = totalCount;
      el.style.display = totalCount > 0 ? 'inline-flex' : 'none';
    });

    const floatingBtn = document.getElementById('floatingCartBtn');
    if (floatingBtn) {
      floatingBtn.style.display = totalCount > 0 ? 'inline-flex' : 'none';
    }

    const countHint = document.getElementById('cartDrawerItemCount');
    if (countHint) {
      countHint.textContent = totalCount + ' barang tersimpan';
    }
  }

  // Fetch Cart Data
  window.loadCartData = function(callback) {
    let url = '/cart';
    if (window.currentStoreId) {
      url += '?store_id=' + encodeURIComponent(window.currentStoreId);
    }
    fetch(url, {
      headers: {
        'Accept': 'application/json'
      }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
      if (data && data.success && data.cart) {
        cachedCart = data.cart;
        updateCartBadges(cachedCart.total_quantity || 0);
        renderCartStores(cachedCart);
        if (typeof callback === 'function') callback(cachedCart);
      }
    })
    .catch(function(err) {
      console.warn('Gagal memuat keranjang:', err);
    });
  };

  // Add To Cart
  window.addToCart = function(productId, qty) {
    qty = qty || 1;
    const btn = event ? event.currentTarget : null;
    if (btn) btn.disabled = true;

    const payload = {
      product_id: productId,
      quantity: qty
    };
    if (window.currentStoreId) {
      payload.store_id = window.currentStoreId;
    }

    fetch('/cart/add', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    })
    .then(function(res) { return res.json().then(function(d) { return { status: res.status, data: d }; }); })
    .then(function(result) {
      if (btn) btn.disabled = false;
      if (result.status === 200 && result.data.success) {
        cachedCart = result.data.cart;
        updateCartBadges(cachedCart.total_quantity || 0);
        renderCartStores(cachedCart);
        showCartToast(result.data.message || 'Produk ditambahkan ke keranjang!');
      } else {
        alert(result.data.message || 'Gagal menambahkan produk ke keranjang.');
      }
    })
    .catch(function(err) {
      if (btn) btn.disabled = false;
      alert('Terjadi kendala jaringan saat menambahkan ke keranjang.');
    });
  };

  // Update Cart Item Quantity
  window.updateCartQty = function(cartItemId, newQty) {
    let url = '/cart/items/' + cartItemId;
    if (window.currentStoreId) {
      url += '?store_id=' + encodeURIComponent(window.currentStoreId);
    }
    fetch(url, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        quantity: newQty,
        store_id: window.currentStoreId || undefined
      })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
      if (data && data.success && data.cart) {
        cachedCart = data.cart;
        updateCartBadges(cachedCart.total_quantity || 0);
        renderCartStores(cachedCart);
      }
    });
  };

  // Remove Cart Item
  window.removeCartItem = function(cartItemId) {
    if (!confirm('Hapus produk ini dari keranjang?')) return;

    let url = '/cart/items/' + cartItemId;
    if (window.currentStoreId) {
      url += '?store_id=' + encodeURIComponent(window.currentStoreId);
    }
    fetch(url, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json'
      }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
      if (data && data.success && data.cart) {
        cachedCart = data.cart;
        updateCartBadges(cachedCart.total_quantity || 0);
        renderCartStores(cachedCart);
        showCartToast('Produk dihapus dari keranjang.');
      }
    });
  };

  // Render Cart Stores & Items
  function renderCartStores(cart) {
    const container = document.getElementById('cartStoresContainer');
    const emptyState = document.getElementById('cartEmptyState');
    const footer = document.getElementById('cartDrawerFooter');
    const grandTotalText = document.getElementById('cartGrandTotalText');

    if (!container) return;

    if (!cart || !cart.stores || cart.stores.length === 0) {
      container.innerHTML = '';
      if (emptyState) emptyState.style.display = 'flex';
      if (footer) footer.style.display = 'none';
      return;
    }

    if (emptyState) emptyState.style.display = 'none';
    if (footer) footer.style.display = 'block';
    if (grandTotalText) grandTotalText.textContent = cart.grand_total_formatted;

    let html = '';

    cart.stores.forEach(function(store) {
      const storeJson = JSON.stringify(store).replace(/"/g, '&quot;');
      const canPay = store.can_checkout;

      html += `
        <div class="cart-store-card">
          <div class="cart-store-card-header">
            <div class="d-flex align-items-center gap-2">
              <span class="cart-store-badge">🏪 Toko</span>
              <a href="/${store.store_slug}" class="cart-store-name-link" target="_blank">${escapeHtml(store.store_name)}</a>
            </div>
            ${store.out_of_stock_count > 0 ? `<span class="badge-stock-danger">${store.out_of_stock_count} barang habis</span>` : ''}
          </div>

          <div class="cart-store-items">
      `;

      store.items.forEach(function(item) {
        const isOutOfStock = item.is_out_of_stock;
        const exceedsStock = item.exceeds_stock;

        html += `
          <div class="cart-item-row ${isOutOfStock ? 'is-out-of-stock' : ''}">
            <img src="${item.image_url || ''}" alt="${escapeHtml(item.name)}" class="cart-item-thumb">
            <div class="cart-item-info">
              <a href="${item.product_url}" class="cart-item-name" target="_blank">${escapeHtml(item.name)}</a>
              <div class="cart-item-price">${item.price_formatted}</div>

              ${isOutOfStock ? `
                <div class="stock-alert-pill out-of-stock">
                  <span>⛔ Stok Habis — Tidak Dapat Dibayar</span>
                </div>
              ` : (exceedsStock ? `
                <div class="stock-alert-pill exceeds-stock">
                  <span>⚠️ Sisa stok: ${item.stock} unit</span>
                </div>
              ` : `
                <div class="cart-item-stock-hint">Stok tersedia: ${item.stock} unit</div>
              `)}

              <div class="cart-item-controls">
                <div class="cart-stepper">
                  <button type="button" class="cart-stepper-btn" onclick="updateCartQty(${item.id}, ${item.quantity - 1})" aria-label="Kurangi jumlah">−</button>
                  <span class="cart-stepper-val">${item.quantity}</span>
                  <button type="button" class="cart-stepper-btn" onclick="updateCartQty(${item.id}, ${item.quantity + 1})" ${item.quantity >= item.stock ? 'disabled' : ''} aria-label="Tambah jumlah">+</button>
                </div>
                <div class="cart-item-subtotal">${item.subtotal_formatted}</div>
                <button type="button" class="btn-cart-remove" onclick="removeCartItem(${item.id})" title="Hapus produk">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6"/></svg>
                </button>
              </div>
            </div>
          </div>
        `;
      });

      html += `
          </div>

          <div class="cart-store-card-footer">
            <div class="cart-store-subtotal-row">
              <span class="cart-subtotal-label">Subtotal Toko:</span>
              <strong class="cart-subtotal-val">${store.store_subtotal_formatted}</strong>
            </div>

            ${canPay ? `
              <button type="button" class="btn-checkout-store" onclick="openStoreCartCheckout(${storeJson})">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <span>Bayar Pesanan ${escapeHtml(store.store_name)}</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
              </button>
            ` : `
              <div class="store-cant-checkout-alert">
                <button type="button" class="btn-checkout-store disabled" disabled>
                  <span>⛔ Stok Habis — Tidak Dapat Dibayar</span>
                </button>
                <div class="store-cant-checkout-hint">
                  Hapus atau sesuaikan produk yang habis di atas untuk melanjutkan pembayaran toko ini.
                </div>
              </div>
            `}
          </div>
        </div>
      `;
    });

    container.innerHTML = html;
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // Open / Close Drawer
  window.openCartDrawer = function() {
    const overlay = document.getElementById('cartDrawerOverlay');
    if (!overlay) return;
    overlay.style.display = 'block';
    document.body.style.overflow = 'hidden';
    loadCartData();
  };

  window.closeCartDrawer = function() {
    const overlay = document.getElementById('cartDrawerOverlay');
    if (!overlay) return;
    overlay.style.display = 'none';
    document.body.style.overflow = '';
  };

  // Restore buyer profile
  function restoreCartBuyerProfile(store) {
    let saved = null;
    try {
      const raw = localStorage.getItem('katalog_buyer_profile');
      if (raw) saved = JSON.parse(raw);
    } catch (e) {}

    const storeCust = (store && store.customer_profile) ? store.customer_profile : (window.currentStoreCustomer || null);

    const nameInput = document.getElementById('cartBuyerName');
    const phoneInput = document.getElementById('cartBuyerPhone');
    const addrInput = document.getElementById('cartBuyerAddress');
    const notesInput = document.getElementById('cartBuyerNotes');

    if (nameInput) {
      nameInput.value = (storeCust && storeCust.name) ? storeCust.name : ((saved && saved.name) ? saved.name : (authUser.name || ''));
    }
    if (phoneInput) {
      phoneInput.value = (storeCust && storeCust.phone) ? storeCust.phone : ((saved && saved.phone) ? saved.phone : '');
    }
    if (addrInput) {
      addrInput.value = (storeCust && storeCust.address) ? storeCust.address : ((saved && saved.address) ? saved.address : '');
    }
    if (notesInput && storeCust && storeCust.notes && !notesInput.value) {
      notesInput.value = storeCust.notes;
    }
  }

  // Store Cart Checkout
  window.openStoreCartCheckout = function(store) {
    activeCheckoutStore = store;
    selectedCartProofFile = null;
    isCartSubmitting = false;

    // Tutup drawer
    closeCartDrawer();

    const modal = document.getElementById('cartCheckoutModal');
    if (!modal) return;

    // Reset views
    document.getElementById('cartCheckoutFormBody').style.display = 'flex';
    document.getElementById('cartCheckoutSuccessBody').style.display = 'none';
    document.getElementById('cartCheckoutFooter').style.display = 'block';
    hideCartCheckoutError();
    resetCartProofUI();

    // Set Store Info
    document.getElementById('cartCheckoutStoreName').textContent = store.store_name;
    document.getElementById('cartCheckoutTotalPrice').textContent = store.store_subtotal_formatted;

    // Render Mini Items List
    const itemsContainer = document.getElementById('cartCheckoutItemsList');
    let itemsHtml = '';
    store.items.forEach(function(item) {
      itemsHtml += `
        <div class="checkout-item-mini-row">
          <div class="d-flex align-items-center gap-2">
            <img src="${item.image_url || ''}" alt="${escapeHtml(item.name)}" class="checkout-mini-thumb">
            <div>
              <div class="checkout-mini-name">${escapeHtml(item.name)}</div>
              <div class="checkout-mini-qty">${item.quantity} unit × ${item.price_formatted}</div>
            </div>
          </div>
          <strong class="checkout-mini-subtotal">${item.subtotal_formatted}</strong>
        </div>
      `;
    });
    itemsContainer.innerHTML = itemsHtml;

    // Set QR Info
    const qrContent = document.getElementById('cartCheckoutQrContent');
    const noQrAlert = document.getElementById('cartCheckoutNoQrAlert');

    if (store.store_qr_url) {
      document.getElementById('cartCheckoutQrImg').src = store.store_qr_url;
      document.getElementById('cartCheckoutQrAccount').textContent = store.store_qr_name ? store.store_qr_name : (store.store_name + ' QRIS');
      document.getElementById('cartCheckoutCopyTotal').textContent = store.store_subtotal_formatted;
      qrContent.style.display = 'block';
      noQrAlert.style.display = 'none';
    } else {
      qrContent.style.display = 'none';
      noQrAlert.style.display = 'block';
    }

    // Prefill buyer profile
    restoreCartBuyerProfile(store);

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  };

  window.closeStoreCartCheckout = function() {
    const modal = document.getElementById('cartCheckoutModal');
    if (!modal) return;
    modal.style.display = 'none';
    document.body.style.overflow = '';
  };

  // Cart Geolocation
  window.autoDetectCartAddress = function() {
    const btn = document.getElementById('btnCartAutoGps');
    const btnText = document.getElementById('btnCartAutoGpsText');
    const btnIcon = document.getElementById('btnCartAutoGpsIcon');
    const statusHint = document.getElementById('cartGpsStatusMessage');
    const addressInput = document.getElementById('cartBuyerAddress');

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

        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&addressdetails=1`, {
          headers: { 'Accept-Language': 'id,en' }
        })
        .then(function(res) {
          if (!res.ok) throw new Error('Gagal memuat alamat');
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
            addressInput.value = `Koordinat: ${lat.toFixed(6)}, ${lon.toFixed(6)} (Mohon lengkapi detail jalan)`;
            statusHint.className = 'gps-status-hint warning';
            statusHint.textContent = 'Koordinat didapat. Silakan lengkapi detail nama jalan.';
          }
        })
        .catch(function() {
          btn.disabled = false;
          btnText.textContent = 'Gunakan Lokasi Saya';
          btnIcon.textContent = '📍';
          addressInput.value = `Koordinat: ${lat.toFixed(6)}, ${lon.toFixed(6)}`;
          statusHint.className = 'gps-status-hint success';
          statusHint.textContent = 'Koordinat GPS tersimpan. Silakan tambahkan patokan alamat.';
        });
      },
      function(error) {
        btn.disabled = false;
        btnText.textContent = 'Gunakan Lokasi Saya';
        btnIcon.textContent = '📍';
        statusHint.className = 'gps-status-hint warning';
        statusHint.textContent = 'Gagal mengakses GPS. Silakan ketik alamat secara manual.';
      },
      { enableHighAccuracy: true, timeout: 12000, maximumAge: 60000 }
    );
  };

  // Cart Payment Proof handlers
  window.onCartPaymentProofSelected = function(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];

    if (file.size > 5 * 1024 * 1024) {
      alert('Ukuran file bukti pembayaran terlalu besar (maksimal 5MB).');
      input.value = '';
      return;
    }

    selectedCartProofFile = file;

    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('cartProofPreviewImg').src = e.target.result;
      document.getElementById('cartProofFileName').textContent = file.name;
      document.getElementById('cartProofPlaceholder').style.display = 'none';
      document.getElementById('cartProofPreviewBox').style.display = 'flex';
    };
    reader.readAsDataURL(file);
  };

  window.removeCartPaymentProof = function(e) {
    if (e) {
      e.stopPropagation();
      e.preventDefault();
    }
    resetCartProofUI();
  };

  function resetCartProofUI() {
    selectedCartProofFile = null;
    const input = document.getElementById('cartPaymentProofInput');
    if (input) input.value = '';
    const placeholder = document.getElementById('cartProofPlaceholder');
    const previewBox = document.getElementById('cartProofPreviewBox');
    if (placeholder) placeholder.style.display = 'block';
    if (previewBox) previewBox.style.display = 'none';
  }

  function showCartCheckoutError(msg) {
    const el = document.getElementById('cartCheckoutErrorMessage');
    if (!el) return;
    el.textContent = msg;
    el.style.display = 'block';
  }

  function hideCartCheckoutError() {
    const el = document.getElementById('cartCheckoutErrorMessage');
    if (el) el.style.display = 'none';
  }

  // Submit Store Cart Checkout
  window.submitStoreCartCheckout = function() {
    if (!activeCheckoutStore || isCartSubmitting) return;

    hideCartCheckoutError();

    const buyerName = (document.getElementById('cartBuyerName').value || '').trim();
    const buyerPhone = (document.getElementById('cartBuyerPhone').value || '').trim();
    const buyerAddress = (document.getElementById('cartBuyerAddress').value || '').trim();
    const buyerNotes = (document.getElementById('cartBuyerNotes').value || '').trim();

    if (!buyerName) {
      showCartCheckoutError('Silakan masukkan nama lengkap pemesan.');
      document.getElementById('cartBuyerName').focus();
      return;
    }

    if (!buyerPhone || buyerPhone.length < 8) {
      showCartCheckoutError('Silakan masukkan nomor WhatsApp pemesan yang aktif.');
      document.getElementById('cartBuyerPhone').focus();
      return;
    }

    if (!buyerAddress || buyerAddress.length < 5) {
      showCartCheckoutError('Silakan masukkan alamat pengiriman lengkap (atau klik "Gunakan Lokasi Saya").');
      document.getElementById('cartBuyerAddress').focus();
      return;
    }

    const storeSlug = activeCheckoutStore.store_slug;
    if (!storeSlug) {
      showCartCheckoutError('Gagal memproses pesanan: Slug toko tidak valid.');
      return;
    }

    isCartSubmitting = true;
    const submitBtn = document.getElementById('cartCheckoutSubmitBtn');
    const submitText = document.getElementById('cartCheckoutSubmitText');
    const origText = submitText.textContent;
    submitBtn.disabled = true;
    submitBtn.style.opacity = '0.75';
    submitText.textContent = 'Menyimpan Pesanan...';

    const formData = new FormData();
    formData.append('buyer_name', buyerName);
    formData.append('buyer_phone', buyerPhone);
    formData.append('buyer_address', buyerAddress);
    if (buyerNotes) formData.append('buyer_notes', buyerNotes);
    if (selectedCartProofFile) formData.append('payment_proof', selectedCartProofFile);
    if (window.currentStoreId) formData.append('store_id', window.currentStoreId);

    fetch('/' + encodeURIComponent(storeSlug) + '/cart/checkout', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': getCsrfToken(),
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
      isCartSubmitting = false;
      submitBtn.disabled = false;
      submitBtn.style.opacity = '1';
      submitText.textContent = origText;

      if (!result.ok || !result.data.success) {
        const errorMsg = (result.data && result.data.message) ? result.data.message : 'Terjadi kendala saat memproses pesanan keranjang.';
        showCartCheckoutError(errorMsg);
        return;
      }

      // Simpan profile pembeli
      try {
        localStorage.setItem('katalog_buyer_profile', JSON.stringify({
          name: buyerName,
          phone: buyerPhone,
          address: buyerAddress,
          saved_at: new Date().toISOString()
        }));
      } catch (e) {}

      // Perbarui status keranjang lokal
      if (result.data.cart) {
        cachedCart = result.data.cart;
        updateCartBadges(cachedCart.total_quantity || 0);
        renderCartStores(cachedCart);
      }

      // Tampilkan layar sukses
      const data = result.data;
      document.getElementById('cartSuccessOrderCode').textContent = data.order_code || '-';
      document.getElementById('cartSuccessTotalPrice').textContent = data.total_price_formatted || 'Rp 0';

      const proofStatusEl = document.getElementById('cartSuccessProofStatus');
      if (data.has_proof) {
        proofStatusEl.textContent = '✓ Bukti Terunggah';
        proofStatusEl.className = 'proof-badge-success';
      } else {
        proofStatusEl.textContent = 'Menunggu Konfirmasi WA';
        proofStatusEl.className = 'proof-badge-pending';
      }

      const waBtn = document.getElementById('btnCartProceedWhatsApp');
      if (data.whatsapp_url) {
        waBtn.href = data.whatsapp_url;
        waBtn.style.display = 'inline-flex';
        window.open(data.whatsapp_url, '_blank');
      } else {
        waBtn.style.display = 'none';
      }

      document.getElementById('cartCheckoutFormBody').style.display = 'none';
      document.getElementById('cartCheckoutFooter').style.display = 'none';
      document.getElementById('cartCheckoutSuccessBody').style.display = 'flex';
    })
    .catch(function(err) {
      isCartSubmitting = false;
      submitBtn.disabled = false;
      submitBtn.style.opacity = '1';
      submitText.textContent = origText;
      showCartCheckoutError('Terjadi kesalahan koneksi saat memproses pesanan.');
    });
  };

  // Keyboard and initialization
  document.addEventListener('DOMContentLoaded', function() {
    loadCartData();
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeCartDrawer();
      closeStoreCartCheckout();
    }
  });
})();
</script>

