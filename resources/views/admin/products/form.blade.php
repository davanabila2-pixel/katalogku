@extends('layouts.dashboard')
@section('title', ($product->exists ? 'Edit' : 'Tambah').' Produk')
@section('subtitle', $store->name)
@section('content')
@if($product->exists && isset($hpp))
<div class="panel"><h2>Biaya & Margin (internal)</h2>
<p style="font-size:14px">
  @if($product->isBundle())
    <span class="badge" style="background:#0064e0;color:#fff">Produk Setelan (Bundle)</span><br><br>
    HPP Gabungan Komponen: <strong>{{ $hpp !== null ? 'Rp '.number_format($hpp, 0, ',', '.') : '—' }}</strong> ·
    Estimasi margin: <strong>{{ $margin !== null ? 'Rp '.number_format($margin, 0, ',', '.') : '—' }}</strong> ·
    Stok otomatis: <strong>{{ $product->stock }} paket</strong>
    <br><small style="color:var(--steel)">Stok paket dihitung otomatis secara real-time dari stok terendah produk komponen penyusunnya dan tidak dapat diubah manual agar tidak terjadi kerancuan stok.</small>
  @else
    HPP (rata-rata tertimbang): <strong>{{ $hpp !== null ? 'Rp '.number_format($hpp, 0, ',', '.') : '— (belum ada batch)' }}</strong> ·
    Estimasi margin: <strong>{{ $margin !== null ? 'Rp '.number_format($margin, 0, ',', '.') : '—' }}</strong> ·
    Stok saat ini: <strong>{{ $product->stock }}</strong>
  @endif
</p>
@if(!$product->isBundle())
<form method="POST" action="{{ route('admin.products.adjust', [$store, $product]) }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:end">@csrf
  <div class="field" style="margin:0"><label>Koreksi (+/-)</label><input class="input" style="width:120px" type="number" name="quantity" required></div>
  <div class="field" style="margin:0;flex:1;min-width:200px"><label>Alasan (wajib, tercatat di audit)</label><input class="input" type="text" name="reason" required minlength="5" placeholder="cth: stok opname selisih 2 pcs"></div>
  <button class="btn btn-secondary btn-sm" type="submit">Koreksi Stok</button>
</form>
@endif
</div>
@endif

@if($product->exists)
<div class="panel" style="border-left: 4px solid #0064e0; background: #f8fafc; margin-bottom: 20px">
  <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px">
    <div style="flex:1; min-width:280px">
      <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px">
        <h2 style="margin:0; font-size:18px">Identitas Barcode & QR Label</h2>
        <span class="badge" style="background:#e0f2fe; color:#0369a1; font-weight:600">Google Drive Storage</span>
      </div>
      
      <p style="font-size:13px; color:var(--steel); margin:0 0 12px; line-height:1.5">
        Label barcode ini terintegrasi langsung dengan penyimpanan cloud <strong>Google Drive</strong> toko.
        <br>
        • <strong>Scan sebagai Admin:</strong> Jika di-scan oleh admin toko atau superadmin yang sedang login, browser akan langsung membuka halaman edit produk ini.
        <br>
        • <strong>Scan sebagai Pelanggan:</strong> Jika di-scan oleh pembeli umum, akan langsung membuka halaman katalog produk toko.
      </p>

      <div style="display:flex; flex-direction:column; gap:6px; font-size:13px; margin-bottom:14px">
        <div>Kode Barcode: <strong style="font-family:monospace; font-size:15px; color:var(--ink-deep); background:#e2e8f0; padding:2px 8px; border-radius:4px">{{ $product->barcode ?? '—' }}</strong></div>
        <div>URL Scan: <a href="{{ $product->scanUrl() }}" target="_blank" style="word-break:break-all">{{ $product->scanUrl() }}</a></div>
      </div>

      <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center">
        @if($product->barcodeUrl())
          <a class="btn btn-secondary btn-sm" href="{{ $product->barcodeUrl() }}" target="_blank" download>
            📥 Download / Cetak Label
          </a>
          <a class="btn btn-secondary btn-sm" href="{{ $product->scanUrl() }}" target="_blank">
            🔍 Uji Scan Langsung
          </a>
        @endif
        <form method="POST" action="{{ route('admin.products.barcode.regenerate', [$store, $product]) }}" style="margin:0">
          @csrf
          <button class="btn btn-ghost btn-sm" type="submit" onclick="return confirm('Generate ulang label barcode dan simpan ke Google Drive?')">
            🔄 {{ $product->barcodeUrl() ? 'Generate Ulang' : 'Generate Barcode' }}
          </button>
        </form>
      </div>
    </div>

    @if($product->barcodeUrl())
      <div style="text-align:center; background:#fff; padding:10px; border-radius:var(--r-lg); border:1px solid #cbd5e1; box-shadow:0 2px 8px rgba(0,0,0,0.06)">
        <a href="{{ $product->barcodeUrl() }}" target="_blank" title="Klik untuk memperbesar">
          <img src="{{ $product->barcodeUrl() }}" alt="Barcode {{ $product->name }}" style="max-height:190px; width:auto; display:block; border-radius:4px">
        </a>
        <small style="color:var(--steel); font-size:11px; margin-top:4px; display:block">Tersimpan di Google Drive</small>
      </div>
    @else
      <div style="background:#fff3cd; color:#856404; padding:14px; border-radius:var(--r-lg); font-size:13px; max-width:240px">
        ⚠️ Gambar barcode belum tersimpan di Google Drive. Klik tombol <strong>Generate Barcode</strong> untuk membuat dan menyimpannya.
      </div>
    @endif
  </div>
