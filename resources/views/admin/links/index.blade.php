@extends('layouts.dashboard')
@section('title', 'Tautan')
@section('subtitle', $store->name)

@section('header-actions')
<a class="btn btn-secondary btn-sm" href="{{ route('store.links', $store->slug) }}" target="_blank" rel="noopener">Buka Halaman Tautan ↗</a>
@endsection

@section('content')
@php
    $editingId = session('editing');
    $editing = $editingId ? $links->firstWhere('id', $editingId) : null;
@endphp

{{-- Alamat halaman tautan: satu hal yang disalin dan dibagikan ke pelanggan. --}}
<div class="panel">
  <h2>Bagikan halaman tautan</h2>
  <p class="lk-share-hint">Salin alamat ini lalu tempel ke bio Instagram, status WhatsApp, atau kartu nama. Hanya tautan berstatus aktif yang tampil di halaman tersebut.</p>
  <div class="lk-share-row">
    <input class="input lk-share-url" id="lk-share-url" type="text" readonly
           value="{{ route('store.links', $store->slug) }}" aria-label="Alamat halaman tautan">
    <button class="btn btn-primary" type="button" id="lk-copy">Salin tautan</button>
    <a class="btn btn-ghost" href="{{ route('store.links', $store->slug) }}" target="_blank" rel="noopener">Buka halaman</a>
  </div>
  <p class="lk-copy-note" id="lk-copy-note" role="status" aria-live="polite"></p>
</div>

{{-- Satu formulir untuk dua keadaan: tambah dan ubah. --}}
<div class="panel">
  <h2 id="lk-form-title">{{ $editing ? 'Ubah Tautan' : 'Tambah Tautan' }}</h2>
  <form method="POST" id="lk-form"
        action="{{ $editing ? route('admin.links.update', [$store, $editing]) : route('admin.links.store', $store) }}"
        data-store-action="{{ route('admin.links.store', $store) }}"
        data-editing="{{ $editing?->id ?? '' }}">
    @csrf
    @if($editing) @method('PUT') @endif
    <div class="lk-form-grid">
      <div class="field">
        <label for="lk-title">Judul</label>
        <input class="input" type="text" id="lk-title" name="title" maxlength="80" required
               placeholder="cth: Katalog Produk" value="{{ old('title', $editing?->title) }}">
      </div>
      <div class="field">
        <label for="lk-url">Alamat tautan</label>
        <input class="input" type="url" id="lk-url" name="url" required
               placeholder="https://…" value="{{ old('url', $editing?->url) }}">
      </div>
      <div class="field lk-form-check">
        <span class="lk-field-label">Tampilkan</span>
        <input type="hidden" name="is_active" value="0">
        <label class="lk-check" for="lk-active">
          <input type="checkbox" id="lk-active" name="is_active" value="1"
                 {{ old('is_active', $editing ? $editing->is_active : true) ? 'checked' : '' }}>
          Aktif
        </label>
      </div>
      <div class="field lk-form-submit">
        <button class="btn btn-buy" type="submit" id="lk-submit">{{ $editing ? 'Simpan Perubahan' : 'Simpan Tautan' }}</button>
        <button class="btn btn-ghost" type="button" id="lk-cancel"{{ $editing ? '' : ' hidden' }}>Batal</button>
      </div>
    </div>
  </form>
</div>

