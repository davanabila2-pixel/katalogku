@if ($paginator->hasPages() || $paginator->total() > 0)
@php
    $from = $paginator->firstItem() ?? 1;
    $to = $paginator->lastItem() ?? $paginator->total();
    $total = $paginator->total();
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $prevUrl = $paginator->previousPageUrl();
    $nextUrl = $paginator->nextPageUrl();
    $urlTemplate = $paginator->url(999999);
@endphp
<div class="pagination-container" data-pagination-component>
  <div class="pagination-info">
    Showing {{ $from }} - {{ $to }} of {{ $total }} data
  </div>
  <div class="pagination-controls">
    @if ($paginator->onFirstPage())
      <button type="button" class="pagination-nav-btn is-disabled" disabled aria-disabled="true">
        &lt; PREV PAGE
      </button>
    @else
      <a href="{{ $prevUrl }}" class="pagination-nav-btn" data-page="{{ $currentPage - 1 }}">
        &lt; PREV PAGE
      </a>
    @endif

    <div class="pagination-page-box">
      <input
        type="number"
        class="pagination-page-input"
        value="{{ $currentPage }}"
        min="1"
        max="{{ $lastPage }}"
        aria-label="Nomor halaman"
        data-current="{{ $currentPage }}"
        data-max="{{ $lastPage }}"
        data-url-template="{{ $urlTemplate }}"
      >
      <span class="pagination-total-label">of {{ $lastPage }} pages</span>
    </div>

    @if ($paginator->hasMorePages())
      <a href="{{ $nextUrl }}" class="pagination-nav-btn" data-page="{{ $currentPage + 1 }}">
        NEXT PAGE &gt;
      </a>
    @else
      <button type="button" class="pagination-nav-btn is-disabled" disabled aria-disabled="true">
        NEXT PAGE &gt;
      </button>
    @endif
  </div>
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