</div>
@endif

<div class="panel form-card">
<form method="POST" action="{{ $product->exists ? route('admin.products.update', [$store, $product]) : route('admin.products.store', $store) }}" enctype="multipart/form-data">
@csrf @if($product->exists)@method('PUT')@endif

  <div class="field">
    <label>Tipe Produk</label>
    <div style="display:flex;gap:20px;align-items:center;margin-top:6px;padding:12px;background:var(--surface-soft);border-radius:var(--r-lg)">
      <label style="font-weight:normal;cursor:pointer;display:flex;align-items:center;gap:6px">
        <input type="radio" name="is_bundle" value="0" {{ old('is_bundle', $product->is_bundle ? '1' : '0') === '0' ? 'checked' : '' }} onchange="toggleBundleSection(false)">
        <span>Produk Satuan Biasa</span>
      </label>
      <label style="font-weight:normal;cursor:pointer;display:flex;align-items:center;gap:6px">
        <input type="radio" name="is_bundle" value="1" {{ old('is_bundle', $product->is_bundle ? '1' : '0') === '1' ? 'checked' : '' }} onchange="toggleBundleSection(true)">
        <strong>Produk Setelan / Paket (Bundle)</strong>
      </label>
    </div>
  </div>

  <div id="single-product-visibility" style="display: {{ old('is_bundle', $product->is_bundle ? '1' : '0') === '0' ? 'block' : 'none' }}; margin-bottom: 20px; padding: 14px 16px; background: #fff; border: 1px solid var(--hairline-soft); border-radius: var(--r-lg)">
    <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;margin:0">
      <input type="checkbox" name="is_hidden_from_catalog" value="1" {{ old('is_hidden_from_catalog', $product->is_hidden_from_catalog) ? 'checked' : '' }} style="margin-top:3px;width:16px;height:16px">
      <div>
        <strong style="font-size:14px;color:var(--ink-deep)">Sembunyikan produk satuan ini dari katalog publik</strong>
        <div style="font-size:13px;color:var(--steel);margin-top:2px">
          Gunakan opsi ini jika produk ini hanya ingin dijual sebagai bagian dari Paket Setelan (One Set) dan tidak ingin ditampilkan terpisah di etalase toko. Stok produk tetap aktif dan dihitung otomatis untuk paket setelan.
        </div>
      </div>
    </label>
  </div>

  <div id="bundle-container" style="display: {{ old('is_bundle', $product->is_bundle ? '1' : '0') === '1' ? 'block' : 'none' }}; border: 1.5px solid #0064e0; border-radius: var(--r-xl); padding: 18px; margin-bottom: 24px; background: #f8fbff">
    <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px">
      <h3 style="margin:0;font-size:16px;color:#004085">Pilih Komponen Isi Paket (Minimal 2 Produk)</h3>
      <span class="badge" style="background:#0064e0;color:#fff">Anti-Rancu Stok</span>
    </div>
    <p style="font-size:13px;color:var(--steel);margin:0 0 16px">
      Pilih produk yang sudah ada (misal: 1 Atasan + 1 Bawahan). Stok paket akan otomatis dihitung dari stok terendah produk komponen.
    </p>

    @if(isset($storeProducts) && $storeProducts->count() > 0)
      @php
        $selectedBundles = old('bundle_items', $product->exists ? $product->bundleItems->mapWithKeys(fn($item) => [$item->id => ['product_id' => $item->id, 'quantity' => $item->pivot->quantity]])->all() : []);
      @endphp
      <div style="display:flex;flex-direction:column;gap:8px;max-height:300px;overflow-y:auto;padding-right:4px">
        @foreach($storeProducts as $sp)
          @php
            $isChecked = isset($selectedBundles[$sp->id]) || in_array($sp->id, array_column((array)$selectedBundles, 'product_id'));
            $itemQty = 1;
            if (isset($selectedBundles[$sp->id]['quantity'])) {
              $itemQty = $selectedBundles[$sp->id]['quantity'];
            } else {
              foreach ((array)$selectedBundles as $sb) {
                if (($sb['product_id'] ?? null) == $sp->id) {
                  $itemQty = $sb['quantity'] ?? 1;
                  break;
                }
              }
            }
          @endphp
          <div class="bundle-item-row" style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:#fff;border-radius:8px;border:1px solid var(--hairline-soft)" data-price="{{ $sp->selling_price }}" data-stock="{{ $sp->stock }}">
            <input type="checkbox" name="bundle_items[{{ $sp->id }}][product_id]" value="{{ $sp->id }}" id="comp_{{ $sp->id }}" class="bundle-checkbox" {{ $isChecked ? 'checked' : '' }} onchange="calculateBundleEstimate()">
            <label for="comp_{{ $sp->id }}" style="flex:1;margin:0;cursor:pointer;font-size:14px">
              <strong>{{ $sp->name }}</strong>
              <span style="color:var(--steel);font-size:13px"> · Rp {{ number_format($sp->selling_price, 0, ',', '.') }} · Stok fisik: <strong>{{ $sp->stock }}</strong></span>
            </label>
            <div style="display:flex;align-items:center;gap:6px">
              <span style="font-size:12px;color:var(--steel)">Jumlah/set:</span>
              <input type="number" class="input bundle-qty-input" name="bundle_items[{{ $sp->id }}][quantity]" value="{{ $itemQty }}" min="1" style="width:65px;height:32px;padding:4px 8px;font-size:13px" onchange="calculateBundleEstimate()" oninput="calculateBundleEstimate()">
            </div>
          </div>
        @endforeach
      </div>

      <div id="bundle-calc-box" style="margin-top:14px;padding:12px;background:#e7f3ff;border-radius:8px;font-size:13px;color:#004085;line-height:1.6">
        💡 <em>Centang minimal 2 produk di atas untuk melihat kalkulasi otomatis total harga normal & estimasi stok setelan.</em>
      </div>
    @else
      <div class="alert alert-error" style="margin:0;font-size:13px">
        Belum ada produk satuan di toko ini. Buat produk atasan dan bawahan terlebih dahulu sebelum membuat setelan.
      </div>
    @endif
  </div>

  <div class="field"><label>Nama produk</label><input class="input" type="text" name="name" value="{{ old('name', $product->name) }}" required placeholder="cth: Setelan Casual Vintage (Kaos + Celana)"></div>
  <div class="form-row">
    <div class="field"><label>Slug (opsional)</label><input class="input" type="text" name="slug" value="{{ old('slug', $product->slug) }}"></div>
    <div class="field"><label>SKU (opsional)</label><input class="input" type="text" name="sku" value="{{ old('sku', $product->sku) }}"></div>
  </div>
  <div class="field"><label>Kategori</label>
    <select class="select" name="category_id"><option value="">— Tanpa kategori —</option>
    @foreach($categories as $c)<option value="{{ $c->id }}" {{ (string)old('category_id', $product->category_id) === (string)$c->id ? 'selected' : '' }}>{{ $c->parent->name ?? '' }}{{ $c->parent ? ' › ' : '' }}{{ $c->name }}{{ $c->is_active ? '' : ' (nonaktif)' }}</option>@endforeach</select></div>
  <div class="field"><label>Deskripsi</label><textarea class="textarea" name="description">{{ old('description', $product->description) }}</textarea></div>
  <div class="form-row">
    <div class="field">
      <label>Harga jual (Rp)</label>
      <input class="input" type="number" id="selling_price_input" name="selling_price" value="{{ old('selling_price', $product->selling_price) }}" min="0" step="0.01" required>
      <small style="color:var(--steel);display:block;margin-top:4px" id="bundle-price-hint"></small>
    </div>
    <div class="field"><label>Status publikasi</label><select class="select" name="status"><option value="draft" {{ old('status', $product->status) === 'draft' ? 'selected' : '' }}>Draft</option><option value="active" {{ old('status', $product->status) === 'active' ? 'selected' : '' }}>Aktif (tampil publik)</option><option value="inactive" {{ old('status', $product->status) === 'inactive' ? 'selected' : '' }}>Nonaktif</option></select></div>
  </div>
  <div class="field">
    <label>Foto Utama Produk (Cover)</label>
    @if($product->image_path)
      <div style="margin-bottom:8px;display:flex;align-items:center;gap:12px">
        <img src="{{ $product->imageUrl() }}" alt="Cover" style="width:72px;height:72px;object-fit:cover;border-radius:8px;border:1px solid var(--hairline-soft)">
        <span style="font-size:12px;color:var(--steel)">Foto utama saat ini</span>
      </div>
    @endif
    <input type="file" name="image" accept="image/*">
    <small style="color:var(--steel);display:block;margin-top:4px">Format: JPG, PNG, WebP (maks. 5MB). Digunakan sebagai tampilan depan katalog.</small>
  </div>

  <div class="field" style="margin-top:16px">
    <label>Galeri Foto Tambahan (Bisa Pilih Banyak Foto Sekaligus)</label>
    @if($product->exists && $product->images->count() > 0)
      <div style="margin-bottom:12px">
        <div style="font-size:12.5px;font-weight:600;color:var(--ink-deep);margin-bottom:8px">Foto Galeri Tersimpan ({{ $product->images->count() }}):</div>
        <div style="display:flex;flex-wrap:wrap;gap:10px" id="existingGalleryList">
          @foreach($product->images as $galImg)
            <div style="position:relative;width:80px;height:80px;border-radius:8px;overflow:hidden;border:1px solid var(--hairline-soft)" id="gal-img-{{ $galImg->id }}">
              <img src="{{ $galImg->imageUrl() }}" alt="Galeri" style="width:100%;height:100%;object-fit:cover">
              <button type="button" onclick="deleteProductGalleryImage({{ $galImg->id }})" style="position:absolute;top:3px;right:3px;background:rgba(239,68,68,0.9);color:#fff;border:none;border-radius:50%;width:22px;height:22px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:12px;font-weight:bold" title="Hapus foto ini">✕</button>
            </div>
          @endforeach
        </div>
      </div>
    @endif
    <input type="file" name="gallery_images[]" multiple accept="image/*">
    <small style="color:var(--steel);display:block;margin-top:4px">Pilih 1 atau lebih foto tambahan untuk galeri produk. Pengunjung dapat melihat seluruh foto dalam mode layar penuh.</small>
  </div>
  <button class="btn btn-buy" type="submit">Simpan</button>
  <a class="btn btn-ghost" href="{{ route('admin.products.index', $store) }}">Batal</a>
