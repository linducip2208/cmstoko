@php
    $finalPrice = $product->effectivePrice();
@endphp
<a href="{{ route('product.show', $product->slug) }}" wire:navigate class="group block">
    <div class="bezel">
        <div class="bezel-inner flex flex-col">
            <div class="zoom-media relative aspect-[4/5] overflow-hidden bg-paper">
                <img src="{{ $product->coverImage() }}" alt="{{ $product->name }}" loading="lazy"
                     class="h-full w-full object-cover">
                @if ($product->hasDiscount())
                    <span class="absolute left-4 top-4 rounded-full bg-ink px-3 py-1 text-[10px] font-bold uppercase tracking-[0.15em] text-white">
                        -{{ $product->discountPercent() }}%
                    </span>
                @endif
                @if ($product->stock < 1)
                    <div class="absolute inset-0 flex items-center justify-center bg-white/70">
                        <span class="rounded-full bg-ink px-4 py-2 text-xs font-bold text-white">Stok Habis</span>
                    </div>
                @endif
            </div>
            <div class="flex flex-1 flex-col gap-1 p-5">
                <span class="text-[10px] font-semibold uppercase tracking-[0.2em] text-ink/40">{{ $product->category->name }}</span>
                <h3 class="text-[15px] font-bold leading-snug tracking-tight text-ink">{{ $product->name }}</h3>
                <div class="mt-auto flex items-baseline gap-2 pt-3">
                    <span class="text-base font-extrabold tracking-tight text-ink">{{ rupiah($finalPrice) }}</span>
                    @if ($product->hasDiscount())
                        <span class="text-xs font-medium text-ink/35 line-through">{{ rupiah($product->price) }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</a>
