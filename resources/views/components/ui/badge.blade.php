@props(['tone' => 'default'])
<span {{ $attributes->merge(['class' => match ($tone) {
    'sale' => 'badge bg-negative-soft text-negative',
    'new' => 'badge bg-accent-soft text-accent-ink',
    'low' => 'badge bg-warning-soft text-warning',
    'muted' => 'badge bg-surface-2 text-ink-2',
    'dark' => 'badge bg-ink text-paper',
    default => 'badge bg-surface-2 text-ink',
} })]>{{ $slot }}</span>
