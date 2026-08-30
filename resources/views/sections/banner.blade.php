@php
    $align = $config['align'] ?? 'left'; // left = media left
    $url = $config['link_url'] ?? null;
    $media = $config['image'] ?? null;
@endphp
<div class="reveal overflow-hidden rounded-xl border border-line bg-surface shadow-card">
    <div class="grid items-center {{ $align === 'right' ? '' : '' }} md:grid-cols-2">
        @if ($media)
            <div class="zoom-media {{ $align === 'right' ? 'order-2' : '' }} aspect-[4/3] overflow-hidden bg-surface-2 md:aspect-auto md:h-full">
                <img src="{{ $media }}" alt="{{ $section->title ?? 'Banner' }}" loading="lazy" class="h-full w-full object-cover">
            </div>
        @endif
        <div class="p-8 sm:p-12">
            @if ($eyebrow = $config['eyebrow'] ?? null)
                <p class="overline">{{ $eyebrow }}</p>
            @endif
            @if ($section->title)
                <h2 class="mt-3 font-display text-3xl text-ink sm:text-4xl">{{ $section->title }}</h2>
            @endif
            @if ($section->subtitle)
                <p class="mt-3 text-ink-2">{{ $section->subtitle }}</p>
            @endif
            @if ($url && ($config['link_label'] ?? null))
                <a href="{{ $url }}" wire:navigate class="btn btn-primary mt-6">{{ $config['link_label'] }}</a>
            @endif
        </div>
    </div>
</div>
