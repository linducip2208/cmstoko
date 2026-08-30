<div class="border-t border-line pt-6">
    <!-- Price -->
    <div class="flex items-baseline gap-3">
        <span class="text-2xl font-bold tabular-nums text-ink">{{ rupiah($this->price) }}</span>
        @if ($this->hasDiscount)
            <span class="text-sm text-ink-3 line-through">{{ rupiah($this->regularPrice) }}</span>
        @endif
    </div>

    <!-- Variant selectors -->
    @if ($attributes->isNotEmpty())
        <div class="mt-6 space-y-5">
            @foreach ($attributes as $attribute)
                <fieldset>
                    <legend class="label mb-2">{{ $attribute->name }}</legend>
                    <div class="flex flex-wrap gap-2" role="group" aria-label="{{ $attribute->name }}">
                        @foreach ($attribute->options as $option)
                            <button type="button"
                                    wire:click="$set('selected.{{ $attribute->id }}', {{ $option->id }})"
                                    aria-pressed="{{ isset($selected[$attribute->id]) && $selected[$attribute->id] === $option->id ? 'true' : 'false' }}"
                                    class="min-w-11 rounded-md border px-4 py-2.5 text-sm font-medium transition-colors @if (isset($selected[$attribute->id]) && $selected[$attribute->id] === $option->id) border-ink bg-ink text-paper @else border-line-strong bg-surface text-ink hover:border-ink @endif">
                                @if ($attribute->type === 'color' && $option->color)
                                    <span class="inline-block h-3 w-3 rounded-full align-middle" style="background: {{ $option->color }}" aria-hidden="true"></span>
                                    <span class="ml-1.5 align-middle">{{ $option->label }}</span>
                                @else
                                    {{ $option->label }}
                                @endif
                            </button>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach

            @if ($variant)
                <p class="text-sm">
                    <span class="text-ink-3">SKU:</span>
                    <span class="font-mono font-medium text-ink">{{ $variant->sku ?? '—' }}</span>
                </p>
            @endif

            @error('variant')
                <p class="text-sm text-negative" role="alert">{{ $message }}</p>
            @enderror
        </div>
    @else
        <p class="mt-4 text-sm text-ink-2">SKU: <span class="font-mono font-medium text-ink">{{ $product->sku ?? '—' }}</span></p>
    @endif

    <!-- Stock -->
    <div class="mt-6">
        @if ($variant !== null || ! $product->isConfigurable())
            @if ($this->stock > 0)
                <p class="text-sm font-medium text-positive">{{ $this->stock <= 5 ? 'Tersisa '.$this->stock.' â€” segera checkout' : 'Tersedia' }}</p>
            @else
                <p class="text-sm font-medium text-negative">{{ $product->isConfigurable() ? 'Varian ini sedang habis' : 'Stok habis' }}</p>
            @endif
        @endif
    </div>

    <!-- Qty + CTA -->
    <div class="mt-5 flex flex-wrap items-center gap-3">
        <div class="inline-flex items-center rounded-md border border-line-strong bg-surface">
            <button type="button" wire:click="$set('qty', max(1, $qty - 1))"
                    class="flex h-11 w-10 items-center justify-center rounded-l-md text-lg text-ink-2 transition-colors hover:bg-surface-2 hover:text-ink"
                    aria-label="Kurangi jumlah">Ã¢Ë†â€™</button>
            <span class="min-w-10 text-center text-sm font-semibold tabular-nums" aria-live="polite">{{ $qty }}</span>
            <button type="button" wire:click="$set('qty', min(99, $qty + 1))"
                    class="flex h-11 w-10 items-center justify-center rounded-r-md text-lg text-ink-2 transition-colors hover:bg-surface-2 hover:text-ink"
                    aria-label="Tambah jumlah">+</button>
        </div>

        <button type="button" wire:click="addToCart" @disabled(! $product->inStock())
                class="btn btn-primary flex-1 sm:flex-none">
            {{ $product->inStock() ? 'Tambah ke Keranjang' : 'Stok Habis' }}
        </button>
    </div>

    @error('qty')
        <p class="mt-2 text-sm text-negative" role="alert">{{ $message }}</p>
    @enderror

    @if ($added)
        <p class="mt-3 text-sm text-positive" role="status">
            Ditambahkan ke keranjang Ã¢â‚¬â€
            <a href="{{ route('cart') }}" wire:navigate class="font-semibold underline">lihat keranjang</a>
        </p>
    @endif
</div>
