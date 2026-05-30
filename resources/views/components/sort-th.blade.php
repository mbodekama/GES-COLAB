@props(['column', 'label'])

@php
    $active = request('sort_by') === $column;
    $dir    = $active ? request('sort_dir', 'asc') : 'asc';
    $newDir = ($active && $dir === 'asc') ? 'desc' : 'asc';
    $url    = request()->fullUrlWithQuery(['sort_by' => $column, 'sort_dir' => $newDir]);
@endphp

<th>
    <a href="{{ $url }}" class="sort-th {{ $active ? 'sort-th--active' : '' }}" title="Trier par {{ $label }}">
        {{ $label }}
        @if($active)
            <i class="bi bi-caret-{{ $dir === 'asc' ? 'up' : 'down' }}-fill ms-1"></i>
        @else
            <i class="bi bi-chevron-expand ms-1 opacity-25"></i>
        @endif
    </a>
</th>
