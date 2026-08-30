@props(['rating', 'count' => null])
@php
    $full = floor($rating);
    $half = $rating - $full >= 0.5;
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1', 'role' => 'img', 'aria-label' => 'Rating '.$rating.' dari 5']) }}>
    <span class="flex" aria-hidden="true">
        @for ($i = 1; $i <= 5; $i++)
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                 class="h-4 w-4 {{ $i <= $full ? 'text-warning' : ($i === $full + 1 && $half ? 'text-warning/50' : 'text-line-strong') }}">
                <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z" clip-rule="evenodd"/>
            </svg>
        @endfor
    </span>
    @isset($count)
        <span class="text-xs font-medium text-ink-3">({{ number_format($count) }})</span>
    @endisset
</span>