</form>
</div>

<script>
function toggleBundleSection(isBundle) {
  var container = document.getElementById('bundle-container');
  var singleVisibility = document.getElementById('single-product-visibility');
  if (container) {
    container.style.display = isBundle ? 'block' : 'none';
    calculateBundleEstimate();
  }
  if (singleVisibility) {
    singleVisibility.style.display = isBundle ? 'none' : 'block';
  }
}

function calculateBundleEstimate() {
  var isBundle = document.querySelector('input[name="is_bundle"]:checked');
  if (!isBundle || isBundle.value !== '1') {
    document.getElementById('bundle-price-hint').innerText = '';
    return;
  }

  var rows = document.querySelectorAll('.bundle-item-row');
  var totalOriginalPrice = 0;
  var minStock = null;
  var checkedCount = 0;

  rows.forEach(function(row) {
    var checkbox = row.querySelector('.bundle-checkbox');
    var qtyInput = row.querySelector('.bundle-qty-input');
    if (checkbox && checkbox.checked) {
      checkedCount++;
      var price = parseFloat(row.getAttribute('data-price')) || 0;
      var stock = parseInt(row.getAttribute('data-stock')) || 0;
      var qty = parseInt(qtyInput ? qtyInput.value : 1) || 1;

      totalOriginalPrice += (price * qty);
      var possibleSets = Math.floor(stock / qty);
      if (minStock === null || possibleSets < minStock) {
        minStock = possibleSets;
      }
    }
  });

  var calcBox = document.getElementById('bundle-calc-box');
  var priceHint = document.getElementById('bundle-price-hint');

  if (checkedCount < 2) {
    if (calcBox) {
      calcBox.innerHTML = '⚠️ <strong>Pilih minimal 2 produk</strong> agar setelan dapat dibentuk.';
      calcBox.style.background = '#fff3cd';
      calcBox.style.color = '#856404';
    }
    if (priceHint) priceHint.innerText = '';
  } else {
    if (calcBox) {
      calcBox.innerHTML = '✅ <strong>Kalkulasi Paket:</strong><br>' +
        '• Total Harga Normal Beli Satuan: <strong>Rp ' + totalOriginalPrice.toLocaleString('id-ID') + '</strong><br>' +
        '• Estimasi Stok Paket Otomatis: <strong>' + (minStock !== null ? minStock : 0) + ' paket</strong> (mengikuti stok terendah komponen).';
      calcBox.style.background = '#e7f3ff';
      calcBox.style.color = '#004085';
    }
    if (priceHint) {
      priceHint.innerText = '💡 Total harga normal jika beli terpisah: Rp ' + totalOriginalPrice.toLocaleString('id-ID');
    }
  }
}

document.addEventListener('DOMContentLoaded', function() {
  calculateBundleEstimate();
});

function deleteProductGalleryImage(imgId) {
  if (!confirm('Hapus foto ini dari galeri produk?')) return;

  var token = document.querySelector('input[name="_token"]')?.value || '';
  var storeId = '{{ $store->id }}';
  var productId = '{{ $product->id }}';

  fetch('/admin/' + storeId + '/products/' + productId + '/images/' + imgId, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': token,
      'Accept': 'application/json'
    }
  })
  .then(function(res) { return res.json(); })
  .then(function(data) {
    if (data && data.success) {
      var el = document.getElementById('gal-img-' + imgId);
      if (el) el.remove();
    } else {
      alert('Gagal menghapus foto.');
    }
  })
  .catch(function() {
    alert('Terjadi kesalahan jaringan.');
  });
}
</script>
@endsection
