@php
    $variantClasses = match ($variant ?? 'primary') {
        'accent' => 'btn-accent',
        'outline' => 'btn-outline',
        'ghost' => 'btn-ghost',
        default => 'btn-primary',
    };
@endphp
<button {{ $attributes->merge(['type' => $type ?? 'button', 'class' => 'btn '.$variantClasses.' '.match ($size ?? 'md') { 'sm' => 'btn-sm', 'lg' => 'btn-lg', default => '' }]) }}>
    {{ $slot }}
</button>
