@php
    $primaryCta = $config['primary_cta'] ?? ['label' => 'Jelajahi Katalog', 'url' => route('shop')];
    $secondaryCta = $config['secondary_cta'] ?? null;
    $eyebrow = $config['eyebrow'] ?? null;
    $highlight = $config['highlight'] ?? null;
    $product = $products->first();
@endphp
<div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
    <div class="reveal">
        @if ($eyebrow)
            <p class="overline">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-4 font-display text-5xl leading-[1.05] text-ink sm:text-6xl">
            {{ $section->title }}
            @if ($highlight)
                <em class="text-accent">{{ $highlight }}</em>
            @endif
        </h1>
        @if ($section->subtitle)
            <p class="mt-5 max-w-md text-base leading-relaxed text-ink-2 sm:text-lg">{{ $section->subtitle }}</p>
        @endif

        <div class="mt-8 flex flex-wrap items-center gap-3">
            @if (! empty($primaryCta['label']))
                <a href="{{ $primaryCta['url'] }}" wire:navigate class="btn btn-primary btn-lg">{{ $primaryCta['label'] }}</a>
            @endif
            @if (! empty($secondaryCta['label']))
                <a href="{{ $secondaryCta['url'] }}" wire:navigate class="btn btn-outline btn-lg">{{ $secondaryCta['label'] }}</a>
            @endif
        </div>
    </div>

    <div class="reveal relative" style="--reveal-delay: 120ms">
        @if ($media = data_get($config, 'image'))
            <img src="{{ $media }}" alt="{{ $section->title ?? 'Hero' }}"
                 class="aspect-[4/5] w-full rounded-xl border border-line object-cover shadow-card sm:aspect-[5/4] lg:aspect-[4/5]"
                 width="800" height="1000" fetchpriority="high">
        @elseif ($product)
            <a href="{{ route('product.show', $product->slug) }}" wire:navigate class="group block">
                <img src="{{ $product->coverImage() }}" alt="{{ $product->name }}"
                     class="zoom-media aspect-[4/5] w-full rounded-xl border border-line object-cover shadow-card"
                     width="800" height="1000" fetchpriority="high">
                <div class="mt-4 flex items-center justify-between">
                    <div>
                        <p class="overline">Produk Unggulan</p>
                        <p class="mt-1 font-semibold text-ink">{{ $product->name }}</p>
                    </div>
                    <x-ui.money :amount="$product->effectivePrice()" class="text-lg font-bold" />
                </div>
            </a>
        @endif
    </div>
</div>
