<div class="inline-flex flex-wrap items-center gap-3">
    @if ($compact)
        <button type="button" wire:click="addToCart" class="btn-pill flex items-center gap-2 rounded-full bg-ink px-4 py-2 text-xs font-bold text-white hover:shadow-[0_12px_30px_-10px_rgba(16,16,20,0.5)]">
            + Keranjang
            <span class="btn-orbit flex h-5 w-5 items-center justify-center rounded-full bg-white/10 text-[10px]">↗</span>
        </button>
    @else
        <div class="flex items-center rounded-full bg-ink/5 p-1">
            <button type="button" wire:click="$set('qty', {{ max(1, $qty - 1) }})" class="flex h-9 w-9 items-center justify-center rounded-full text-lg font-bold text-ink/60 transition-colors duration-500 ease-[cubic-bezier(0.32,0.72,0,1)] hover:bg-white hover:text-ink">−</button>
            <span class="min-w-8 text-center text-sm font-bold tabular-nums">{{ $qty }}</span>
            <button type="button" wire:click="$set('qty', {{ min($stock, $qty + 1) }})" class="flex h-9 w-9 items-center justify-center rounded-full text-lg font-bold text-ink/60 transition-colors duration-500 ease-[cubic-bezier(0.32,0.72,0,1)] hover:bg-white hover:text-ink">+</button>
        </div>

        <button type="button" wire:click="addToCart" wire:loading.attr="disabled"
                class="btn-pill flex items-center gap-2.5 rounded-full bg-ink px-7 py-3 text-sm font-bold text-white hover:shadow-[0_16px_40px_-12px_rgba(16,16,20,0.55)] disabled:opacity-50">
            <span wire:loading.remove wire:target="addToCart">Tambah ke Keranjang</span>
            <span wire:loading wire:target="addToCart">Menambahkan…</span>
            <span class="btn-orbit flex h-7 w-7 items-center justify-center rounded-full bg-white/10 text-xs" wire:loading.remove wire:target="addToCart">↗</span>
        </button>
    @endif
</div>
