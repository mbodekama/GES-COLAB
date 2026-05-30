@props(['items' => []])

@if(count($items) > 0)
<nav aria-label="breadcrumb" class="d-none d-md-block mb-3">
    <ol class="breadcrumb mb-0" style="font-size:13px">
        @foreach($items as $item)
            @if(!$loop->last)
                <li class="breadcrumb-item">
                    @if(!empty($item['url']))
                        <a href="{{ $item['url'] }}" class="text-decoration-none" style="color:#185FA5">{{ $item['label'] }}</a>
                    @else
                        {{ $item['label'] }}
                    @endif
                </li>
            @else
                <li class="breadcrumb-item active" aria-current="page">{{ $item['label'] }}</li>
            @endif
        @endforeach
    </ol>
</nav>
@endif
