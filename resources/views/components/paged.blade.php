@props(['items' => null, 'empty' => 'Belum ada data.'])
@if(($items ?? collect())->count())
  {{ $slot }}
  @if(method_exists($items, 'links'))
    <x-pagination :paginator="$items" />
  @endif
@else
  <div class="empty"><h3>Belum ada data</h3><p>{{ $empty }}</p></div>
@endif
