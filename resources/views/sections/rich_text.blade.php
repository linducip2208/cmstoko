@php $align = $config['align'] ?? 'left'; @endphp
<div class="reveal mx-auto max-w-2xl {{ $align === 'center' ? 'text-center' : '' }}">
    @if ($section->title)
        <h2 class="font-display text-3xl text-ink sm:text-4xl">{{ $section->title }}</h2>
    @endif
    @if ($section->subtitle)
        <p class="mt-4 leading-relaxed text-ink-2">{{ $section->subtitle }}</p>
    @endif
    @if ($html = data_get($config, 'html'))
        <div class="prose-content mt-4 leading-relaxed text-ink-2 [&_a]:text-accent [&_strong]:text-ink">
            {!! \App\Models\CmsPage::make(['content' => $html])->renderableContent() !!}
        </div>
    @endif
</div>
