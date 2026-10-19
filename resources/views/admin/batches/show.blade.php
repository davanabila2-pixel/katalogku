@extends('layouts.dashboard')
@section('title', 'Batch '.$batch->batch_code)
@section('subtitle', $store->name)
@section('content')
<div class="panel">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:16px">
    <div>
      <p style="font-size:14px;margin:0 0 6px">Tanggal: <strong>{{ $batch->purchase_date->format('d M Y') }}</strong> · Supplier: <strong>{{ $batch->supplier_name ?? '—' }}</strong> · Dibuat oleh: <strong>{{ $batch->creator->name ?? '—' }}</strong> · Status: <span class="badge {{ $batch->status==='active'?'badge-success':'badge-neutral' }}">{{ $batch->status }}</span></p>
      @if($batch->notes)<p style="margin:0;font-size:13px;color:var(--steel)">Catatan: {{ $batch->notes }}</p>@endif
    </div>

    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <a class="btn btn-primary btn-sm" href="{{ route('admin.batches.labels', [$store, $batch]) }}" target="_blank">
        🖨️ Cetak Lembar Label QR
      </a>
      <form method="POST" action="{{ route('admin.batches.generate-qr', [$store, $batch]) }}" style="margin:0">
        @csrf
        <button class="btn btn-secondary btn-sm" type="submit" onclick="return confirm('Generate / Perbarui QR code dan label Google Drive untuk seluruh produk dalam batch ini?')">
          🔄 Generate / Simpan QR ke Drive
        </button>
      </form>
    </div>
  </div>

  <table class="table">
  <thead>
    <tr>
      <th>Produk</th>
      <th>QR Code & Barcode</th>
      <th>Qty Masuk</th>
      <th>Harga Beli Satuan</th>
      <th>Alokasi Biaya</th>
      <th>Subtotal</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
  @foreach($batch->items as $item)
  <tr>
    <td>
      <strong>{{ $item->product->name ?? '#'.$item->product_id }}</strong>
      @if($item->product && $item->product->sku)
        <br><small style="color:var(--steel)">SKU: {{ $item->product->sku }}</small>
      @endif
    </td>
    <td>
      @if($item->product)
        <div style="display:flex;align-items:center;gap:10px">
          <a href="{{ $item->product->barcodeUrl() ?? $item->product->scanUrl() }}" target="_blank" title="Klik untuk scan / lihat label">
            <img src="{{ $item->product->qrCodeDataUri() }}" alt="QR {{ $item->product->name }}" style="width:48px;height:48px;border:1px solid #cbd5e1;border-radius:4px;padding:2px;background:#fff;display:block">
          </a>
          <div>
            <span style="font-family:monospace;font-size:11px;background:#f1f5f9;color:#334155;padding:2px 6px;border-radius:4px;border:1px solid #cbd5e1;display:inline-block">
              {{ $item->product->barcode ?? '—' }}
            </span>
            <small style="color:var(--steel);display:block;margin-top:2px">
              Jual: Rp {{ number_format($item->product->selling_price, 0, ',', '.') }}
            </small>
          </div>
        </div>
      @else
        <span style="color:var(--steel)">—</span>
      @endif
    </td>
    <td><strong>{{ $item->quantity }}</strong> unit</td>
    <td>Rp {{ number_format($item->unit_purchase_price, 0, ',', '.') }}</td>
    <td>Rp {{ number_format($item->allocated_additional_cost, 0, ',', '.') }}</td>
    <td>Rp {{ number_format($item->totalWithAllocation(), 0, ',', '.') }}</td>
    <td style="white-space:nowrap">
      @if($item->product)
        <a href="{{ $item->product->scanUrl() }}" target="_blank" title="Uji Scan">Scan</a>
        @if($item->product->barcodeUrl())
          · <a href="{{ $item->product->barcodeUrl() }}" target="_blank" download title="Download Label Google Drive">Label</a>
        @endif
        · <a href="{{ route('admin.products.edit', [$store, $item->product]) }}">Edit</a>
      @endif
    </td>
  </tr>
  @endforeach
  </tbody>
  </table>

  <p style="text-align:right;margin-top:16px">Total item: <strong>Rp {{ number_format($batch->itemsTotal(), 0, ',', '.') }}</strong> ·
  Biaya tambahan: <strong>Rp {{ number_format($batch->additional_cost, 0, ',', '.') }}</strong> ·
  Total batch: <strong>Rp {{ number_format($batch->totalCost(), 0, ',', '.') }}</strong></p>

  @if(!$batch->isCancelled())
  <form method="POST" action="{{ route('admin.batches.cancel', [$store, $batch]) }}" onsubmit="return confirm('Batalkan batch? Stok akan dikembalikan.')" style="display:flex;gap:8px;margin-top:20px;padding-top:16px;border-top:1px solid var(--hairline-soft)">@csrf<input class="input" type="text" name="reason" placeholder="Alasan pembatalan (opsional)"><button class="btn btn-secondary btn-sm" type="submit">Batalkan Batch</button></form>
  @endif
</div>
@endsection
