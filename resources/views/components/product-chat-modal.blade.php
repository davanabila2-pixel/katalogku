<div id="productChatModal" class="product-chat-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="chatModalStoreName">
  <div class="product-chat-backdrop" onclick="closeProductChat()"></div>
  <div class="product-chat-dialog">
    {{-- Header --}}
    <div class="product-chat-header">
      <div class="product-chat-header-store">
        <div id="chatModalAvatar" class="product-chat-avatar">T</div>
        <div>
          <div class="product-chat-header-title" id="chatModalStoreName">Toko</div>
          <div class="product-chat-header-status">
            <span class="status-pulse-dot"></span>
            <span>Online • Balas via WhatsApp</span>
          </div>
        </div>
      </div>
      <button type="button" class="product-chat-close-btn" onclick="closeProductChat()" aria-label="Tutup dialog chat">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    {{-- Chat Body --}}
    <div class="product-chat-body" id="chatModalBody">
      <div class="product-chat-date-pill">Hari ini</div>

      {{-- Pesan dari Toko --}}
      <div class="product-chat-bubble bubble-incoming">
        <div class="bubble-text">
          Halo! Ada yang bisa kami bantu seputar produk kami? Silakan tanyakan ketersediaan ukuran, stok, atau pengiriman di bawah ini.
        </div>
        <div class="bubble-meta">
          <span>Baru saja</span>
        </div>
      </div>

      {{-- Pesan dari Pembeli (Preview) --}}
      <div class="product-chat-bubble bubble-outgoing">
        {{-- Cuplikan Produk --}}
        <div class="chat-product-preview-card">
          <img id="chatModalProductImg" src="" alt="Produk" class="chat-product-thumb">
          <div class="chat-product-info">
            <div id="chatModalProductName" class="chat-product-name">Nama Produk</div>
            <div id="chatModalProductPrice" class="chat-product-price">Rp 0</div>
            <div id="chatModalProductDesc" class="chat-product-desc">Deskripsi singkat produk...</div>
          </div>
        </div>

        {{-- Isi Pertanyaan Pembeli --}}
        <div class="bubble-text" id="chatModalMessagePreview">
          Halo kak, apakah barang ini masih tersedia dan bisa dikirim?
        </div>
        <div class="bubble-meta">
          <span id="chatModalCurrentTime">--:--</span>
          <svg class="check-double-icon" width="16" height="11" viewBox="0 0 16 11" fill="none"><path d="M1 5.5L5 9.5L15 1" stroke="#53bdeb" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M5.5 5.5L9.5 9.5" stroke="#53bdeb" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
      </div>
    </div>

    {{-- Pilihan Pertanyaan Cepat (Chips) --}}
    <div class="product-chat-chips-section">
      <div class="product-chat-chips-label">Pertanyaan Cepat:</div>
      <div class="product-chat-chips" id="chatModalChips">
        <button type="button" class="chat-chip active" onclick="setChatChip(this, 'Halo kak, apakah barang ini masih ready dan bisa dikirim?')">📦 Stok ready?</button>
        <button type="button" class="chat-chip" onclick="setChatChip(this, 'Halo, barang ini bisa dikirim hari ini? Estimasi sampai kapan?')">🚚 Bisa kirim hari ini?</button>
        <button type="button" class="chat-chip" onclick="setChatChip(this, 'Halo, apakah ada pilihan warna atau varian ukuran lain untuk produk ini?')">🎨 Varian & ukuran?</button>
        <button type="button" class="chat-chip" onclick="setChatChip(this, 'Halo, boleh minta info foto asli / detail produk lebih lanjut?')">📸 Minta foto asli?</button>
        <button type="button" class="chat-chip" onclick="setChatChip(this, 'Halo, saya mau beli dalam jumlah banyak. Apakah ada potongan harga khusus?')">💰 Pembelian grosir?</button>
      </div>
    </div>

    {{-- Composer / Footer --}}
    <div class="product-chat-footer">
      <div id="chatModalPhoneWarning" class="chat-phone-warning" style="display:none;">
        ⚠️ Toko ini belum mengatur nomor kontak WhatsApp aktif. Silakan hubungi toko lewat kontak lain yang tertera.
      </div>
      
      <div class="chat-composer-row">
        <textarea id="chatModalInput" class="chat-composer-input" rows="2" placeholder="Tulis pesan atau pertanyaan khusus..." oninput="onChatInputChanged(this.value)"></textarea>
      </div>

      <button type="button" id="chatModalSendBtn" class="btn-chat-send-wa" onclick="submitChatToWhatsApp()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
        <span>Kirim Pesan via WhatsApp</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </button>
    </div>
  </div>
</div>

