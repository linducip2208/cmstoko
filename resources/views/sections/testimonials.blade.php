<div>
    <div class="max-w-xl">
        <p class="overline">{{ $config['overline'] ?? 'Testimoni' }}</p>
        <h2 class="mt-2 font-display text-4xl text-ink sm:text-5xl">{{ $config['heading'] ?? 'Kata Mereka' }}</h2>
    </div>

    <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($testimonials as $testimonial)
            <figure class="card flex h-full flex-col p-6">
                @if ($testimonial->hasRating())
                    <x-ui.rating :rating="$testimonial->rating" class="mb-4" />
                @endif
                <blockquote class="flex-1 text-sm leading-relaxed text-ink-2">“{{ $testimonial->quote }}”</blockquote>
                <figcaption class="mt-5 flex items-center gap-3 border-t border-line pt-4">
                    @if ($testimonial->avatar)
                        <img src="{{ $testimonial->avatar }}" alt="" class="h-10 w-10 rounded-full object-cover" loading="lazy" width="40" height="40">
                    @else
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-surface-2 text-sm font-bold text-ink-2">{{ mb_substr($testimonial->name, 0, 1) }}</span>
                    @endif
                    <div>
                        <p class="text-sm font-semibold text-ink">{{ $testimonial->name }}</p>
                        @if ($testimonial->role_company)
                            <p class="text-xs text-ink-3">{{ $testimonial->role_company }}</p>
                        @endif
                    </div>
                </figcaption>
            </figure>
        @empty
            <div class="col-span-full">
                <x-ui.empty-state title="Belum ada testimoni" />
            </div>
        @endforelse
    </div>
</div>
