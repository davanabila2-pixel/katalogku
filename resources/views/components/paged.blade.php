@props(['items' => null, 'empty' => 'Belum ada data.'])
@if(($items ?? collect())->count())
  {{ $slot }}
  @if(method_exists($items, 'links'))
    <ul class="pagination">
      @if($items->onFirstPage())<li><span>‹</span></li>@else<li><a href="{{ $items->previousPageUrl() }}">‹</a></li>@endif
      @foreach($items->getUrlRange(max(1, $items->currentPage()-2), min($items->lastPage(), $items->currentPage()+2)) as $page => $url)
        <li>@if($page == $items->currentPage())<span class="current">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif</li>
      @endforeach
      @if($items->hasMorePages())<li><a href="{{ $items->nextPageUrl() }}">›</a></li>@else<li><span>›</span></li>@endif
    </ul>
  @endif
@else
  <div class="empty"><h3>Belum ada data</h3><p>{{ $empty }}</p></div>
@endif
