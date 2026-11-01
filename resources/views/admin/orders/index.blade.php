@extends('layouts.dashboard')
@section('title', 'Pesanan Masuk — ' . $store->name)
@section('content')
<div class="dashboard-page-header">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h1 class="page-title">Pesanan Masuk</h1>
      <p class="page-subtitle">Kelola konfirmasi pesanan pelanggan, verifikasi bukti pembayaran QRIS, dan perbarui status pengiriman.</p>
    </div>
  </div>
</div>

{{-- Metric Cards --}}
<div class="grid grid-4" style="margin-bottom:24px">
  <div class="metric-card" style="padding:20px;border-radius:16px;background:var(--canvas);border:1px solid var(--hairline-soft)">
    <div style="font-size:12px;color:var(--steel);font-weight:600;text-transform:uppercase">Semua Pesanan</div>
    <div style="font-size:28px;font-weight:800;color:var(--ink-deep);margin-top:6px">{{ $statusCounts['all'] }}</div>
  </div>
  <div class="metric-card" style="padding:20px;border-radius:16px;background:#fffbeb;border:1px solid #fde68a">
    <div style="font-size:12px;color:#92400e;font-weight:600;text-transform:uppercase">Perlu Verifikasi</div>
    <div style="font-size:28px;font-weight:800;color:#92400e;margin-top:6px">{{ $statusCounts['pending'] }}</div>
  </div>
  <div class="metric-card" style="padding:20px;border-radius:16px;background:#f0fdf4;border:1px solid #bbf7d0">
    <div style="font-size:12px;color:#166534;font-weight:600;text-transform:uppercase">Sudah Dibayar</div>
    <div style="font-size:28px;font-weight:800;color:#166534;margin-top:6px">{{ $statusCounts['paid'] }}</div>
  </div>
  <div class="metric-card" style="padding:20px;border-radius:16px;background:#f8fafc;border:1px solid var(--hairline-soft)">
    <div style="font-size:12px;color:var(--steel);font-weight:600;text-transform:uppercase">Selesai</div>
    <div style="font-size:28px;font-weight:800;color:var(--primary);margin-top:6px">{{ $statusCounts['completed'] }}</div>
  </div>
</div>