<div class="panel" style="padding:0">
  <div class="table-wrap" style="border:none">
    <table class="table">
      <thead>
        <tr>
          <th style="width:52px">No</th>
          <th>Judul</th>
          <th>Alamat</th>
          <th style="width:110px">Status</th>
          <th style="width:270px">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse($links as $link)
        <tr>
          <td>{{ $loop->iteration }}</td>
          <td><strong>{{ $link->title }}</strong></td>
          <td class="lk-url-cell">{{ $link->url }}</td>
          <td>
            <span class="badge {{ $link->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $link->is_active ? 'Aktif' : 'Nonaktif' }}</span>
          </td>
          <td>
            <div class="lk-row-actions">
              <form method="POST" action="{{ route('admin.links.move', [$store, $link]) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="direction" value="up">
                <button class="lk-move" type="submit"{{ $loop->first ? ' disabled' : '' }} aria-label="Naikkan {{ $link->title }}">↑</button>
              </form>
              <form method="POST" action="{{ route('admin.links.move', [$store, $link]) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="direction" value="down">
                <button class="lk-move" type="submit"{{ $loop->last ? ' disabled' : '' }} aria-label="Turunkan {{ $link->title }}">↓</button>
              </form>
              <button class="btn btn-ghost btn-sm" type="button" data-lk-edit
                      data-id="{{ $link->id }}"
                      data-action="{{ route('admin.links.update', [$store, $link]) }}"
                      data-title="{{ $link->title }}"
                      data-url="{{ $link->url }}"
                      data-active="{{ $link->is_active ? '1' : '0' }}">Ubah</button>
              <form method="POST" action="{{ route('admin.links.destroy', [$store, $link]) }}">
                @csrf @method('DELETE')
                <button class="btn btn-ghost btn-sm" type="submit"
                        onclick="return confirm('Hapus tautan ini? Tautan yang sudah dihapus tidak bisa dikembalikan.')">Hapus</button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="5" class="lk-empty-cell">Belum ada tautan. Tambahkan tautan pertama lewat formulir di atas.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<script>
(function () {
    // Salin alamat halaman tautan.
    var copyBtn = document.getElementById('lk-copy');
    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            var input = document.getElementById('lk-share-url');
            var note = document.getElementById('lk-copy-note');
            var done = function () { note.textContent = 'Alamat tautan sudah disalin.'; };
            var fallback = function () {
                input.select();
                try { if (document.execCommand('copy')) { done(); } } catch (e) {}
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(input.value).then(done, fallback);
            } else {
                fallback();
            }
        });
    }

    // Formulir tambah/ubah memakai satu kotak yang sama.
    var form = document.getElementById('lk-form');
    if (!form) return;

    var titleInput = document.getElementById('lk-title');
    var urlInput = document.getElementById('lk-url');
    var activeInput = document.getElementById('lk-active');
    var heading = document.getElementById('lk-form-title');
    var submit = document.getElementById('lk-submit');
    var cancel = document.getElementById('lk-cancel');

    function setMethod(method) {
        var field = form.querySelector('input[name="_method"]');
        if (method) {
            if (!field) {
                field = document.createElement('input');
                field.type = 'hidden';
                field.name = '_method';
                form.appendChild(field);
            }
            field.value = method;
        } else if (field) {
            field.parentNode.removeChild(field);
        }
    }

    function toCreateMode() {
        form.setAttribute('action', form.getAttribute('data-store-action'));
        setMethod(null);
        form.setAttribute('data-editing', '');
        titleInput.value = '';
        urlInput.value = '';
        activeInput.checked = true;
        heading.textContent = 'Tambah Tautan';
        submit.textContent = 'Simpan Tautan';
        cancel.hidden = true;
        titleInput.focus();
    }

    Array.prototype.forEach.call(document.querySelectorAll('[data-lk-edit]'), function (button) {
        button.addEventListener('click', function () {
            // Sudah mengedit baris yang sama: biarkan isian yang sedang diketik tetap ada.
            if (form.getAttribute('data-editing') === button.getAttribute('data-id')) {
                titleInput.focus();
                return;
            }

            form.setAttribute('action', button.getAttribute('data-action'));
            setMethod('PUT');
            form.setAttribute('data-editing', button.getAttribute('data-id'));
            titleInput.value = button.getAttribute('data-title');
            urlInput.value = button.getAttribute('data-url');
            activeInput.checked = button.getAttribute('data-active') === '1';
            heading.textContent = 'Ubah Tautan';
            submit.textContent = 'Simpan Perubahan';
            cancel.hidden = false;
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            titleInput.focus();
        });
    });

    cancel.addEventListener('click', toCreateMode);
})();
</script>
@endsection
