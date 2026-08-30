<div>
    <div class="reveal mb-10 flex flex-wrap items-end justify-between gap-4">
        <div>
            @if ($section->title)
                <h2 class="font-display text-3xl text-ink sm:text-4xl">{{ $section->title }}</h2>
            @endif
            @if ($section->subtitle)
                <p class="mt-2 max-w-xl text-ink-2">{{ $section->subtitle }}</p>
            @endif
        </div>
        @if ($url = data_get($config, 'link_url'))
            <a href="{{ $url }}" wire:navigate class="btn btn-outline btn-sm">{{ $config['link_label'] ?? 'Lihat Semua' }}</a>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-{{ min(4, max(2, (int) ($config['columns'] ?? 4))) }}">
        @foreach ($products as $product)
            <div class="reveal" style="--reveal-delay: {{ $loop->index * 60 % 360 }}ms">
                <x-product-card :product="$product" />
            </div>
        @endforeach
    </div>
</div>
