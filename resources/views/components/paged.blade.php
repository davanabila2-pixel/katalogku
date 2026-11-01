@props(['items' => null, 'empty' => 'Belum ada data.', 'countSuffix' => 'produk'])
@if(($items ?? collect())->count())
  {{ $slot }}
  @if(method_exists($items, 'links'))
    <x-pagination :paginator="$items" :countSuffix="$countSuffix" />
  @endif
@else
  <div class="empty"><h3>Belum ada data</h3><p>{{ $empty }}</p></div>
@endif
