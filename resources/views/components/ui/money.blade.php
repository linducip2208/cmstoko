@props(['amount'])
<span {{ $attributes->merge(['class' => 'tabular-nums '.($class ?? '')]) }}>{{ rupiah($amount) }}</span>
