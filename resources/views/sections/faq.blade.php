<div class="mx-auto max-w-3xl">
    <div class="text-center">
        <p class="overline">{{ $config['overline'] ?? 'FAQ' }}</p>
        <h2 class="mt-2 font-display text-4xl text-ink sm:text-5xl">{{ $config['heading'] ?? 'Pertanyaan Umum' }}</h2>
    </div>

    <div class="mt-10 divide-y divide-line rounded-lg border border-line bg-surface">
        @forelse ($faqs as $faq)
            <details class="group">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-5 text-left text-sm font-semibold text-ink transition-colors hover:bg-surface-2 [&::-webkit-details-marker]:hidden">
                    {{ $faq->question }}
                    <span class="shrink-0 text-ink-3 transition-transform duration-300 group-open:rotate-45" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/></svg>
                    </span>
                </summary>
                <div class="px-6 pb-5 text-sm leading-relaxed text-ink-2">{{ $faq->answer }}</div>
            </details>
        @empty
            <p class="px-6 py-10 text-center text-sm text-ink-3">Belum ada pertanyaan yang dipublikasikan.</p>
        @endforelse
    </div>
</div>
