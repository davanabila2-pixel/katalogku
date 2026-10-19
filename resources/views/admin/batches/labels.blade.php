<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Label QR Code - Batch {{ $batch->batch_code }} - {{ $store->name }}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #f1f5f9;
      --card-bg: #ffffff;
      --text-main: #0f172a;
      --text-muted: #64748b;
      --primary: #0064e0;
      --border: #cbd5e1;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
      background: var(--bg);
      color: var(--text-main);
      padding: 24px;
      line-height: 1.4;
    }

    .no-print-bar {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 16px 20px;
      margin-bottom: 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 16px;
      font-size: 14px;
      font-weight: 600;
      border-radius: 8px;
      text-decoration: none;
      cursor: pointer;
      border: none;
      transition: all 0.15s ease;
    }

    .btn-primary {
      background: var(--primary);
      color: #fff;
    }

    .btn-primary:hover {
      background: #0050b3;
    }

    .btn-ghost {
      background: transparent;
      color: var(--text-main);
      border: 1px solid var(--border);
    }

    .btn-ghost:hover {
      background: #f8fafc;
    }

    .mode-switch {
      display: flex;
      gap: 8px;
      align-items: center;
      background: #f1f5f9;
      padding: 4px;
      border-radius: 8px;
    }

    .mode-tab {
      padding: 6px 12px;
      font-size: 13px;
      font-weight: 600;
      border-radius: 6px;
      text-decoration: none;
      color: var(--text-muted);
    }

    .mode-tab.active {
      background: #fff;
      color: var(--primary);
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .labels-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 16px;
    }

    .label-card {
      background: #fff;
      border: 1.5px solid #0f172a;
      border-radius: 10px;
      padding: 16px;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      page-break-inside: avoid;
      break-inside: avoid;
    }

    .label-header {
      width: 100%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px dashed #cbd5e1;
      padding-bottom: 8px;
      margin-bottom: 10px;
      font-size: 11px;
      font-weight: 700;
      color: var(--text-muted);
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }

    .label-title {
      font-size: 15px;
      font-weight: 700;
      color: #0f172a;
      margin-bottom: 10px;
      line-height: 1.25;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      min-height: 38px;
    }

    .label-qr {
      margin: 4px 0 10px;
      width: 135px;
      height: 135px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .label-qr img {
      width: 135px;
      height: 135px;
      display: block;
    }

    .label-barcode-text {
      font-family: 'JetBrains Mono', monospace;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 1px;
      color: #1e293b;
      margin-bottom: 4px;
    }

    .label-price {
      font-size: 16px;
      font-weight: 700;
      color: var(--primary);
      margin-bottom: 4px;
    }

    .label-footer {
      font-size: 10px;
      color: var(--text-muted);
      margin-top: 6px;
    }

    @media print {
      body {
        background: #fff;
        padding: 0;
      }

      .no-print {
        display: none !important;
      }

      .labels-grid {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 10px !important;
      }

      .label-card {
        border: 1px solid #000 !important;
        border-radius: 6px !important;
        padding: 12px 10px !important;
      }

      .label-price {
        color: #000 !important;
      }

      @page {
        size: A4;
        margin: 10mm;
      }
    }
  </style>
</head>
<body>

  <div class="no-print no-print-bar">
    <div>
      <a class="btn btn-ghost" href="{{ route('admin.batches.show', [$store, $batch]) }}" style="margin-bottom:4px">
        ← Kembali ke Detail Batch
      </a>
      <h1 style="font-size:18px;margin-top:4px">Lembar Stiker Label QR Code</h1>
      <p style="font-size:13px;color:var(--text-muted)">Batch: <strong>#{{ $batch->batch_code }}</strong> ({{ $batch->purchase_date->format('d M Y') }}) · Toko: <strong>{{ $store->name }}</strong></p>
    </div>

    <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
      <div class="mode-switch">
        <a href="{{ route('admin.batches.labels', [$store, $batch, 'mode' => 'qty']) }}" class="mode-tab {{ $mode === 'qty' ? 'active' : '' }}">
          Sesuai Qty Masuk (Stiker Fisik)
        </a>
        <a href="{{ route('admin.batches.labels', [$store, $batch, 'mode' => 'unique']) }}" class="mode-tab {{ $mode === 'unique' ? 'active' : '' }}">
          1 Per Produk Unik
        </a>
      </div>

      <button class="btn btn-primary" onclick="window.print()">
        🖨️ Cetak / Print Sekarang
      </button>
    </div>
  </div>

  @php
    $labels = [];
    foreach ($batch->items as $item) {
      if (!$item->product) continue;
      $count = ($mode === 'qty') ? max(1, $item->quantity) : 1;
      for ($i = 0; $i < $count; $i++) {
        $labels[] = [
          'product' => $item->product,
          'item' => $item,
          'index' => $i + 1,
          'total' => $count,
        ];
      }
    }
  @endphp

  @if(count($labels) > 0)
    <div class="labels-grid">
      @foreach($labels as $entry)
        @php
          $prod = $entry['product'];
        @endphp
        <div class="label-card">
          <div class="label-header">
            <span>{{ Str::limit($store->name, 18) }}</span>
            <span>#{{ $batch->batch_code }} @if($mode === 'qty')({{ $entry['index'] }}/{{ $entry['total'] }})@endif</span>
          </div>

          <div class="label-title">
            {{ $prod->name }}
          </div>

          <div class="label-qr">
            <img src="{{ $prod->qrCodeDataUri() }}" alt="QR {{ $prod->name }}">
          </div>

          <div class="label-barcode-text">
            * {{ $prod->barcode ?? 'PROD-'.$prod->id }} *
          </div>

          <div class="label-price">
            Rp {{ number_format($prod->selling_price, 0, ',', '.') }}
          </div>

          <div class="label-footer">
            Scan untuk detail produk & edit admin
          </div>
        </div>
      @endforeach
    </div>
  @else
    <div style="text-align:center;padding:40px;background:#fff;border-radius:12px;border:1px solid var(--border)">
      <h3>Tidak ada produk dalam batch ini</h3>
    </div>
  @endif

</body>
</html>
