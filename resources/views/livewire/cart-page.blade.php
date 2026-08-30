<div class="px-4 pb-24 sm:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="reveal py-10">
            <span class="text-[10px] font-semibold uppercase tracking-[0.25em] text-ink/40">Keranjang</span>
            <h1 class="mt-3 text-4xl font-extrabold tracking-tight sm:text-5xl">Belanjamu</h1>
        </div>

        @if ($items->isEmpty())
            <div class="reveal bezel mx-auto max-w-xl">
                <div class="bezel-inner flex flex-col items-center gap-4 p-16 text-center">
                    <span class="text-5xl">🛒</span>
                    <h2 class="text-2xl font-extrabold tracking-tight">Keranjang masih kosong</h2>
                    <p class="text-sm text-ink/45">Yuk isi dengan produk-produk terbaik kami.</p>
                    <a href="{{ route('shop') }}" wire:navigate class="btn-pill group mt-4 flex items-center gap-2 rounded-full bg-ink px-7 py-3.5 text-sm font-bold text-white">
                        Mulai Belanja
                        <span class="btn-orbit flex h-7 w-7 items-center justify-center rounded-full bg-white/10 text-xs">↗</span>
                    </a>
                </div>
            </div>
        @else
            <div class="grid items-start gap-8 lg:grid-cols-[1fr_380px]">
                <!-- items -->
                <div class="space-y-5">
                    @foreach ($items as $index => $item)
                        <div class="group reveal flex gap-5" style="--reveal-delay: {{ $index * 60 }}ms">
                            <div class="bezel w-full">
                                <div class="bezel-inner flex flex-col gap-4 p-4 sm:flex-row sm:items-center">
                                    <a href="{{ route('product.show', $item['product']->slug) }}" wire:navigate class="zoom-media aspect-square w-24 shrink-0 overflow-hidden rounded-3xl bg-paper sm:w-28">
                                        <img src="{{ $item['product']->coverImage() }}" alt="{{ $item['product']->name }}" class="h-full w-full object-cover">
                                    </a>
                                    <div class="min-w-0 flex-1">
                                        <span class="text-[10px] font-semibold uppercase tracking-[0.2em] text-ink/40">{{ $item['product']->category->name }}</span>
                                        <h3 class="truncate text-[15px] font-bold tracking-tight">{{ $item['product']->name }}</h3>
                                        <p class="mt-1 text-sm font-extrabold">{{ rupiah($item['price']) }}</p>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <div class="flex items-center rounded-full bg-ink/5 p-1">
                                            <button type="button" wire:click="decrement({{ $item['product']->id }})" class="flex h-8 w-8 items-center justify-center rounded-full font-bold text-ink/60 transition-colors duration-500 ease-[cubic-bezier(0.32,0.72,0,1)] hover:bg-white">−</button>
                                            <span class="min-w-7 text-center text-sm font-bold tabular-nums">{{ $item['qty'] }}</span>
                                            <button type="button" wire:click="increment({{ $item['product']->id }})" class="flex h-8 w-8 items-center justify-center rounded-full font-bold text-ink/60 transition-colors duration-500 ease-[cubic-bezier(0.32,0.72,0,1)] hover:bg-white">+</button>
                                        </div>
                                        <p class="hidden min-w-24 text-right text-sm font-extrabold sm:block">{{ rupiah($item['subtotal']) }}</p>
                                        <button type="button" wire:click="removeItem({{ $item['product']->id }})" wire:loading.attr="disabled"
                                                class="flex h-9 w-9 items-center justify-center rounded-full text-ink/30 transition-colors duration-500 ease-[cubic-bezier(0.32,0.72,0,1)] hover:bg-red-50 hover:text-red-500" aria-label="Hapus">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.4" stroke="currentColor" class="h-4.5 w-4.5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <a href="{{ route('shop') }}" wire:navigate class="inline-flex items-center gap-2 pt-2 text-sm font-semibold text-ink/45 transition-colors duration-500 hover:text-ink">← Tambah produk lain</a>
                </div>

                <!-- summary -->
                <div class="reveal lg:sticky lg:top-28" style="--reveal-delay: 150ms">
                    <div class="bezel">
                        <div class="bezel-inner p-7">
                            <h2 class="text-lg font-extrabold tracking-tight">Ringkasan</h2>

                            <!-- coupon -->
                            <div class="mt-5">
                                @if ($coupon)
                                    <div class="flex items-center justify-between rounded-2xl bg-accent-soft px-4 py-3">
                                        <span class="text-sm font-bold text-accent">{{ $coupon->code }}</span>
                                        <button type="button" wire:click="removeCoupon" class="text-xs font-semibold text-ink/40 hover:text-ink">Hapus</button>
                                    </div>
                                @else
                                    <form wire:submit="applyCoupon" class="flex gap-2">
                                        <input type="text" wire:model="couponCode" placeholder="Kode kupon"
                                               class="w-full rounded-full bg-ink/5 px-4 py-2.5 text-sm font-medium uppercase outline-none transition-shadow duration-500 focus:ring-2 focus:ring-ink/20 placeholder:normal-case placeholder:text-ink/35">
                                        <button type="submit" class="btn-pill rounded-full bg-ink px-5 text-xs font-bold text-white">Pakai</button>
                                    </form>
                                    @if ($couponMessage)
                                        <p class="mt-2 text-xs font-medium {{ $couponSuccess ? 'text-emerald-600' : 'text-red-500' }}">{{ $couponMessage }}</p>
                                    @endif
                                @endif
                            </div>

                            <dl class="mt-6 space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-ink/50">Subtotal</dt>
                                    <dd class="font-bold">{{ rupiah($subtotal) }}</dd>
                                </div>
                                @if ($discount > 0)
                                    <div class="flex justify-between">
                                        <dt class="text-ink/50">Diskon</dt>
                                        <dd class="font-bold text-emerald-600">−{{ rupiah($discount) }}</dd>
                                    </div>
                                @endif
                                <div class="flex justify-between">
                                    <dt class="text-ink/50">Berat total</dt>
                                    <dd class="font-bold">{{ number_format($weight, 0, ',', '.') }} g</dd>
                                </div>
                                <div class="flex justify-between border-t border-ink/5 pt-3 text-base">
                                    <dt class="font-bold">Ongkir</dt>
                                    <dd class="text-xs text-ink/40">dihitung saat checkout</dd>
                                </div>
                            </dl>

                            <a href="{{ route('checkout') }}" wire:navigate class="btn-pill group mt-7 flex w-full items-center justify-center gap-3 rounded-full bg-ink px-7 py-4 text-sm font-bold text-white hover:shadow-[0_20px_50px_-14px_rgba(16,16,20,0.6)]">
                                Lanjut ke Checkout
                                <span class="btn-orbit flex h-7 w-7 items-center justify-center rounded-full bg-white/10 text-xs">↗</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
