@props([
    'title' => null,
    'subtitle' => null,
    'store' => null,
    'eyebrow' => null,
])

@php
    $resolvedTitle = $title ?: (app()->view->getSections()['title'] ?? 'Dashboard');
    $resolvedSubtitle = $subtitle ?: (app()->view->getSections()['subtitle'] ?? null);
    $resolvedEyebrow = $eyebrow ?: (app()->view->getSections()['eyebrow'] ?? null);

    if (!$resolvedEyebrow) {
        if ($store) {
            $resolvedEyebrow = 'Toko · ' . $store->name;
        } elseif (auth()->check() && auth()->user()->isSuperadmin()) {
            $resolvedEyebrow = 'Superadmin Panel';
        } else {
            $resolvedEyebrow = config('app.name');
        }
    }
@endphp

<div class="dashboard-page-header">
  <div class="header-content-group">
    @if($resolvedEyebrow)
      <div class="header-eyebrow">
        <span class="eyebrow-dot"></span>
        <span class="eyebrow-text">{{ $resolvedEyebrow }}</span>
      </div>
    @endif
    <h1 class="header-title">{{ $resolvedTitle }}</h1>
    @if($resolvedSubtitle)
      <p class="header-subtitle">{{ $resolvedSubtitle }}</p>
    @endif
  </div>

  @if(isset($actions) && $actions->isNotEmpty())
    <div class="header-actions-group">
      {{ $actions }}
    </div>
  @elseif(app()->view->hasSection('header-actions'))
    <div class="header-actions-group">
      @yield('header-actions')
    </div>
  @endif
</div>
