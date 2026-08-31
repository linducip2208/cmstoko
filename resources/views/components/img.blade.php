{{-- Responsive image component.
     Media model: <x-img :media="$media" alt="..." sizes="..." />
     Plain URL:   <x-img src="storage/x.jpg" alt="..." eager />
     Hero/LCP: add `eager` + `preload`. Width/height prevent CLS. --}}
@props([
    'media' => null,      // Media model
    'src' => null,        // plain URL fallback when no Media model
    'alt' => '',
    'sizes' => '100vw',
    'width' => null,
    'height' => null,
    'class' => '',
    'eager' => false,     // false = loading="lazy"
    'preload' => false,   // emit <link rel="preload"> in head
])

@php
    $pipeline = app(\App\Services\ImagePipeline::class);

    if ($media instanceof \App\Models\Media) {
        $srcUrl = $media->url();
        $srcsetAttr = $pipeline->srcset($media);
        $w = $media->width;
        $h = $media->height;
    } else {
        $srcUrl = $src ?? '';
        $srcsetAttr = null;
        $w = $width;
        $h = $height;
    }

    // Nothing to render without a source.
    $renderable = $srcUrl !== '';
@endphp

@if ($renderable)
    @if ($preload)
        <link rel="preload" as="image" href="{{ $srcUrl }}" imagesrcset="{{ $srcsetAttr }}" imagesizes="{{ $sizes }}">
    @endif

    <img src="{{ $srcUrl }}"
         @if ($srcsetAttr) srcset="{{ $srcsetAttr }}" @endif
         sizes="{{ $sizes }}"
         @if ($w) width="{{ $w }}" @endif
         @if ($h) height="{{ $h }}" @endif
         alt="{{ $alt }}"
         loading="{{ $eager ? 'eager' : 'lazy' }}"
         decoding="{{ $eager ? 'sync' : 'async' }}"
         {{ $attributes->merge(['class' => $class]) }}>
@endif
