@extends('layouts.dashboard')
@section('title', 'Buat Batch Pembelian')
@section('subtitle', $store->name.' — total = Σ(qty × harga) + biaya tambahan')
@section('content')
<div class="panel form-card" style="max-width:800px">
<form method="POST" action="{{ route('admin.batches.store', $store) }}">@csrf
  <div class="form-row">
    <div class="field"><label>Kode batch (unik)</label><input class="input" type="text" name="batch_code" value="{{ old('batch_code', 'BATCH-'.date('Ymd-His')) }}" required></div>
    <div class="field"><label>Tanggal pembelian</label><input class="input" type="date" name="purchase_date" value="{{ old('purchase_date', date('Y-m-d')) }}" required></div>
  </div>
  <div class="form-row">
    <div class="field"><label>Supplier (opsional)</label><input class="input" type="text" name="supplier_name" value="{{ old('supplier_name') }}"></div>
    <div class="field"><label>Biaya tambahan (Rp, dibagi proporsional)</label><input class="input" type="number" name="additional_cost" value="{{ old('additional_cost', 0) }}" min="0" step="0.01"></div>
  </div>
  <div class="field"><label>Catatan</label><textarea class="textarea" name="notes">{{ old('notes') }}</textarea></div>
  <h3>Item (produk harus milik toko ini)</h3>
  <div id="items">
    @for($i = 0; $i < 3; $i++)
    <div class="form-row item-row">
      <div class="field"><label>Produk</label><select class="select" name="items[{{ $i }}][product_id]"><option value="">— pilih —</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}{{ $p->sku ? " ({$p->sku})" : '' }}</option>@endforeach</select></div>
      <div class="field"><label>Qty</label><input class="input" type="number" name="items[{{ $i }}][quantity]" min="1"></div>
      <div class="field"><label>Harga beli/satuan</label><input class="input" type="number" name="items[{{ $i }}][unit_purchase_price]" min="0" step="0.01"></div>
    </div>
    @endfor
  </div>
  <button class="btn btn-buy" type="submit">Simpan Batch</button>
  <a class="btn btn-ghost" href="{{ route('admin.batches.index', $store) }}">Batal</a>
</form>
</div>
@endsection
