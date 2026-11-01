@props([
    'paginator' => null,
    'countSuffix' => 'produk',
    'perPageOptions' => [10, 25, 50, 100],
])

@if($paginator && method_exists($paginator, 'total') && $paginator->total() > 0)
@php
    $from = $paginator->firstItem() ?? 1;
    $to = $paginator->lastItem() ?? $paginator->total();
    $total = $paginator->total();
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $prevUrl = $paginator->previousPageUrl();
    $nextUrl = $paginator->nextPageUrl();
    $urlTemplate = $paginator->url(999999);
    $currentPerPage = (int) request('per_page', $paginator->perPage());
    if (! in_array($currentPerPage, $perPageOptions, true)) {
        $currentPerPage = $paginator->perPage();
    }
@endphp
<div class="pagination-container" data-pagination-component>
  <div class="pagination-meta">
    <div class="pagination-info">
      <span>Menampilkan</span>
      <span class="pagination-info-range">{{ $from }}–{{ $to }}</span>
      <span>dari</span>
      <span class="pagination-info-total">{{ number_format($total, 0, ',', '.') }} {{ $countSuffix }}</span>
    </div>

    {{-- Dropdown Pilihan Tampilkan 10, 25, 50, 100 --}}
    <div class="pagination-per-page-wrap">
      <label for="pagination-per-page-select-{{ $paginator->getPageName() }}" class="pagination-per-page-label">Tampilkan:</label>
      <div class="pagination-select-pill">
        <select
          id="pagination-per-page-select-{{ $paginator->getPageName() }}"
          class="pagination-per-page-select"
          aria-label="Pilih jumlah data per halaman"
          onchange="window.location.href=this.value"
        >
          @foreach($perPageOptions as $size)
            <option
              value="{{ request()->fullUrlWithQuery(['per_page' => $size, 'page' => 1]) }}"
              {{ $currentPerPage === $size ? 'selected' : '' }}
            >
              {{ $size }}
            </option>
          @endforeach
        </select>
        <svg class="pagination-select-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="6 9 12 15 18 9"/>
        </svg>
      </div>
    </div>
  </div>

  <nav class="pagination-controls" aria-label="Navigasi Halaman">
    {{-- Tombol Sebelumnya --}}
    @if($paginator->onFirstPage())
      <button type="button" class="pagination-nav-btn is-disabled" disabled aria-disabled="true" title="Halaman pertama">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        <span>Sebelumnya</span>
      </button>
    @else
      <a href="{{ $prevUrl }}" class="pagination-nav-btn" data-page="{{ $currentPage - 1 }}" aria-label="Halaman sebelumnya" title="Ke halaman sebelumnya">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        <span>Sebelumnya</span>
      </a>
    @endif

    {{-- Kapsul Input Halaman Interaktif --}}
    <div class="pagination-page-capsule" title="Ketik nomor halaman dan tekan Enter untuk berpindah">
      <span class="pagination-capsule-label">Hal.</span>
      <input
        type="number"
        class="pagination-page-input"
        value="{{ $currentPage }}"
        min="1"
        max="{{ $lastPage }}"
        aria-label="Ketik nomor halaman"
        data-current="{{ $currentPage }}"
        data-max="{{ $lastPage }}"
        data-url-template="{{ $urlTemplate }}"
      >
      <span class="pagination-capsule-divider">/</span>
      <span class="pagination-capsule-total">{{ $lastPage }}</span>
    </div>

    {{-- Tombol Berikutnya --}}
    @if($paginator->hasMorePages())
      <a href="{{ $nextUrl }}" class="pagination-nav-btn" data-page="{{ $currentPage + 1 }}" aria-label="Halaman berikutnya" title="Ke halaman berikutnya">
        <span>Berikutnya</span>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
      </a>
    @else
      <button type="button" class="pagination-nav-btn is-disabled" disabled aria-disabled="true" title="Halaman terakhir">
        <span>Berikutnya</span>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    @endif
  </nav>
</div>

<script>
(function() {
  if (window.__paginationComponentInitialized) return;
  window.__paginationComponentInitialized = true;

  function jumpPage(input) {
    var max = parseInt(input.getAttribute('data-max') || input.getAttribute('max'), 10) || 1;
    var current = parseInt(input.getAttribute('data-current'), 10) || 1;
    var target = parseInt(input.value, 10);
    if (isNaN(target) || target < 1) target = 1;
    if (target > max) target = max;
    input.value = target;

    if (target !== current) {
      var template = input.getAttribute('data-url-template');
      if (template) {
        window.location.href = template.replace('999999', target).replace('page=999999', 'page=' + target);
      }
    }
  }

  document.addEventListener('change', function(e) {
    if (e.target.matches('.pagination-page-input[data-url-template]')) {
      jumpPage(e.target);
    }
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.matches('.pagination-page-input[data-url-template]')) {
      e.preventDefault();
      jumpPage(e.target);
    }
  });
})();
</script>
@endif
