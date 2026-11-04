@extends('layouts.app')

@section('content')
<div class="checkout-page-wrap">
  {{-- Breadcrumb --}}
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
      <li class="breadcrumb-item active" aria-current="page">
        <span class="breadcrumb-current">Checkout Pesanan</span>
      </li>
    </ol>
  </nav>

  @php
    $flashSuccess = session('order_success');
    $hasItems = ! empty($items) && count($items) > 0;
    $savedAddresses = $savedAddresses ?? collect();
    $selectedAddress = $savedAddresses->firstWhere('is_default', true) ?? $savedAddresses->first();
    $defaultName = old('buyer_name', $selectedAddress?->recipient_name ?? $customerProfile?->name ?? (auth()->check() ? auth()->user()->name : ''));
    $defaultPhone = old('buyer_phone', $selectedAddress?->phone ?? $customerProfile?->phone ?? '');
    $defaultAddress = old('buyer_address', $selectedAddress?->address ?? $customerProfile?->address ?? '');
    $defaultPostal = old('buyer_postal_code', $selectedAddress?->postal_code ?? $customerProfile?->postal_code ?? '');
    $defaultLat = old('buyer_latitude', $selectedAddress?->latitude ?? $customerProfile?->latitude ?? '');
    $defaultLng = old('buyer_longitude', $selectedAddress?->longitude ?? $customerProfile?->longitude ?? '');
    $defaultNotes = old('buyer_notes', $selectedAddress?->notes ?? $customerProfile?->notes ?? '');
    $hasCoords = !empty($defaultLat) && !empty($defaultLng);
  @endphp

  {{-- State 1: Flash Success (Non-AJAX Redirect Fallback) --}}
  @if($flashSuccess)
    <div class="checkout-success-container">
      <div class="checkout-success-card">
        <div class="success-icon-badge">
          <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <h2 class="success-heading">Pesanan Berhasil Dicatat!</h2>
        <p class="success-desc">Data pesanan Anda telah tersimpan di sistem <strong>{{ $store->name }}</strong>. Silakan lanjutkan ke WhatsApp toko untuk konfirmasi pemrosesan.</p>

        <div class="order-code-box">
          <div class="order-code-label">Nomor Pesanan:</div>
          <div class="order-code-value" id="flashOrderCode">{{ $flashSuccess['order_code'] }}</div>
          <button type="button" class="btn-copy-code" onclick="copyOrderCode('{{ $flashSuccess['order_code'] }}', this)" title="Salin nomor pesanan">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
            <span>Salin</span>
          </button>
        </div>

        <div class="success-summary-box">
          <div class="summary-line">
            <span>Total Pembayaran:</span>
            <strong style="color:var(--ink-deep);font-size:15px;">{{ $flashSuccess['total_price_formatted'] }}</strong>
          </div>
          <div class="summary-line">
            <span>Status Bukti Transfer:</span>
            <span class="proof-badge-success">{{ !empty($flashSuccess['has_proof']) ? '✓ Terunggah' : 'Menunggu Konfirmasi' }}</span>
          </div>
        </div>

        @if(!empty($flashSuccess['whatsapp_url']))
          <a href="{{ $flashSuccess['whatsapp_url'] }}" target="_blank" rel="noopener" class="btn-checkout-wa-cta">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
            <span>Buka Chat WhatsApp Toko</span>
          </a>
        @endif

        <div class="checkout-success-actions">
          @auth
            <a href="{{ route('store.orders', $store->slug) }}" class="btn-secondary-action">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
              <span>Lihat Pesanan Saya</span>
            </a>
          @endauth
          <a href="{{ $store->url() }}" class="btn-secondary-action">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <span>Kembali ke Katalog Toko</span>
          </a>
        </div>
      </div>
    </div>
  @elseif(! $hasItems)
    {{-- State 2: Keranjang Kosong --}}
    <div class="checkout-empty-card">
      <div class="checkout-empty-icon">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
        </svg>
      </div>
      <h2 class="checkout-empty-title">Keranjang Belanja Toko Kosong</h2>
      <p class="checkout-empty-desc">Belum ada produk yang dipilih untuk checkout di <strong>{{ $store->name }}</strong>. Silakan pilih produk dari katalog toko.</p>
      <a href="{{ $store->url() }}" class="btn btn-primary" style="padding:11px 24px;border-radius:var(--r-full);display:inline-flex;align-items:center;gap:8px;text-decoration:none;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/></svg>
        <span>Jelajahi Produk {{ $store->name }}</span>
      </a>
    </div>
  @else
    {{-- State 3: Halaman Checkout Aktif (Formulir Mandiri) --}}
    <div id="checkoutActiveContainer">
      <div class="checkout-header-area">
        <h1 class="checkout-main-title">Checkout Pesanan</h1>
        <p class="checkout-main-subtitle">
          Selesaikan data pemesan dan konfirmasi pembayaran untuk toko <strong>{{ $store->name }}</strong>.
        </p>
      </div>

      {{-- Notifikasi Error Global --}}
      <div id="checkoutGlobalError" class="checkout-alert-error" style="display:none;" role="alert">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span id="checkoutGlobalErrorText"></span>
      </div>

      <form id="storeCheckoutForm" method="POST" action="{{ route('cart.checkout', $store->slug) }}" enctype="multipart/form-data" onsubmit="handleCheckoutFormSubmit(event)">
        @csrf
        @if($isDirectBuy && $directProduct)
          <input type="hidden" name="product_id" value="{{ $directProduct->id }}">
          <input type="hidden" name="quantity" id="checkoutDirectQtyInput" value="{{ $totalQty }}">
        @endif

        <div class="checkout-layout-grid">
          {{-- Kolom Kiri: Data Pemesan & Pembayaran --}}
          <div class="checkout-left-col">
            {{-- 1. Data Pemesan & Pengiriman --}}
            <div class="checkout-card">
              <div class="checkout-card-header">
                <div class="checkout-step-badge">1</div>
                <div>
                  <h3 class="checkout-card-title">Data Pemesan & Pengiriman</h3>
                  <div class="checkout-card-subtitle">Identitas pembeli dan alamat tujuan paket</div>
                </div>
              </div>

              <div class="checkout-card-body">
                @if($savedAddresses->isNotEmpty())
                  {{-- Dropdown Pemilihan Alamat Pengiriman (Multiple Addresses) --}}
                  <div class="checkout-form-group" style="margin-bottom:16px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;gap:8px;flex-wrap:wrap;">
                      <label for="checkoutAddressSelect" class="checkout-label" style="margin:0;font-weight:700;display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#0f172a;">
                        <span>📍 Pilih Alamat Pengiriman Saya:</span>
                        <span class="badge" style="background:#e0f2fe;color:#0369a1;font-size:11px;font-weight:700;padding:2px 7px;border-radius:6px;">{{ $savedAddresses->count() }} Alamat</span>
                      </label>
                      <a href="{{ route('store.address', $store->slug) }}" target="_blank" style="font-size:12px;font-weight:600;color:var(--primary, #0066ff);text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                        <span>+ Kelola / Tambah Alamat</span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                      </a>
                    </div>
                    <div style="position:relative;">
                      <select id="checkoutAddressSelect" name="saved_address_id" class="checkout-input" onchange="onAddressDropdownChange(this)" style="width:100%;font-size:13.5px;padding:11px 14px;border-radius:10px;border:1.5px solid #cbd5e1;background:#fff;cursor:pointer;line-height:1.4;">
                        @foreach($savedAddresses as $addr)
                          <option value="{{ $addr->id }}" {{ ($selectedAddress && $selectedAddress->id === $addr->id) ? 'selected' : '' }}>
                            {{ $addr->label ? "[{$addr->label}]" : '' }} {{ $addr->recipient_name }} · {{ Str::limit($addr->address, 65) }} {{ $addr->is_default ? '(⭐ Utama)' : '' }}
                          </option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                @endif

                <div class="checkout-field-row">
                  <div class="checkout-form-group">
                    <label for="checkoutBuyerName" class="checkout-label">Nama Lengkap <span class="required-star">*</span></label>
                    <input type="text" id="checkoutBuyerName" name="buyer_name" class="checkout-input" placeholder="cth: Budi Santoso" value="{{ $defaultName }}" required autocomplete="name">
                  </div>
                  <div class="checkout-form-group">
                    <label for="checkoutBuyerPhone" class="checkout-label">No. WhatsApp Pembeli <span class="required-star">*</span></label>
                    <input type="tel" id="checkoutBuyerPhone" name="buyer_phone" class="checkout-input" placeholder="cth: 081234567890" value="{{ $defaultPhone }}" required autocomplete="tel">
                  </div>
                </div>

                <div class="checkout-form-group" style="margin-top:12px;">
                  <div class="checkout-label-with-action">
                    <label for="checkoutBuyerAddress" class="checkout-label">Alamat Pengiriman Lengkap <span class="required-star">*</span></label>
                    <button type="button" id="btnCheckoutAutoGps" class="btn-auto-gps" onclick="autoDetectCheckoutAddress()" title="Ambil alamat otomatis menggunakan GPS perangkat">
                      <span id="btnCheckoutAutoGpsIcon">📍</span>
                      <span id="btnCheckoutAutoGpsText">Gunakan Lokasi Saya</span>
                    </button>
                  </div>
                  <textarea id="checkoutBuyerAddress" name="buyer_address" class="checkout-textarea" rows="3" placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan, kota/kabupaten, kode pos..." required>{{ $defaultAddress }}</textarea>
                  <div id="checkoutGpsStatusMessage" class="gps-status-hint" style="display:none;"></div>
                </div>

                <div class="checkout-field-row" style="margin-top:12px;">
                  <div class="checkout-form-group">
                    <label for="checkoutBuyerPostalCode" class="checkout-label">Kode Pos <span style="font-size:11px;font-weight:normal;color:#0284c7;">(Penting untuk Cek Ongkir)</span></label>
                    <input type="text" id="checkoutBuyerPostalCode" name="buyer_postal_code" class="checkout-input" placeholder="cth: 40115" value="{{ $defaultPostal }}" maxlength="10">
                  </div>
                  <div class="checkout-form-group">
                    <label class="checkout-label">Koordinat GPS / Titik Peta</label>
                    <div style="display:flex;align-items:center;gap:8px;">
                      <input type="text" id="checkoutBuyerCoordsDisplay" class="checkout-input" readonly placeholder="Belum ada koordinat" value="{{ $hasCoords ? "{$defaultLat}, {$defaultLng}" : '' }}" style="background:#f8fafc;font-family:monospace;font-size:12px;">
                      @auth
                        <a href="{{ route('store.address', $store->slug) }}" class="btn-address-link" target="_blank" title="Atur peta di Alamat Saya" style="white-space:nowrap;padding:10px 12px;border-radius:10px;background:#f1f5f9;color:#334155;text-decoration:none;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:4px;border:1px solid #cbd5e1;">
                          <span>🗺️ Alamat Saya</span>
                        </a>
                      @endauth
                    </div>
                  </div>
                </div>
                <input type="hidden" id="checkoutBuyerLat" name="buyer_latitude" value="{{ $defaultLat }}">
                <input type="hidden" id="checkoutBuyerLng" name="buyer_longitude" value="{{ $defaultLng }}">

                <div class="checkout-form-group" style="margin-top:12px;margin-bottom:0;">
                  <label for="checkoutBuyerNotes" class="checkout-label">Catatan Tambahan (Opsional)</label>
                  <input type="text" id="checkoutBuyerNotes" name="buyer_notes" class="checkout-input" placeholder="cth: Titipkan di pos satpam, jangan dibanting..." value="{{ $defaultNotes }}">
                </div>
              </div>
            </div>

            {{-- 2. Pilihan Pengiriman & Cek Ongkir (Biteship) --}}
            <div class="checkout-card" id="shippingSectionCard">
              <div class="checkout-card-header">
                <div class="checkout-step-badge">2</div>
                <div>
                  <h3 class="checkout-card-title">Pilihan Pengiriman & Cek Ongkir (Biteship)</h3>
                  <div class="checkout-card-subtitle">Pilih ekspedisi dan estimasi ongkos kirim ke alamat Anda</div>
                </div>
              </div>

              <div class="checkout-card-body">
                <input type="hidden" id="checkoutSelectedCourier" name="shipping_courier" value="">
                <input type="hidden" id="checkoutSelectedService" name="shipping_service" value="">
                <input type="hidden" id="checkoutSelectedFee" name="shipping_fee" value="0">
                <input type="hidden" id="checkoutSelectedEtd" name="shipping_etd" value="">

                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px;margin-bottom:14px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                  <div>
                    <div style="font-size:11.5px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">Gudang / Asal Pengiriman:</div>
                    <div style="font-size:13.5px;font-weight:700;color:#0f172a;margin-top:2px;">📍 {{ $store->name }} {{ $store->city ? '('.$store->city.')' : '' }}</div>
                    @if($store->postal_code)
                      <div style="font-size:12px;color:#64748b;">Kode Pos Asal: {{ $store->postal_code }}</div>
                    @endif
                    <div style="font-size:12px;color:#64748b;margin-top:4px;">
                      Ekspedisi aktif:
                      @forelse($store->activeCourierCodes() as $cc)
                        <span class="courier-badge-tag {{ $cc }}" style="margin-left:2px;">{{ \App\Support\Couriers::all()[$cc]['name'] }}</span>
                      @empty
                        <em>belum ada (Ambil Sendiri / manual via WhatsApp)</em>
                      @endforelse
                    </div>
                  </div>

                  <button type="button" class="btn-check-rates" id="btnCheckShippingRates" onclick="calculateShippingRates()" style="padding:9px 18px;border-radius:10px;background:var(--brand-coral);color:#fff;border:none;font-weight:700;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:opacity 0.2s;">
                    <span id="btnCheckShippingRatesIcon">🚚</span>
                    <span id="btnCheckShippingRatesText">Cek Pilihan Kurir</span>
                  </button>
                </div>

                {{-- Status / Error box rates --}}
                <div id="shippingRatesStatus" style="display:none;margin-bottom:12px;padding:10px 14px;border-radius:10px;font-size:13px;"></div>

                {{-- Courier Options List --}}
                <div id="shippingCouriersList" style="display:flex;flex-direction:column;gap:10px;">
                  {{-- Default / Tanpa Kurir Option --}}
                  <label class="courier-option-card selected" id="courier-opt-default" onclick="selectCourierOption(null)">
                    <input type="radio" name="courier_radio" value="" checked style="accent-color:var(--brand-coral);">
                    <div style="flex:1;">
                      <div style="font-weight:700;font-size:13.5px;color:#0f172a;">Ambil Sendiri / Ongkir Manual Toko</div>
                      <div style="font-size:12px;color:#64748b;">Tanpa ongkir otomatis, koordinasi pengiriman langsung dengan penjual melalui WhatsApp.</div>
                    </div>
                    <div style="text-align:right;">
                      <div style="font-weight:700;font-size:14px;color:#16a34a;">Gratis / Rp 0</div>
                    </div>
                  </label>
                </div>
              </div>
            </div>

            {{-- 3. Metode & QRIS Pembayaran Toko --}}
            <div class="checkout-card">
              <div class="checkout-card-header">
                <div class="checkout-step-badge">3</div>
                <div>
                  <h3 class="checkout-card-title">Metode & QR Pembayaran Toko</h3>
                  <div class="checkout-card-subtitle">Pindai QRIS resmi atau lakukan transfer bank</div>
                </div>
              </div>

              <div class="checkout-card-body">
                @if($store->hasStaticQr())
                  <div class="checkout-qr-layout">
                    <div class="checkout-qr-box">
                      <div class="qr-image-frame">
                        <img id="checkoutQrImage" @if($store->hasDynamicQris()) data-dynamic="1" data-base-url="{{ route('store.qris', $store->slug) }}" @endif src="{{ $store->hasDynamicQris() ? $store->dynamicQrisUrl((float) $totalPrice) : $store->qrImageUrl() }}" alt="QRIS {{ $store->name }}" class="qr-code-img">
                      </div>
                      @if($store->hasDynamicQris())
                        <div id="checkoutQrDynamicNote" style="margin-top:8px;font-size:11.5px;font-weight:700;color:#065f46;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:8px;padding:5px 9px;text-align:center;">
                          ✓ QR sudah berisi nominal, langsung scan &amp; bayar
                        </div>
                      @endif
                      <div class="qr-account-badge">{{ $store->qr_account_name ?: ($store->name . ' QRIS') }}</div>
                    </div>

                    <div class="checkout-qr-details">
                      <div class="checkout-qr-amount-card">
                        <div class="qr-amount-caption">Total Pembayaran Pas:</div>
                        <div class="qr-amount-row">
                          <strong class="qr-amount-num" id="checkoutDisplayTotalAmount">Rp {{ number_format($totalPrice, 0, ',', '.') }}</strong>
                          <button type="button" class="btn-copy-amount" onclick="copyAmount(String(window.currentGrandTotal || {{ (int) $totalPrice }}), this)" title="Salin nominal total">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                            <span>Salin Nominal</span>
                          </button>
                        </div>
                      </div>

                      <div class="qr-instruction-steps" style="margin-top:12px;">
                        <ol>
                          <li>Buka aplikasi m-Banking atau E-Wallet (BCA, Mandiri, BRI, GoPay, OVO, ShopeePay, Dana, dll).</li>
                          <li>Scan kode QR di samping dan bayar pas sesuai total: <strong id="checkoutInstructionTotal">Rp {{ number_format($totalPrice, 0, ',', '.') }}</strong></li>
                          <li>Simpan tangkapan layar (screenshot) bukti transfer dan unggah pada bagian di bawah.</li>
                        </ol>
                      </div>
                    </div>
                  </div>
                @else
                  <div class="buy-no-qr-alert">
                    <div style="font-weight:700;font-size:14px;margin-bottom:4px;display:flex;align-items:center;gap:6px;">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                      <span>Instruksi Transfer via WhatsApp</span>
                    </div>
                    <div>Toko <strong>{{ $store->name }}</strong> belum mengatur kode QRIS langsung. Pesanan Anda akan otomatis dicatat di sistem dan diteruskan ke WhatsApp toko untuk konfirmasi rekening transfer resmi.</div>
                  </div>
                @endif
              </div>
            </div>

            {{-- 4. Unggah Bukti Transfer / QRIS --}}
            <div class="checkout-card">
              <div class="checkout-card-header">
                <div class="checkout-step-badge">4</div>
                <div>
                  <div class="d-flex align-items-center gap-2">
                    <h3 class="checkout-card-title">Unggah Bukti Transfer / QRIS</h3>
                    <span class="badge-optional">Disarankan</span>
                  </div>
                  <div class="checkout-card-subtitle">Lampirkan foto struk transfer agar pesanan langsung diproses cepat</div>
                </div>
              </div>

              <div class="checkout-card-body">
                <div class="proof-upload-area" onclick="document.getElementById('checkoutPaymentProofInput').click()">
                  <input type="file" id="checkoutPaymentProofInput" name="payment_proof" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="onCheckoutPaymentProofSelected(this)">
                  
                  <div id="checkoutProofPlaceholder" class="proof-placeholder">
                    <div class="proof-icon-circle">
                      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    </div>
                    <div class="proof-text-primary">Klik atau seret foto bukti transfer di sini</div>
                    <div class="proof-text-muted">Mendukung format JPG, PNG, atau WebP (Maksimal 5MB)</div>
                  </div>

                  <div id="checkoutProofPreviewBox" class="proof-preview-box" style="display:none;">
                    <img id="checkoutProofPreviewImg" src="" alt="Bukti Pembayaran" class="proof-thumb">
                    <div class="proof-info">
                      <div id="checkoutProofFileName" class="proof-filename">bukti-transfer.jpg</div>
                      <div class="proof-status-tag">✓ Siap Dilampirkan</div>
                      <button type="button" class="btn-remove-proof" onclick="removeCheckoutPaymentProof(event)">
                        ✕ Hapus Foto
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {{-- Kolom Kanan (Sticky Sidebar): Ringkasan Belanja Toko --}}
          <div class="checkout-sidebar-col">
            <div class="checkout-sidebar-sticky">
              <div class="checkout-summary-card">
                <div class="summary-card-header">
                  <div class="summary-store-meta">
                    @if($store->logoUrl())
                      <img src="{{ $store->logoUrl() }}" alt="{{ $store->name }}" class="summary-store-logo">
                    @else
                      <div class="summary-store-initial">{{ mb_substr($store->name, 0, 1) }}</div>
                    @endif
                    <div>
                      <h4 class="summary-store-name">{{ $store->name }}</h4>
                      <span class="summary-store-badge">Pesanan Toko ({{ count($items) }} jenis)</span>
                    </div>
                  </div>
                </div>

                {{-- Daftar Produk --}}
                <div class="summary-items-list">
                  @foreach($items as $idx => $item)
                    <div class="summary-item-row {{ !empty($item['is_out_of_stock']) || !empty($item['exceeds_stock']) ? 'is-out-of-stock' : '' }}">
                      <img src="{{ $item['image_url'] ?: asset('images/placeholder.svg') }}" alt="{{ $item['name'] }}" class="summary-item-thumb">
                      <div class="summary-item-info">
                        <a href="{{ $item['product_url'] }}" target="_blank" class="summary-item-name" title="{{ $item['name'] }}">
                          {{ $item['name'] }}
                        </a>
                        <div class="summary-item-meta">
                          @if($isDirectBuy && count($items) === 1)
                            <div class="checkout-qty-stepper">
                              <button type="button" class="checkout-stepper-btn" onclick="adjustDirectQty(-1, {{ $item['stock'] }}, {{ $item['price'] }})" title="Kurangi jumlah">−</button>
                              <span id="directQtyDisplay" class="checkout-stepper-val">{{ $item['quantity'] }}</span>
                              <button type="button" class="checkout-stepper-btn" onclick="adjustDirectQty(1, {{ $item['stock'] }}, {{ $item['price'] }})" title="Tambah jumlah">+</button>
                            </div>
                            <span class="summary-item-price-unit">× {{ $item['price_formatted'] }} ({{ $item['weight_formatted'] ?? '500 gr' }})</span>
                          @else
                            <span>{{ $item['quantity'] }} unit × {{ $item['price_formatted'] }} ({{ $item['weight_formatted'] ?? '500 gr' }})</span>
                          @endif
                        </div>
                        @if(!empty($item['is_out_of_stock']))
                          <span class="badge-stock-alert">Stok Habis</span>
                        @elseif(!empty($item['exceeds_stock']))
                          <span class="badge-stock-alert">Melebihi Stok (Sisa {{ $item['stock'] }})</span>
                        @endif
                      </div>
                      <strong class="summary-item-subtotal" id="{{ $isDirectBuy ? 'directItemSubtotal' : '' }}">
                        {{ $item['subtotal_formatted'] }}
                      </strong>
                    </div>
                  @endforeach
                </div>

                {{-- Rincian Biaya --}}
                <div class="summary-pricing-breakdown">
                  <div class="summary-price-line">
                    <span>Jumlah Barang</span>
                    <strong id="checkoutTotalQtySummary">{{ $totalQty }} unit</strong>
                  </div>
                  <div class="summary-price-line">
                    <span>Total Berat Kirim</span>
                    <strong id="checkoutTotalWeightSummary" style="color:var(--ink-deep);">{{ $totalWeightFormatted ?? '—' }}</strong>
                  </div>
                  <div class="summary-price-line">
                    <span>Subtotal Produk</span>
                    <strong id="checkoutSubtotalSummary">Rp {{ number_format($totalPrice, 0, ',', '.') }}</strong>
                  </div>
                  <div class="summary-price-line" id="checkoutShippingSummaryRow">
                    <span id="checkoutShippingLabel">Ongkos Kirim</span>
                    <strong id="checkoutShippingFeeSummary" style="color:#16a34a;">Rp 0</strong>
                  </div>
                  <div class="summary-price-divider"></div>
                  <div class="summary-price-line summary-total-line">
                    <span>Total Pembayaran</span>
                    <strong class="summary-total-val" id="checkoutGrandTotalSummary">Rp {{ number_format($totalPrice, 0, ',', '.') }}</strong>
                  </div>
                </div>

                {{-- Submit Button --}}
                <div class="summary-submit-wrap">
                  @if($hasOutOfStock)
                    <div class="checkout-stock-error-hint">
                      ⛔ Ada produk dengan stok habis atau melebihi kuota. Silakan periksa kembali keranjang Anda.
                    </div>
                    <button type="button" class="btn-checkout-submit disabled" disabled>
                      <span>Tidak Dapat Melanjutkan Checkout</span>
                    </button>
                  @else
                    <button type="submit" id="btnCheckoutSubmit" class="btn-checkout-submit">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
                      <span id="btnCheckoutSubmitText">Kirim Pesanan & Konfirmasi WA</span>
                    </button>
                  @endif

                  <div class="checkout-trust-badge">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span>Pesanan tercatat aman di sistem toko dan langsung diteruskan ke WhatsApp resmi.</span>
                  </div>

                  <a href="{{ $store->url() }}" class="btn-continue-shopping">
                    ← Lanjut Belanja di {{ $store->name }}
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>

    {{-- State 4: Tampilan Sukses Setelah Submit AJAX --}}
    <div id="checkoutSuccessContainer" class="checkout-success-container" style="display:none;">
      <div class="checkout-success-card">
        <div class="success-icon-badge">
          <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <h2 class="success-heading">Pesanan Berhasil Dicatat!</h2>
        <p class="success-desc">Data pesanan Anda telah tersimpan di sistem toko <strong>{{ $store->name }}</strong>. Silakan lanjutkan ke WhatsApp toko untuk konfirmasi pemrosesan.</p>

        <div class="order-code-box">
          <div class="order-code-label">Nomor Pesanan:</div>
          <div class="order-code-value" id="ajaxSuccessOrderCode">-</div>
          <button type="button" class="btn-copy-code" id="btnCopyAjaxCode" title="Salin nomor pesanan">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
            <span>Salin</span>
          </button>
        </div>

        <div class="success-summary-box">
          <div class="summary-line">
            <span>Total Pembayaran:</span>
            <strong id="ajaxSuccessTotalPrice" style="color:var(--ink-deep);font-size:15px;">Rp 0</strong>
          </div>
          <div class="summary-line">
            <span>Status Bukti Transfer:</span>
            <span id="ajaxSuccessProofStatus" class="proof-badge-success">✓ Terunggah</span>
          </div>
        </div>

        <a id="btnAjaxProceedWhatsApp" href="#" target="_blank" rel="noopener" class="btn-checkout-wa-cta">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
          <span>Buka Chat WhatsApp Toko</span>
        </a>

        <div class="checkout-success-actions">
          @auth
            <a href="{{ route('store.orders', $store->slug) }}" class="btn-secondary-action">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
              <span>Lihat Pesanan Saya</span>
            </a>
          @endauth
          <a href="{{ $store->url() }}" class="btn-secondary-action">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <span>Kembali ke Katalog Toko</span>
          </a>
        </div>
      </div>
    </div>
  @endif
