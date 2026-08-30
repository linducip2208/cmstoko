<x-layouts.app :title="$seo['title']">
    <x-seo.meta :seo="$seo" />
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <header class="max-w-2xl">
            <p class="overline">Blog</p>
            <h1 class="mt-2 font-display text-4xl text-ink sm:text-5xl">
                @if ($activeCategory) {{ $activeCategory->name }}
                @elseif ($activeTag) Tag: {{ $activeTag->name }}
                @elseif (request('q')) Pencarian: {{ request('q') }}
                @else Cerita &amp; Panduan
                @endif
            </h1>
        </header>

        <div class="mt-10 grid gap-10 lg:grid-cols-[240px_1fr]">
            <aside class="space-y-8 lg:sticky lg:top-28 lg:h-fit">
                <form action="{{ route('blog') }}" method="GET" role="search">
                    <label for="blog-search" class="label">Cari Artikel</label>
                    <div class="flex gap-2">
                        <input id="blog-search" type="search" name="q" value="{{ request('q') }}" class="input" placeholder="Kata kunci…">
                        <button type="submit" class="btn btn-primary btn-sm shrink-0">Cari</button>
                    </div>
                </form>

                <div>
                    <p class="overline mb-3">Kategori</p>
                    <ul class="space-y-1.5 text-sm">
                        <li><a href="{{ route('blog') }}" class="{{ $activeCategory ? 'text-ink-2 hover:text-ink' : 'font-semibold text-ink' }}">Semua</a></li>
                        @foreach ($categories as $category)
                            <li>
                                <a href="{{ route('blog', ['kategori' => $category->slug]) }}"
                                   class="{{ $activeCategory?->is($category) ? 'font-semibold text-ink' : 'text-ink-2 hover:text-ink' }}">
                                    {{ $category->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                @if ($tags->isNotEmpty())
                    <div>
                        <p class="overline mb-3">Tag</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($tags as $tag)
                                <a href="{{ route('blog', ['tag' => $tag->slug]) }}"
                                   class="badge {{ $activeTag?->is($tag) ? 'bg-ink text-paper' : 'bg-surface-2 text-ink-2 hover:text-ink' }}">
                                    {{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </aside>

            <section>
                @if ($posts->isEmpty())
                    <div class="card">
                        <x-ui.empty-state title="Belum ada artikel" description="Artikel akan tampil di sini setelah dipublikasikan." />
                    </div>
                @else
                    <div class="grid gap-8 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($posts as $post)
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
                                    <h2 class="mt-1.5 text-lg font-bold leading-snug tracking-tight text-ink">
                                        <a href="{{ route('blog.show', $post->slug) }}" class="hover:text-accent">{{ $post->title }}</a>
                                    </h2>
                                    <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-ink-2">{{ $post->excerpt }}</p>
                                    <p class="mt-3 text-xs text-ink-3">
                                        {{ $post->published_at?->translatedFormat('d M Y') ?? $post->created_at->translatedFormat('d M Y') }}
                                        @if ($post->author) · {{ $post->author->name }} @endif
                                    </p>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-12">{{ $posts->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-layouts.app>
