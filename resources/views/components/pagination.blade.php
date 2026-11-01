@props([
    'paginator' => null,
    'countSuffix' => 'produk',
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

    // Sliding window algorithm for aesthetic page numbers
    $pageElements = [];
    if ($lastPage <= 7) {
        for ($i = 1; $i <= $lastPage; $i++) {
            $pageElements[] = $i;
        }
    } else {
        if ($currentPage <= 4) {
            for ($i = 1; $i <= 5; $i++) {
                $pageElements[] = $i;
            }
            $pageElements[] = '...';
            $pageElements[] = $lastPage;
        } elseif ($currentPage >= $lastPage - 3) {
            $pageElements[] = 1;
            $pageElements[] = '...';
            for ($i = $lastPage - 4; $i <= $lastPage; $i++) {
                $pageElements[] = $i;
            }
        } else {
            $pageElements[] = 1;
            $pageElements[] = '...';
            $pageElements[] = $currentPage - 1;
            $pageElements[] = $currentPage;
            $pageElements[] = $currentPage + 1;
            $pageElements[] = '...';
            $pageElements[] = $lastPage;
        }
    }
@endphp
<div class="pagination-container" data-pagination-component>
  <div class="pagination-info">
    <span>Menampilkan</span>
    <span class="pagination-info-range">{{ $from }}–{{ $to }}</span>
    <span>dari</span>
    <span class="pagination-info-total">{{ number_format($total, 0, ',', '.') }} {{ $countSuffix }}</span>
  </div>

  <nav class="pagination-controls" aria-label="Navigasi Halaman">
    {{-- Tombol Sebelumnya --}}
    @if($paginator->onFirstPage())
      <span class="pagination-action-btn is-disabled" aria-disabled="true" title="Halaman pertama">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        <span>Sebelumnya</span>
      </span>
    @else
      <a href="{{ $prevUrl }}" class="pagination-action-btn" aria-label="Ke halaman sebelumnya" title="Halaman sebelumnya">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        <span>Sebelumnya</span>
      </a>
    @endif

    {{-- Deretan Tombol Angka Halaman --}}
    <div class="pagination-pages-list">
      @foreach($pageElements as $el)
        @if($el === '...')
          <span class="pagination-ellipsis" aria-hidden="true">&hellip;</span>
        @elseif($el === $currentPage)
          <span class="pagination-page-item is-active" aria-current="page">{{ $el }}</span>
        @else
          <a href="{{ $paginator->url($el) }}" class="pagination-page-item" aria-label="Halaman {{ $el }}">{{ $el }}</a>
        @endif
      @endforeach
    </div>

    {{-- Tombol Berikutnya --}}
    @if($paginator->hasMorePages())
      <a href="{{ $nextUrl }}" class="pagination-action-btn" aria-label="Ke halaman berikutnya" title="Halaman berikutnya">
        <span>Berikutnya</span>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
      </a>
    @else
      <span class="pagination-action-btn is-disabled" aria-disabled="true" title="Halaman terakhir">
        <span>Berikutnya</span>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
      </span>
    @endif
  </nav>
</div>
@endif
