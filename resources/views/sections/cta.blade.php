@php $cta = $config['cta'] ?? ['label' => 'Belanja Sekarang', 'url' => route('shop')]; @endphp
<div class="reveal mx-auto max-w-2xl text-center">
    @if ($section->title)
        <h2 class="font-display text-4xl text-ink sm:text-5xl">{{ $section->title }}</h2>
    @endif
    @if ($section->subtitle)
        <p class="mt-4 text-lg text-ink-2">{{ $section->subtitle }}</p>
    @endif
    @if (! empty($cta['label']))
        <a href="{{ $cta['url'] }}" wire:navigate class="btn btn-accent btn-lg mt-8">{{ $cta['label'] }}</a>
    @endif
</div>
