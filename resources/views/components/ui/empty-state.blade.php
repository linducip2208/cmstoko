@props(['title' => '', 'description' => null])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-20 text-center']) }}>
    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-surface-2">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.4" stroke="currentColor" class="h-7 w-7 text-ink-3" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
        </svg>
    </div>
    <p class="mt-5 text-lg font-semibold text-ink">{{ $title }}</p>
    @isset($description)
        <p class="mt-1.5 max-w-sm text-sm text-ink-2">{{ $description }}</p>
    @endisset
    @isset($slot)
        <div class="mt-6">{{ $slot }}</div>
    @endisset
</div>