</div>

<style>
.courier-option-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  background: #ffffff;
  transition: all 0.2s ease;
}
.courier-option-card:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}
.courier-option-card.selected {
  border-color: var(--brand-coral);
  background: #fff5f6;
  box-shadow: 0 0 0 1px var(--brand-coral);
}
.courier-badge-tag {
  font-size: 11px;
  font-weight: 800;
  padding: 3px 8px;
  border-radius: 6px;
  background: #f1f5f9;
  color: #334155;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.courier-badge-tag.jne { background: #fee2e2; color: #dc2626; }
.courier-badge-tag.sicepat { background: #fef3c7; color: #b45309; }
.courier-badge-tag.jnt { background: #fee2e2; color: #b91c1c; }
.courier-badge-tag.anteraja { background: #ede9fe; color: #6d28d9; }
.courier-badge-tag.pos { background: #ffedd5; color: #c2410c; }
.courier-badge-tag.tiki { background: #e0f2fe; color: #0369a1; }
.courier-badge-tag.wahana { background: #fef9c3; color: #854d0e; }
.courier-badge-tag.gojek { background: #dcfce7; color: #15803d; }
.courier-badge-tag.grab { background: #dcfce7; color: #166534; }
</style>

{{-- Scripts untuk Interaktivitas Halaman Checkout --}}
<script>
  let selectedCheckoutProofFile = null;
  let isCheckoutSubmitting = false;
  let currentDirectQty = {{ (int) $totalQty }};
  const unitPrice = {{ $isDirectBuy && count($items) === 1 ? (float) $items[0]['price'] : 0 }};
  const baseItemsSubtotal = {{ (float) $totalPrice }};
  const isDirectBuy = {{ $isDirectBuy ? 'true' : 'false' }};
  const directProduct = {{ $directProduct ? 'true' : 'false' }};

  let selectedShippingFee = 0;
  let availableRates = [];

  // Multi-Address Dropdown Handlers
  const savedAddressesData = @js($savedAddresses->keyBy('id'));

  function onAddressDropdownChange(selectElem) {
    if (!selectElem) return;
    const selectedId = selectElem.value;
    const addr = savedAddressesData[selectedId];
    if (addr) {
      selectSavedAddress(addr);
    }
  }

  function selectSavedAddress(addr) {
    if (!addr) return;

    // 1. Perbarui nilai input form checkout
    const nameInput = document.getElementById('checkoutBuyerName');
    const phoneInput = document.getElementById('checkoutBuyerPhone');
    const addressInput = document.getElementById('checkoutBuyerAddress');
    const postalInput = document.getElementById('checkoutBuyerPostalCode');
    const latInput = document.getElementById('checkoutBuyerLat');
    const lngInput = document.getElementById('checkoutBuyerLng');
    const coordsDisplay = document.getElementById('checkoutBuyerCoordsDisplay');
    const notesInput = document.getElementById('checkoutBuyerNotes');

    if (nameInput) nameInput.value = addr.recipient_name || '';
    if (phoneInput) phoneInput.value = addr.phone || '';
    if (addressInput) addressInput.value = addr.address || '';
    if (postalInput) postalInput.value = addr.postal_code || '';
    if (latInput) latInput.value = addr.latitude || '';
    if (lngInput) lngInput.value = addr.longitude || '';
    if (notesInput && addr.notes) notesInput.value = addr.notes;

    if (coordsDisplay) {
      if (addr.latitude && addr.longitude) {
        coordsDisplay.value = `${Number(addr.latitude).toFixed(5)}, ${Number(addr.longitude).toFixed(5)}`;
      } else {
        coordsDisplay.value = 'Belum ada koordinat';
      }
    }

    // 2. Sinkronkan dropdown value jika dipanggil programatis
    const selectElem = document.getElementById('checkoutAddressSelect');
    if (selectElem && selectElem.value != addr.id) {
      selectElem.value = addr.id;
    }

    // 3. Picu ulang kalkulasi ongkos kirim Biteship dengan alamat baru
    if (typeof calculateShippingRates === 'function') {
      calculateShippingRates();
    }
  }

  function formatRupiah(num) {
    return 'Rp ' + Number(num).toLocaleString('id-ID');
  }

  function updateGrandTotalDisplay() {
    const currentSubtotal = (isDirectBuy && directProduct) ? (currentDirectQty * unitPrice) : baseItemsSubtotal;
    const grandTotal = currentSubtotal + selectedShippingFee;
    const formatted = formatRupiah(grandTotal);

    const grandTotalSummary = document.getElementById('checkoutGrandTotalSummary');
    const qrDisplay = document.getElementById('checkoutDisplayTotalAmount');
    const qrInstr = document.getElementById('checkoutInstructionTotal');

    if (grandTotalSummary) grandTotalSummary.textContent = formatted;
    if (qrDisplay) qrDisplay.textContent = formatted;
    if (qrInstr) qrInstr.textContent = formatted;

    window.currentGrandTotal = Math.round(grandTotal);

    // QRIS dinamis: muat ulang QR dengan nominal total terbaru (termasuk ongkir).
    const qrImg = document.getElementById('checkoutQrImage');
    if (qrImg && qrImg.dataset.dynamic === '1' && grandTotal > 0) {
      qrImg.src = qrImg.dataset.baseUrl + '?amount=' + Math.round(grandTotal);
    }
  }

  // Direct Buy Qty Adjuster
  function adjustDirectQty(delta, maxStock, price) {
    let newQty = currentDirectQty + delta;
    if (newQty < 1) newQty = 1;
    if (newQty > maxStock) {
      alert('Jumlah pembelian melebihi stok yang tersedia (' + maxStock + ' unit).');
      return;
    }

    currentDirectQty = newQty;
    const qtyDisplay = document.getElementById('directQtyDisplay');
    const qtyInput = document.getElementById('checkoutDirectQtyInput');
    const totalQtySummary = document.getElementById('checkoutTotalQtySummary');
    const subtotalDisplay = document.getElementById('directItemSubtotal');
    const subtotalSummary = document.getElementById('checkoutSubtotalSummary');

    if (qtyDisplay) qtyDisplay.textContent = newQty;
    if (qtyInput) qtyInput.value = newQty;
    if (totalQtySummary) totalQtySummary.textContent = newQty + ' unit';

    const weightSummary = document.getElementById('checkoutTotalWeightSummary');
    if (weightSummary) {
      const unitW = {{ (int) ($items[0]['weight'] ?? 500) }};
      const totG = newQty * unitW;
      weightSummary.textContent = totG >= 1000
        ? (totG / 1000).toLocaleString('id-ID', {maximumFractionDigits: 2}) + ' kg'
        : totG.toLocaleString('id-ID') + ' gr';
    }

    const newSubtotal = newQty * price;
    const formatted = formatRupiah(newSubtotal);

    if (subtotalDisplay) subtotalDisplay.textContent = formatted;
    if (subtotalSummary) subtotalSummary.textContent = formatted;

    updateGrandTotalDisplay();
  }

  // Geolocation Auto Address
  function autoDetectCheckoutAddress() {
    const btn = document.getElementById('btnCheckoutAutoGps');
    const btnText = document.getElementById('btnCheckoutAutoGpsText');
    const btnIcon = document.getElementById('btnCheckoutAutoGpsIcon');
    const statusHint = document.getElementById('checkoutGpsStatusMessage');
    const addressInput = document.getElementById('checkoutBuyerAddress');
    const latInput = document.getElementById('checkoutBuyerLat');
    const lngInput = document.getElementById('checkoutBuyerLng');
    const coordsDisplay = document.getElementById('checkoutBuyerCoordsDisplay');
    const postalInput = document.getElementById('checkoutBuyerPostalCode');

    if (!navigator.geolocation) {
      alert('Peramban Anda tidak mendukung deteksi lokasi otomatis (GPS). Silakan masukkan alamat secara manual.');
      return;
    }

    btn.disabled = true;
    btnIcon.textContent = '⏳';
    btnText.textContent = 'Mencari Lokasi...';
    statusHint.className = 'gps-status-hint info';
    statusHint.textContent = 'Menghubungkan GPS perangkat Anda...';
    statusHint.style.display = 'block';

    navigator.geolocation.getCurrentPosition(
      async function(pos) {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        statusHint.textContent = `Titik koordinat ditemukan (${lat.toFixed(5)}, ${lng.toFixed(5)}). Mengambil alamat lengkap...`;

        if (latInput) latInput.value = lat.toFixed(6);
        if (lngInput) lngInput.value = lng.toFixed(6);
        if (coordsDisplay) coordsDisplay.value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;

        try {
          const resp = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`, {
            headers: { 'Accept': 'application/json' }
          });

          if (!resp.ok) throw new Error('Gagal menghubungi layanan geocoding.');

          const data = await resp.json();
          let addr = data.display_name;

          if (data.address) {
            const parts = [];
            if (data.address.road) parts.push(data.address.road);
            if (data.address.neighbourhood || data.address.suburb) parts.push(data.address.neighbourhood || data.address.suburb);
            if (data.address.village || data.address.town || data.address.city_district) parts.push(data.address.village || data.address.town || data.address.city_district);
            if (data.address.city || data.address.county) parts.push(data.address.city || data.address.county);
            if (data.address.state) parts.push(data.address.state);
            if (data.address.postcode) {
              parts.push(data.address.postcode);
              if (postalInput && !postalInput.value) {
                postalInput.value = data.address.postcode;
              }
            }

            if (parts.length > 0) {
              addr = parts.join(', ');
            }
          }

          if (addr) {
            addressInput.value = addr;
            statusHint.className = 'gps-status-hint success';
            statusHint.textContent = '✓ Alamat berhasil dideteksi dari GPS Anda. Silakan lengkapi nomor rumah/patokan jika diperlukan.';
          } else {
            addressInput.value = `Koordinat: ${lat}, ${lng}`;
            statusHint.className = 'gps-status-hint warning';
            statusHint.textContent = 'Titik lokasi ditemukan. Silakan tambahkan rincian nama jalan atau nomor rumah.';
          }

          // Cek ongkir otomatis setelah GPS ditemukan
          calculateShippingRates();

        } catch (e) {
          addressInput.value = `Lokasi GPS: https://www.google.com/maps?q=${lat},${lng}`;
          statusHint.className = 'gps-status-hint warning';
          statusHint.textContent = 'Koordinat peta disematkan. Silakan lengkapi alamat detail secara manual.';
          calculateShippingRates();
        } finally {
          btn.disabled = false;
          btnIcon.textContent = '📍';
          btnText.textContent = 'Gunakan Lokasi Saya';
        }
      },
      function(err) {
        btn.disabled = false;
        btnIcon.textContent = '📍';
        btnText.textContent = 'Gunakan Lokasi Saya';
        statusHint.className = 'gps-status-hint warning';

        if (err.code === 1) {
          statusHint.textContent = 'Izin akses lokasi ditolak oleh peramban. Silakan ketik alamat Anda secara manual.';
        } else {
          statusHint.textContent = 'Tidak dapat mengambil sinyal GPS. Silakan masukkan alamat Anda secara manual.';
        }
      },
      { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
    );
  }

  // Payment Proof Handler
  function onCheckoutPaymentProofSelected(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
      alert('Format foto bukti pembayaran harus berupa JPG, PNG, atau WebP.');
      input.value = '';
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      alert('Ukuran foto bukti transfer maksimal 5 MB.');
      input.value = '';
      return;
    }

    selectedCheckoutProofFile = file;
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('checkoutProofPreviewImg').src = e.target.result;
      document.getElementById('checkoutProofFileName').textContent = file.name;
      document.getElementById('checkoutProofPlaceholder').style.display = 'none';
      document.getElementById('checkoutProofPreviewBox').style.display = 'flex';
    };
    reader.readAsDataURL(file);
  }

  function removeCheckoutPaymentProof(event) {
    if (event) event.stopPropagation();
    selectedCheckoutProofFile = null;
    const input = document.getElementById('checkoutPaymentProofInput');
    if (input) input.value = '';
    document.getElementById('checkoutProofPreviewImg').src = '';
    document.getElementById('checkoutProofFileName').textContent = '';
    document.getElementById('checkoutProofPlaceholder').style.display = 'flex';
    document.getElementById('checkoutProofPreviewBox').style.display = 'none';
  }

  // Salin Nominal / Kode
  function copyAmount(amount, btn) {
    if (!amount) return;
    navigator.clipboard.writeText(amount.toString()).then(() => {
      const originalText = btn.innerHTML;
      btn.innerHTML = '<span>✓ Disalin!</span>';
      setTimeout(() => { btn.innerHTML = originalText; }, 1800);
    }).catch(() => {
      alert('Nominal: ' + amount);
    });
  }

  function copyOrderCode(code, btn) {
    if (!code) return;
    navigator.clipboard.writeText(code).then(() => {
      const orig = btn.innerHTML;
      btn.innerHTML = '<span>✓ Disalin!</span>';
      setTimeout(() => { btn.innerHTML = orig; }, 1800);
    }).catch(() => {
      alert('Kode Pesanan: ' + code);
    });
  }

  // Biteship Shipping Rates Calculation
  async function calculateShippingRates() {
    const lat = document.getElementById('checkoutBuyerLat')?.value || '';
    const lng = document.getElementById('checkoutBuyerLng')?.value || '';
    const postalCode = document.getElementById('checkoutBuyerPostalCode')?.value.trim() || '';
    const address = document.getElementById('checkoutBuyerAddress')?.value.trim() || '';
    const statusBox = document.getElementById('shippingRatesStatus');
    const btn = document.getElementById('btnCheckShippingRates');
    const btnText = document.getElementById('btnCheckShippingRatesText');
    const btnIcon = document.getElementById('btnCheckShippingRatesIcon');

    if (!postalCode && (!lat || !lng)) {
      if (statusBox) {
        statusBox.style.display = 'block';
        statusBox.style.background = '#fef2f2';
        statusBox.style.color = '#991b1b';
        statusBox.style.border = '1px solid #fecaca';
        statusBox.textContent = 'Silakan isi Kode Pos tujuan atau gunakan tombol "Gunakan Lokasi Saya" di atas untuk cek tarif kurir.';
      }
      return;
    }

    if (statusBox) {
      statusBox.style.display = 'block';
      statusBox.style.background = '#f0f9ff';
      statusBox.style.color = '#0369a1';
      statusBox.style.border = '1px solid #bae6fd';
      statusBox.textContent = 'Sedang mencari tarif kurir real-time dari Biteship...';
    }

    if (btn) {
      btn.disabled = true;
      btn.style.opacity = '0.75';
      if (btnIcon) btnIcon.textContent = '⏳';
      if (btnText) btnText.textContent = 'Mengecek...';
    }

    try {
      const payload = {
        _token: '{{ csrf_token() }}',
        postal_code: postalCode,
        latitude: lat,
        longitude: lng,
        address: address,
      };

      @if($isDirectBuy && $directProduct)
        payload.product_id = '{{ $directProduct->id }}';
        payload.quantity = currentDirectQty;
      @endif

      const res = await fetch("{{ route('store.shipping.rates', $store->slug) }}", {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      const data = await res.json();

      if (!res.ok || !data.success) {
        throw new Error(data.message || 'Gagal mengambil tarif ongkir Biteship.');
      }

      availableRates = data.rates || [];
      renderCouriersList(availableRates);

      if (statusBox) {
        statusBox.style.display = 'block';
        statusBox.style.background = '#f0fdf4';
        statusBox.style.color = '#166534';
        statusBox.style.border = '1px solid #bbf7d0';
        if (availableRates.length === 0) {
          statusBox.style.background = '#fffbeb';
          statusBox.style.color = '#92400e';
          statusBox.style.border = '1px solid #fde68a';
          statusBox.textContent = data.message || 'Belum ada opsi kurir yang tersedia untuk tujuan ini. Silakan pilih Ambil Sendiri / koordinasi via WhatsApp.';
        } else {
          statusBox.textContent = `✓ Ditemukan ${availableRates.length} opsi kurir. Silakan pilih layanan kurir yang diinginkan di bawah.`;
        }
      }

    } catch (err) {
      if (statusBox) {
        statusBox.style.display = 'block';
        statusBox.style.background = '#fef2f2';
        statusBox.style.color = '#991b1b';
        statusBox.style.border = '1px solid #fecaca';
        statusBox.textContent = err.message || 'Gagal menghubungi layanan Biteship.';
      }
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.style.opacity = '1';
        if (btnIcon) btnIcon.textContent = '🚚';
        if (btnText) btnText.textContent = 'Perbarui Tarif';
      }
    }
  }

  function renderCouriersList(rates) {
    const list = document.getElementById('shippingCouriersList');
    if (!list) return;

    let html = `
      <label class="courier-option-card ${selectedShippingFee === 0 ? 'selected' : ''}" id="courier-opt-default" onclick="selectCourierOption(null)">
        <input type="radio" name="courier_radio" value="" ${selectedShippingFee === 0 ? 'checked' : ''} style="accent-color:var(--brand-coral);">
        <div style="flex:1;">
          <div style="font-weight:700;font-size:13.5px;color:#0f172a;">Ambil Sendiri / Ongkir Manual Toko</div>
          <div style="font-size:12px;color:#64748b;">Tanpa ongkir otomatis, koordinasi pengiriman langsung dengan penjual melalui WhatsApp.</div>
        </div>
        <div style="text-align:right;">
          <div style="font-weight:700;font-size:14px;color:#16a34a;">Gratis / Rp 0</div>
        </div>
      </label>
    `;

    rates.forEach((r, idx) => {
      const company = (r.company || '').toLowerCase();
      html += `
        <label class="courier-option-card" id="courier-opt-${idx}" onclick="selectCourierOption(${idx})">
          <input type="radio" name="courier_radio" value="${idx}" style="accent-color:var(--brand-coral);">
          <div style="flex:1;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:2px;flex-wrap:wrap;">
              <span class="courier-badge-tag ${company}">${r.courier_name}</span>
              <strong style="font-size:13.5px;color:#0f172a;">${r.service_name}</strong>
              <span style="font-size:12px;color:#64748b;">• Estimasi: ${r.duration || '-'}</span>
            </div>
            <div style="font-size:12px;color:#64748b;">Layanan ${r.service_type || 'standard'} pengiriman resmi ${r.courier_name}</div>
          </div>
          <div style="text-align:right;">
            <div style="font-weight:700;font-size:14px;color:#0f172a;">${r.price_formatted}</div>
          </div>
        </label>
      `;
    });

    list.innerHTML = html;
  }

  function selectCourierOption(rateIndex) {
    document.querySelectorAll('.courier-option-card').forEach(el => el.classList.remove('selected'));

    const courierInput = document.getElementById('checkoutSelectedCourier');
    const serviceInput = document.getElementById('checkoutSelectedService');
    const feeInput = document.getElementById('checkoutSelectedFee');
    const etdInput = document.getElementById('checkoutSelectedEtd');
    const label = document.getElementById('checkoutShippingLabel');
    const summary = document.getElementById('checkoutShippingFeeSummary');

    if (rateIndex === null || typeof availableRates[rateIndex] === 'undefined') {
      const defaultCard = document.getElementById('courier-opt-default');
      if (defaultCard) defaultCard.classList.add('selected');
      const radio = defaultCard?.querySelector('input[type="radio"]');
      if (radio) radio.checked = true;

      selectedShippingFee = 0;
      if (courierInput) courierInput.value = '';
      if (serviceInput) serviceInput.value = '';
      if (feeInput) feeInput.value = '0';
      if (etdInput) etdInput.value = '';

      if (label) label.textContent = 'Ongkos Kirim';
      if (summary) {
        summary.textContent = 'Rp 0';
        summary.style.color = '#16a34a';
      }
    } else {
      const rate = availableRates[rateIndex];
      const card = document.getElementById(`courier-opt-${rateIndex}`);
      if (card) {
        card.classList.add('selected');
        const radio = card.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
      }

      selectedShippingFee = parseFloat(rate.price) || 0;
      if (courierInput) courierInput.value = rate.courier_name || rate.company;
      if (serviceInput) serviceInput.value = rate.service_name || '';
      if (feeInput) feeInput.value = selectedShippingFee.toString();
      if (etdInput) etdInput.value = rate.duration || '';

      if (label) label.textContent = `Ongkir (${rate.courier_name} ${rate.service_name || ''})`.trim();
      if (summary) {
        summary.textContent = rate.price_formatted;
        summary.style.color = '#0f172a';
      }
    }

    updateGrandTotalDisplay();
  }

  // Form Submit Handler
  async function handleCheckoutFormSubmit(event) {
    event.preventDefault();
    if (isCheckoutSubmitting) return;

    const errorBanner = document.getElementById('checkoutGlobalError');
    const errorText = document.getElementById('checkoutGlobalErrorText');
    const submitBtn = document.getElementById('btnCheckoutSubmit');
    const submitText = document.getElementById('btnCheckoutSubmitText');

    errorBanner.style.display = 'none';

    const name = document.getElementById('checkoutBuyerName').value.trim();
    const phone = document.getElementById('checkoutBuyerPhone').value.trim();
    const address = document.getElementById('checkoutBuyerAddress').value.trim();
    const notes = document.getElementById('checkoutBuyerNotes')?.value.trim() || '';

    if (!name || !phone || !address) {
      errorText.textContent = 'Silakan lengkapi nama, nomor WhatsApp, dan alamat pengiriman Anda.';
      errorBanner.style.display = 'flex';
      errorBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }

    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('buyer_name', name);
    formData.append('buyer_phone', phone);
    formData.append('buyer_address', address);
    formData.append('buyer_notes', notes);
    formData.append('buyer_postal_code', document.getElementById('checkoutBuyerPostalCode')?.value.trim() || '');
    formData.append('buyer_latitude', document.getElementById('checkoutBuyerLat')?.value || '');
    formData.append('buyer_longitude', document.getElementById('checkoutBuyerLng')?.value || '');

    formData.append('shipping_courier', document.getElementById('checkoutSelectedCourier')?.value || '');
    formData.append('shipping_service', document.getElementById('checkoutSelectedService')?.value || '');
    formData.append('shipping_fee', document.getElementById('checkoutSelectedFee')?.value || '0');
    formData.append('shipping_etd', document.getElementById('checkoutSelectedEtd')?.value || '');

    @if($isDirectBuy && $directProduct)
      formData.append('product_id', '{{ $directProduct->id }}');
      formData.append('quantity', currentDirectQty.toString());
    @endif

    if (selectedCheckoutProofFile) {
      formData.append('payment_proof', selectedCheckoutProofFile);
    }

    isCheckoutSubmitting = true;
    submitBtn.disabled = true;
    submitBtn.style.opacity = '0.75';
    submitText.textContent = 'Memproses Pesanan...';

    try {
      const response = await fetch("{{ route('cart.checkout', $store->slug) }}", {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: formData
      });

      const res = await response.json();

      if (!response.ok || !res.success) {
        throw new Error(res.message || 'Terjadi kendala saat memproses pesanan Anda. Silakan coba lagi.');
      }

      // Berhasil: Alihkan tampilan ke Success State
      document.getElementById('checkoutActiveContainer').style.display = 'none';

      const successContainer = document.getElementById('checkoutSuccessContainer');
      document.getElementById('ajaxSuccessOrderCode').textContent = res.order_code;
      document.getElementById('ajaxSuccessTotalPrice').textContent = res.total_price_formatted;
      document.getElementById('ajaxSuccessProofStatus').textContent = res.has_proof ? '✓ Terunggah' : 'Menunggu Konfirmasi';
      
      const copyBtn = document.getElementById('btnCopyAjaxCode');
      if (copyBtn) {
        copyBtn.onclick = function() { copyOrderCode(res.order_code, this); };
      }

      const waBtn = document.getElementById('btnAjaxProceedWhatsApp');
      if (res.whatsapp_url) {
        waBtn.href = res.whatsapp_url;
        waBtn.style.display = 'inline-flex';
        // Buka otomatis ke WhatsApp di tab baru jika browser mengizinkan
        try {
          window.open(res.whatsapp_url, '_blank');
        } catch(e) {}
      } else {
        waBtn.style.display = 'none';
      }

      successContainer.style.display = 'block';
      window.scrollTo({ top: 0, behavior: 'smooth' });

      // Refresh cart drawer badge jika fungsi tersedia
      if (typeof fetchCartSummary === 'function') {
        fetchCartSummary();
      }

    } catch (err) {
      errorText.textContent = err.message || 'Gagal memproses pesanan. Periksa koneksi internet Anda.';
      errorBanner.style.display = 'flex';
      errorBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
      isCheckoutSubmitting = false;
      submitBtn.disabled = false;
      submitBtn.style.opacity = '1';
      submitText.textContent = 'Kirim Pesanan & Konfirmasi WA';
    }
  }

  // Auto trigger rate check jika koordinat atau kode pos sudah tersedia
  document.addEventListener('DOMContentLoaded', function() {
    const postal = document.getElementById('checkoutBuyerPostalCode')?.value;
    const lat = document.getElementById('checkoutBuyerLat')?.value;
    if (postal || lat) {
      calculateShippingRates();
    }
  });
</script>
@endsection

