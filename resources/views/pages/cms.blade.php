<x-layouts.app :title="$seo['title']">
    <x-seo.meta :seo="$seo" />
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <article>
            <header class="mb-10">
                <h1 class="font-display text-4xl text-ink sm:text-5xl">{{ $page->title }}</h1>
                @if ($page->excerpt)
                    <p class="mt-3 text-lg leading-relaxed text-ink-2">{{ $page->excerpt }}</p>
                @endif
                @if ($page->published_at)
                    <p class="mt-3 text-xs text-ink-3">{{ $page->published_at->translatedFormat('d F Y') }}</p>
                @endif
            </header>

            @if ($page->featured_image)
                <img src="{{ $page->featured_image }}" alt="{{ $page->title }}"
                     class="mb-10 w-full rounded-lg border border-line object-cover" loading="lazy">
            @endif

            <div class="prose-content space-y-5 text-base leading-relaxed text-ink-2 [&_a]:text-accent [&_a:hover]:underline [&_h2]:mt-10 [&_h2]:text-2xl [&_h2]:font-semibold [&_h2]:text-ink [&_h3]:mt-8 [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:text-ink [&_li]:ml-5 [&_ol]:list-decimal [&_strong]:text-ink [&_ul]:list-disc">
                {!! $page->renderableContent() !!}
            </div>
        </article>
    </div>
</x-layouts.app>
