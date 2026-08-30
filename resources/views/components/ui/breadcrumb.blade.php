@props(['items'])
<nav {{ $attributes->merge(['class' => 'mb-8', 'aria-label' => 'Breadcrumb']) }}>
    <ol class="flex flex-wrap items-center gap-1.5 text-sm text-ink-3">
        <li>
            <a href="{{ route('home') }}" wire:navigate class="hover:text-ink">Beranda</a>
        </li>
        @foreach ($items as $label => $url)
            <li class="flex items-center gap-1.5" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5"><path fill-rule="evenodd" d="M8.22 4.47a.75.75 0 0 1 1.06 0l4.5 4.5a.75.75 0 0 1 0 1.06l-4.5 4.5a.75.75 0 0 1-1.06-1.06L12.19 10 8.22 6.03a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
            </li>
            <li @if ($loop->last) aria-current="page" @endif>
                @if ($loop->last)
                    <span class="font-medium text-ink">{{ $label }}</span>
                @else
                    <a href="{{ $url }}" wire:navigate class="hover:text-ink">{{ $label }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
