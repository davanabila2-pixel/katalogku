@extends('layouts.app')
@section('content')
<div class="card form-card" style="margin:48px auto">
  <h1 style="font-size:28px;margin:0 0 8px">Daftar akun</h1>
  <p style="color:var(--steel)">Pilih jenis pendaftaran.</p>
  <form method="POST" action="{{ route('register') }}">@csrf
    <div class="field">
      <label>Daftar sebagai</label>
      <div style="display:flex;gap:8px">
        <label class="pill-tab" style="cursor:pointer"><input type="radio" name="account_type" value="toko" {{ old('account_type', 'toko') === 'toko' ? 'checked' : '' }}> Toko</label>
        <label class="pill-tab" style="cursor:pointer"><input type="radio" name="account_type" value="user" {{ old('account_type') === 'user' ? 'checked' : '' }}> Pengguna</label>
      </div>
      <small class="category-hierarchy"><strong>Toko</strong>: langsung punya katalog sendiri dan menjadi admin utama. <strong>Pengguna</strong>: akun saja, bisa membuat toko nanti.</small>
    </div>
    <div class="field"><label for="name">Nama</label><input id="name" class="input" type="text" name="name" value="{{ old('name') }}" required></div>
    <div class="field"><label for="email">Email</label><input id="email" class="input" type="email" name="email" value="{{ old('email') }}" required></div>
    <div class="field"><label for="password">Password (min 8 karakter)</label><input id="password" class="input" type="password" name="password" required></div>
    <div class="field"><label for="password_confirmation">Konfirmasi password</label><input id="password_confirmation" class="input" type="password" name="password_confirmation" required></div>
    <div id="store-fields">
      <div class="field"><label for="store_name">Nama toko</label><input id="store_name" class="input" type="text" name="store_name" value="{{ old('store_name') }}" placeholder="cth: Thrift Premium"><small class="category-hierarchy">Slug URL dibuat otomatis dari nama toko.</small></div>
      <div class="field"><label for="store_description">Deskripsi toko (opsional)</label><textarea id="store_description" class="textarea" name="store_description">{{ old('store_description') }}</textarea></div>
      <div class="field"><label for="store_contact">Kontak toko (opsional)</label><input id="store_contact" class="input" type="text" name="store_contact" value="{{ old('store_contact') }}"></div>
    </div>
    <button class="btn btn-buy" type="submit" style="width:100%">Daftar</button>
  </form>
  <p style="margin-top:16px;font-size:14px">atau</p>
  <a class="btn btn-secondary" href="{{ route('auth.google.redirect') }}" style="width:100%">Daftar dengan Google</a>
</div>
<script>
const radios = document.querySelectorAll('input[name=account_type]');
const storeFields = document.getElementById('store-fields');
function toggleStore() {
  const isToko = document.querySelector('input[name=account_type]:checked').value === 'toko';
  storeFields.style.display = isToko ? '' : 'none';
  document.getElementById('store_name').required = isToko;
}
radios.forEach(r => r.addEventListener('change', toggleStore));
toggleStore();
</script>
@endsection
