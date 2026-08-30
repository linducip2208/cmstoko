<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="max-w-xl">
            <p class="overline">{{ $config['overline'] ?? 'Blog' }}</p>
            <h2 class="mt-2 font-display text-4xl text-ink sm:text-5xl">{{ $config['heading'] ?? 'Cerita & Panduan' }}</h2>
        </div>
        <a href="{{ route('blog') }}" wire:navigate class="btn btn-outline btn-sm">
            Semua Artikel
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
        </a>
    </div>

    <div class="mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($posts as $post)
            <article class="group">
                <a href="{{ route('blog.show', $post->slug) }}" class="block overflow-hidden rounded-lg bg-surface-2">
                    @if ($post->cover)
                        <img src="{{ $post->cover }}" alt="{{ $post->title }}" class="aspect-[3/2] w-full object-cover transition-transform duration-700 group-hover:scale-[1.03]" loading="lazy" width="600" height="400">
                    @else
                        <div class="flex aspect-[3/2] w-full items-center justify-center bg-surface-2">
                            <span class="font-display text-4xl text-ink/20">{{ mb_substr($post->title, 0, 1) }}</span>
                        </div>
                    @endif
                </a>
                <div class="mt-4">
                    @if ($post->category)
                        <p class="overline">{{ $post->category->name }}</p>
                    @endif
                    <h3 class="mt-1.5 text-lg font-bold leading-snug tracking-tight text-ink">
                        <a href="{{ route('blog.show', $post->slug) }}" class="hover:text-accent">{{ $post->title }}</a>
                    </h3>
                    <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-ink-2">{{ $post->excerpt }}</p>
                    <p class="mt-3 text-xs text-ink-3">{{ $post->published_at?->translatedFormat('d M Y') ?? $post->created_at->translatedFormat('d M Y') }}</p>
                </div>
            </article>
        @empty
            <div class="col-span-full">
                <x-ui.empty-state title="Belum ada artikel" />
            </div>
        @endforelse
    </div>
</div>
