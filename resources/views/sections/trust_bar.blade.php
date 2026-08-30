@php
    $items = \App\Support\Settings::get('trust_bar.items');
@endphp
@if (is_array($items) && $items !== [])
    <div class="reveal grid grid-cols-2 gap-4 sm:grid-cols-4" role="list">
        @foreach ($items as $item)
            <div role="listitem" class="card flex items-center gap-3 p-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-accent-soft text-accent-ink" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4.5 w-4.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                </span>
                <span class="text-sm font-medium leading-snug text-ink">{{ $item['text'] ?? '' }}</span>
            </div>
        @endforeach
    </div>
@endif
