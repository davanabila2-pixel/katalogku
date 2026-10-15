@extends('layouts.dashboard')
@section('title', 'Batch '.$batch->batch_code)
@section('subtitle', $store->name)
@section('nav')
<a href="{{ route('admin.dashboard', $store) }}">Dashboard</a>
<a class="active" href="{{ route('admin.batches.index', $store) }}">Batch Pembelian</a>
<a href="{{ route('admin.stock.index', $store) }}">Stok</a>
@endsection
@section('content')
<div class="panel">
  <p style="font-size:14px">Tanggal: {{ $batch->purchase_date->format('d M Y') }} · Supplier: {{ $batch->supplier_name ?? '—' }} · Dibuat oleh: {{ $batch->creator->name ?? '—' }} · Status: <span class="badge {{ $batch->status==='active'?'badge-success':'badge-neutral' }}">{{ $batch->status }}</span></p>
  @if($batch->notes)<p>{{ $batch->notes }}</p>@endif
  <table class="table">
  <thead><tr><th>Produk</th><th>Qty</th><th>Harga Satuan</th><th>Alokasi Biaya</th><th>Subtotal</th></tr></thead>
  <tbody>
  @foreach($batch->items as $item)
  <tr><td>{{ $item->product->name ?? '#'.$item->product_id }}</td><td>{{ $item->quantity }}</td><td>Rp {{ number_format($item->unit_purchase_price, 0, ',', '.') }}</td><td>Rp {{ number_format($item->allocated_additional_cost, 0, ',', '.') }}</td><td>Rp {{ number_format($item->totalWithAllocation(), 0, ',', '.') }}</td></tr>
  @endforeach
  </tbody>
  </table>
  <p style="text-align:right">Total item: <strong>Rp {{ number_format($batch->itemsTotal(), 0, ',', '.') }}</strong> ·
  Biaya tambahan: <strong>Rp {{ number_format($batch->additional_cost, 0, ',', '.') }}</strong> ·
  Total batch: <strong>Rp {{ number_format($batch->totalCost(), 0, ',', '.') }}</strong></p>
  @if(!$batch->isCancelled())
  <form method="POST" action="{{ route('admin.batches.cancel', [$store, $batch]) }}" onsubmit="return confirm('Batalkan batch? Stok akan dikembalikan.')" style="display:flex;gap:8px">@csrf<input class="input" type="text" name="reason" placeholder="Alasan pembatalan (opsional)"><button class="btn btn-secondary btn-sm" type="submit">Batalkan Batch</button></form>
  @endif
</div>
@endsection
