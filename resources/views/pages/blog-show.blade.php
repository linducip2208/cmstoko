<x-layouts.app :title="$seo['title']">
    <x-seo.meta :seo="$seo" />
    <article class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <x-ui.breadcrumb :items="['Blog' => route('blog'), $post->title => null]" />

        <header class="mt-8">
            @if ($post->category)
                <p class="overline">{{ $post->category->name }}</p>
            @endif
            <h1 class="mt-2 font-display text-4xl leading-tight text-ink sm:text-5xl">{{ $post->title }}</h1>
            @if ($post->excerpt)
                <p class="mt-4 text-lg leading-relaxed text-ink-2">{{ $post->excerpt }}</p>
            @endif
            <p class="mt-6 flex flex-wrap items-center gap-2 text-sm text-ink-3">
                @if ($post->author) <span>{{ $post->author->name }}</span> <span>·</span> @endif
                <time datetime="{{ $post->published_at?->toIso8601String() }}">{{ $post->published_at?->translatedFormat('d M Y') ?? $post->created_at->translatedFormat('d M Y') }}</time>
            </p>
        </header>

        @if ($post->cover)
            <figure class="mt-8">
                <img src="{{ $post->cover }}" alt="{{ $post->title }}" class="aspect-[3/2] w-full rounded-lg object-cover" width="900" height="600">
            </figure>
        @endif

        <div class="prose-editorial mt-10 space-y-5 text-base leading-relaxed text-ink-2 [&_a]:font-medium [&_a]:text-accent [&_h2]:font-display [&_h2]:text-3xl [&_h2]:text-ink [&_h2]:mt-10 [&_h2]:mb-2 [&_h3]:mt-8 [&_h3]:text-xl [&_h3]:font-bold [&_h3]:text-ink [&_img]:rounded-lg [&_ul]:list-disc [&_ul]:pl-5">
            {!! $post->renderableContent() !!}
        </div>

        @if ($post->tags->isNotEmpty())
            <div class="mt-10 flex flex-wrap gap-2 border-t border-line pt-6">
                @foreach ($post->tags as $tag)
                    <a href="{{ route('blog', ['tag' => $tag->slug]) }}" class="badge bg-surface-2 text-ink-2 hover:text-ink">{{ $tag->name }}</a>
                @endforeach
            </div>
        @endif

        @if ($related->isNotEmpty())
            <section class="mt-14 border-t border-line pt-10">
                <p class="overline">Artikel Terkait</p>
                <div class="mt-5 grid gap-8 sm:grid-cols-3">
                    @foreach ($related as $item)
                        <article>
                            <h2 class="text-base font-bold leading-snug text-ink">
                                <a href="{{ route('blog.show', $item->slug) }}" class="hover:text-accent">{{ $item->title }}</a>
                            </h2>
                            <p class="mt-1.5 text-xs text-ink-3">{{ $item->published_at?->translatedFormat('d M Y') }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </article>
</x-layouts.app>