<script>
(function() {
  let activeProduct = null;

  function formatTimeNow() {
    const d = new Date();
    return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
  }

  function cleanPhone(raw) {
    if (!raw) return null;
    let digits = String(raw).replace(/[^0-9]/g, '');
    if (digits.startsWith('0')) digits = '62' + digits.slice(1);
    else if (digits.startsWith('8')) digits = '62' + digits;
    return digits.length >= 9 ? digits : null;
  }

  window.openProductChat = function(data) {
    activeProduct = data;
    const modal = document.getElementById('productChatModal');
    if (!modal) return;

    // Set Store Info
    document.getElementById('chatModalStoreName').textContent = data.storeName || 'Toko';
    const avatar = document.getElementById('chatModalAvatar');
    avatar.textContent = (data.storeName || 'T').charAt(0).toUpperCase();

    // Set Product Card Info
    const imgEl = document.getElementById('chatModalProductImg');
    imgEl.src = data.imageUrl || '';
    imgEl.alt = data.name || 'Produk';
    document.getElementById('chatModalProductName').textContent = data.name || '-';
    document.getElementById('chatModalProductPrice').textContent = data.price || '';
    document.getElementById('chatModalProductDesc').textContent = data.description || 'Tidak ada deskripsi khusus.';

    // Set Time
    document.getElementById('chatModalCurrentTime').textContent = formatTimeNow();

    // Reset default question
    const defaultMsg = 'Halo kak, apakah barang ini masih ready dan bisa dikirim?';
    const inputEl = document.getElementById('chatModalInput');
    inputEl.value = defaultMsg;
    document.getElementById('chatModalMessagePreview').textContent = defaultMsg;

    // Reset chips
    const chips = document.querySelectorAll('#chatModalChips .chat-chip');
    chips.forEach((c, idx) => {
      if (idx === 0) c.classList.add('active');
      else c.classList.remove('active');
    });

    // Check Phone
    const phone = cleanPhone(data.storePhone);
    const warningEl = document.getElementById('chatModalPhoneWarning');
    const sendBtn = document.getElementById('chatModalSendBtn');

    if (!phone) {
      warningEl.style.display = 'block';
      sendBtn.disabled = true;
      sendBtn.style.opacity = '0.5';
      sendBtn.style.cursor = 'not-allowed';
    } else {
      warningEl.style.display = 'none';
      sendBtn.disabled = false;
      sendBtn.style.opacity = '1';
      sendBtn.style.cursor = 'pointer';
    }

    // Show Modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // Auto scroll chat to bottom
    setTimeout(() => {
      const body = document.getElementById('chatModalBody');
      if (body) body.scrollTop = body.scrollHeight;
      inputEl.focus();
    }, 50);
  };

  window.closeProductChat = function() {
    const modal = document.getElementById('productChatModal');
    if (!modal) return;
    modal.style.display = 'none';
    document.body.style.overflow = '';
  };

  window.setChatChip = function(btn, text) {
    document.querySelectorAll('#chatModalChips .chat-chip').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    const inputEl = document.getElementById('chatModalInput');
    inputEl.value = text;
    document.getElementById('chatModalMessagePreview').textContent = text;
    const body = document.getElementById('chatModalBody');
    if (body) body.scrollTop = body.scrollHeight;
  };

  window.onChatInputChanged = function(val) {
    const preview = document.getElementById('chatModalMessagePreview');
    preview.textContent = val.trim() || '(Ketik pertanyaan Anda)';
    document.querySelectorAll('#chatModalChips .chat-chip').forEach(c => c.classList.remove('active'));
    const body = document.getElementById('chatModalBody');
    if (body) body.scrollTop = body.scrollHeight;
  };

  window.submitChatToWhatsApp = function() {
    if (!activeProduct) return;
    const phone = cleanPhone(activeProduct.storePhone);
    if (!phone) {
      alert('Maaf, nomor WhatsApp toko belum tersedia.');
      return;
    }

    const inputVal = (document.getElementById('chatModalInput').value || '').trim();
    const question = inputVal || 'Apakah barang ini masih ready dan bisa dikirim?';

    const fullMessage =
      `Halo ${activeProduct.storeName || 'Toko'}, saya tertarik dengan produk ini:\n\n` +
      `📦 *${activeProduct.name}*\n` +
      `💰 *Harga*: ${activeProduct.price}\n` +
      `📝 *Deskripsi*: ${activeProduct.description || '-'}\n` +
      `🔗 *Link*: ${activeProduct.url || window.location.href}\n\n` +
      `*Pertanyaan*: ${question}`;

    const waUrl = `https://wa.me/${phone}?text=${encodeURIComponent(fullMessage)}`;
    window.open(waUrl, '_blank');
    closeProductChat();
  };

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeProductChat();
    }
  });
})();
</script>

