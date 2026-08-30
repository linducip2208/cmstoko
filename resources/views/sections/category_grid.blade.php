<div>
    @if ($section->title)
        <div class="reveal mb-10">
            <h2 class="font-display text-3xl text-ink sm:text-4xl">{{ $section->title }}</h2>
            @if ($section->subtitle)
                <p class="mt-2 max-w-xl text-ink-2">{{ $section->subtitle }}</p>
            @endif
        </div>
    @endif

    <div class="grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-4">
        @foreach ($categories as $category)
            <a href="{{ route('shop', ['category' => $category->slug]) }}" wire:navigate
               class="group reveal relative overflow-hidden rounded-lg border border-line bg-surface transition-shadow duration-300 hover:shadow-card"
               style="--reveal-delay: {{ $loop->index * 70 % 350 }}ms">
                <div class="zoom-media aspect-[4/3] overflow-hidden bg-surface-2">
                    @if ($category->cover_image ?? $category->image)
                        <img src="{{ $category->cover_image ?? $category->image }}" alt="{{ $category->name }}" loading="lazy" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center">
                            <span class="font-display text-4xl text-ink-3">{{ mb_substr($category->name, 0, 1) }}</span>
                        </div>
                    @endif
                </div>
                <div class="flex items-center justify-between p-4">
                    <div>
                        <p class="font-semibold text-ink">{{ $category->name }}</p>
                        <p class="text-xs text-ink-3">{{ $category->active_products_count }} produk</p>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-ink-3 transition-transform duration-300 group-hover:translate-x-0.5 group-hover:text-accent" aria-hidden="true"><path fill-rule="evenodd" d="M8.22 4.47a.75.75 0 0 1 1.06 0l4.5 4.5a.75.75 0 0 1 0 1.06l-4.5 4.5a.75.75 0 0 1-1.06-1.06L12.19 10 8.22 6.03a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                </div>
            </a>
        @endforeach
    </div>
</div>
