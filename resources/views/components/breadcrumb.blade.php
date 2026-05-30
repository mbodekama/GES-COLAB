@props(['items' => []])

@if(count($items) > 0)
<nav aria-label="breadcrumb" class="d-none d-md-flex align-items-center" style="flex:1;min-width:0">
    <ol class="breadcrumb mb-0 flex-nowrap align-items-center">
        @foreach($items as $item)
            @if(!$loop->last)
                <li class="breadcrumb-item">
                    @if(!empty($item['url']))
                        <a href="{{ $item['url'] }}"
                           class="text-decoration-none"
                           style="color:#6c757d;font-size:13px;font-weight:500;transition:color .15s"
                           onmouseover="this.style.color='#185FA5'"
                           onmouseout="this.style.color='#6c757d'">{{ $item['label'] }}</a>
                    @else
                        <span style="color:#6c757d;font-size:13px">{{ $item['label'] }}</span>
                    @endif
                </li>
            @else
                <li class="breadcrumb-item active text-truncate" aria-current="page"
                    style="font-size:17px;font-weight:600;color:#1a1a2e;max-width:420px">
                    {{ $item['label'] }}
                </li>
            @endif
        @endforeach
    </ol>
</nav>
@endif
