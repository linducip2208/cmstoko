@props(['product'])
@php
    $finalPrice = $product->effectivePrice();
    $secondImage = $product->secondImage();
    $lowStock = ! $product->isConfigurable() && $product->stock > 0 && $product->stock <= 5;
@endphp
<a href="{{ route('product.show', $product->slug) }}" wire:navigate
   class="group relative flex flex-col overflow-hidden rounded-lg border border-line bg-surface transition-shadow duration-300 hover:shadow-card">
    <!-- Media -->
    <div class="zoom-media relative aspect-[4/5] overflow-hidden bg-surface-2">
        <img src="{{ $product->coverImage() }}" alt="{{ $product->name }}" loading="lazy"
             class="h-full w-full object-cover transition-opacity duration-300 {{ $secondImage ? 'group-hover:opacity-0' : '' }}">
        @if ($secondImage)
            <img src="{{ $secondImage }}" alt="" loading="lazy" aria-hidden="true"
                 class="absolute inset-0 h-full w-full scale-105 object-cover opacity-0 transition-opacity duration-300 group-hover:opacity-100">
        @endif

        <div class="absolute left-3 top-3 flex flex-col gap-1.5">
            @if ($product->hasDiscount())
                <span class="badge bg-negative-soft text-negative">−{{ $product->discountPercent() }}%</span>
            @endif
            @if ($product->is_featured)
                <span class="badge bg-ink/85 text-paper">Unggulan</span>
            @endif
        </div>

        @if ($product->isConfigurable())
            <span class="absolute bottom-3 left-3 badge bg-surface/90 text-ink-2">{{ $product->variants->count() }} pilihan</span>
        @endif

        @if (! $product->inStock())
            <div class="absolute inset-0 flex items-end justify-center bg-surface/70 p-4">
                <span class="badge bg-ink text-paper">Stok Habis</span>
            </div>
        @endif
    </div>

    <!-- Body -->
    <div class="flex flex-1 flex-col gap-1.5 p-4">
        @if ($product->brand)
            <span class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">{{ $product->brand->name }}</span>
        @endif
        <h3 class="text-[15px] font-semibold leading-snug text-ink">{{ $product->name }}</h3>

        <div class="mt-auto flex items-baseline gap-2 pt-2">
            <x-ui.money :amount="$finalPrice" class="text-[15px] font-bold text-ink" />
            @if ($product->hasDiscount())
                <span class="text-xs text-ink-3 line-through">{{ rupiah($product->price) }}</span>
            @endif
        </div>

        @if ($lowStock)
            <span class="text-xs font-medium text-warning">Tersisa {{ $product->stock }}</span>
        @endif
    </div>
</a>