{{-- Filter Toolbar --}}
<div class="panel" style="padding:24px;border-radius:20px;background:var(--canvas);border:1px solid var(--hairline-soft);margin-bottom:24px">
  <div class="toolbar" style="margin-bottom:16px">
    <div class="toolbar-left" style="gap:8px">
      <a class="pill-tab {{ !request('status') ? 'active' : '' }}" href="{{ route('admin.orders.index', $store) }}">Semua ({{ $statusCounts['all'] }})</a>
      <a class="pill-tab {{ request('status') === 'pending' ? 'active' : '' }}" href="{{ route('admin.orders.index', [$store, 'status' => 'pending']) }}">Menunggu ({{ $statusCounts['pending'] }})</a>
      <a class="pill-tab {{ request('status') === 'paid' ? 'active' : '' }}" href="{{ route('admin.orders.index', [$store, 'status' => 'paid']) }}">Dibayar ({{ $statusCounts['paid'] }})</a>
      <a class="pill-tab {{ request('status') === 'completed' ? 'active' : '' }}" href="{{ route('admin.orders.index', [$store, 'status' => 'completed']) }}">Selesai ({{ $statusCounts['completed'] }})</a>
      <a class="pill-tab {{ request('status') === 'cancelled' ? 'active' : '' }}" href="{{ route('admin.orders.index', [$store, 'status' => 'cancelled']) }}">Dibatalkan ({{ $statusCounts['cancelled'] }})</a>
    </div>

    <form method="GET" action="{{ route('admin.orders.index', $store) }}" class="toolbar-right">
      @if(request('status'))
        <input type="hidden" name="status" value="{{ request('status') }}">
      @endif
      <x-search-input placeholder="Cari kode/nama/no WA…" width="240px" />
      <button class="btn btn-primary btn-sm" type="submit">Cari</button>
    </form>
  </div>

  @if($orders->count() === 0)
    <div style="text-align:center;padding:48px 24px;color:var(--steel)">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;opacity:0.6"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
      <div style="font-size:16px;font-weight:600;color:var(--ink-deep)">Belum Ada Pesanan</div>
      <p style="font-size:13.5px;max-width:400px;margin:6px auto 0">Pesanan dari pembeli via QRIS atau katalog akan otomatis muncul di sini lengkap dengan bukti transfer dan alamat.</p>
    </div>
  @else
    <div style="overflow-x:auto">
      <table class="table" style="width:100%;border-collapse:collapse;font-size:13.5px">
        <thead>
          <tr style="border-bottom:1px solid var(--hairline-soft);text-align:left;color:var(--steel)">
            <th style="padding:12px 10px">Kode Pesanan</th>
            <th style="padding:12px 10px">Produk</th>
            <th style="padding:12px 10px">Total Bayar</th>
            <th style="padding:12px 10px">Data Pembeli & Alamat</th>
            <th style="padding:12px 10px">Bukti Transfer</th>
            <th style="padding:12px 10px">Status</th>
            <th style="padding:12px 10px;text-align:right">Aksi Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach($orders as $ord)
            <tr style="border-bottom:1px solid var(--hairline-soft)">
              <td style="padding:12px 10px;vertical-align:top">
                <strong style="color:var(--ink-deep);font-family:monospace;font-size:13px">{{ $ord->order_code }}</strong>
                <div style="font-size:11.5px;color:var(--steel);margin-top:4px">{{ $ord->created_at->format('d M Y H:i') }}</div>
              </td>
              <td style="padding:12px 10px;vertical-align:top">
                <div style="font-weight:600;color:var(--ink-deep)">{{ $ord->product_name }}</div>
                <div style="font-size:12px;color:var(--steel)">{{ $ord->quantity }} unit &times; Rp {{ number_format($ord->product_price, 0, ',', '.') }}</div>
              </td>
              <td style="padding:12px 10px;vertical-align:top">
                <strong style="color:var(--primary);font-size:14px">Rp {{ number_format($ord->total_price, 0, ',', '.') }}</strong>
                <div style="font-size:11px;color:var(--steel);margin-top:2px">{{ strtoupper($ord->payment_method) }}</div>
              </td>
              <td style="padding:12px 10px;vertical-align:top;max-width:240px">
                <div style="font-weight:600;color:var(--ink-deep)">{{ $ord->buyer_name }}</div>
                @php
                  $cleanPhone = preg_replace('/[^0-9]/', '', $ord->buyer_phone);
                  if (str_starts_with($cleanPhone, '0')) $cleanPhone = '62' . substr($cleanPhone, 1);
                  elseif (str_starts_with($cleanPhone, '8')) $cleanPhone = '62' . $cleanPhone;
                @endphp
                <div style="font-size:12px;margin-top:2px">
                  <a href="https://wa.me/{{ $cleanPhone }}?text=Halo%20{{ urlencode($ord->buyer_name) }},%20mengenai%20pesanan%20Anda%20{{ $ord->order_code }}%20di%20{{ urlencode($store->name) }}" target="_blank" rel="noopener" style="color:var(--success);text-decoration:none;font-weight:600">
                    📱 {{ $ord->buyer_phone }}
                  </a>
                </div>
                <div style="font-size:12px;color:var(--charcoal);margin-top:4px;line-height:1.4">
                  📍 {{ $ord->buyer_address }}
                </div>
                @if($ord->buyer_notes)
                  <div style="font-size:11.5px;color:var(--steel);margin-top:3px;font-style:italic">
                    Catatan: {{ $ord->buyer_notes }}
                  </div>
                @endif
              </td>
              <td style="padding:12px 10px;vertical-align:top">
                @if($ord->payment_proof_path)
                  <a href="{{ $ord->paymentProofUrl() }}" target="_blank" rel="noopener" style="display:inline-block;border:1px solid var(--hairline-soft);border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06)" title="Klik untuk lihat bukti transfer">
                    <img src="{{ $ord->paymentProofUrl() }}" alt="Bukti Transfer" style="width:52px;height:52px;object-fit:cover;display:block">
                  </a>
                  <div style="font-size:11px;color:var(--success);margin-top:2px">Ada Bukti</div>
                @else
                  <span style="font-size:12px;color:var(--steel)">Tanpa Bukti</span>
                @endif
              </td>
              <td style="padding:12px 10px;vertical-align:top">
                <span class="badge {{ $ord->statusBadgeClass() }}">
                  {{ $ord->statusLabel() }}
                </span>
              </td>
              <td style="padding:12px 10px;vertical-align:top;text-align:right">
                <form method="POST" action="{{ route('admin.orders.status', [$store, $ord]) }}" style="display:inline-flex;gap:6px">
                  @csrf
                  @method('PATCH')
                  <select name="status" class="select" style="height:32px;padding:0 8px;font-size:12px" onchange="this.form.submit()">
                    <option value="pending" {{ $ord->status === 'pending' ? 'selected' : '' }}>Menunggu</option>
                    <option value="paid" {{ $ord->status === 'paid' ? 'selected' : '' }}>Dibayar</option>
                    <option value="processing" {{ $ord->status === 'processing' ? 'selected' : '' }}>Diproses</option>
                    <option value="completed" {{ $ord->status === 'completed' ? 'selected' : '' }}>Selesai</option>
                    <option value="cancelled" {{ $ord->status === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                  </select>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div style="margin-top:20px">
      {{ $orders->links() }}
    </div>
  @endif
</div>
@endsection

