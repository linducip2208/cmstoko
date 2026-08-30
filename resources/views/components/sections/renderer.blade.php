@php
    $id = 'section-'.$section->id;
    $paddingClass = match ($config['padding'] ?? 'normal') {
        'compact' => 'py-10',
        'large' => 'py-24 sm:py-32',
        default => 'py-16 sm:py-20',
    };
@endphp
<section id="{{ $id }}" aria-label="{{ $section->title ?? \App\Models\HomepageSection::TYPES[$section->type] ?? $section->type }}"
         class="{{ $paddingClass }}">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @includeIf('sections.'.$section->type)
    </div>
</section>
