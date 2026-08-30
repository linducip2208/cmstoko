<div
    x-data="{
        open: false,
        trapFocus(e) {
            if (e.shiftKey && $refs.lastFocus === document.activeElement) { e.preventDefault(); $refs.closeBtn.focus(); }
            else if (!e.shiftKey && $refs.firstFocus === document.activeElement) { e.preventDefault(); $refs.lastFocus.focus(); }
        }
    }"
    x-on:open-cart-drawer.window="
        open = true;
        $nextTick(() => $refs.closeBtn.focus());
    "
    x-on:keydown.escape.window="open = false"
    x-effect="document.body.classList.toggle('overflow-hidden', open)"
>
    <!-- Backdrop -->
    <div
        x-cloak x-show="open"
        x-on:click="open = false"
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-50 bg-ink/45 backdrop-blur-[2px]"
        aria-hidden="true"
    ></div>

    <!-- Panel -->
    <aside
        x-cloak x-show="open"
        role="dialog" aria-modal="true" aria-labelledby="cart-drawer-title"
        x-transition:enter="transition duration-300 ease-[cubic-bezier(0.32,0.72,0,1)]"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition duration-200 ease-in"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        @keydown.tab="trapFocus($event)"
        class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col bg-paper shadow-float"
    >
        <!-- Header -->
        <header class="flex items-center justify-between border-b border-line px-5 py-4 sm:px-6">
            <h2 id="cart-drawer-title" class="text-base font-semibold tracking-tight text-ink">
                Keranjang Belanja
                @if ($count > 0)
                    <span class="ml-1 text-sm font-medium text-ink-3">({{ $count }} produk)</span>
                @endif
            </h2>
            <button type="button" x-ref="closeBtn" x-on:click="open = false" class="icon-btn" aria-label="Tutup keranjang">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-5 w-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </header>

        <div wire:loading wire:target="increment, decrement, removeItem" class="skeleton h-0.5 w-full rounded-none"></div>

        <!-- Body -->
        @if ($items->isEmpty())
            <div class="flex flex-1 flex-col items-center justify-center px-6 py-16 text-center">
                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-surface-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.4" stroke="currentColor" class="h-7 w-7 text-ink-3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z"/></svg>
                </span>
                <p class="mt-5 text-base font-semibold text-ink">Keranjang masih kosong</p>
                <p class="mt-1.5 max-w-xs text-sm leading-relaxed text-ink-3">Jelajahi katalog dan temukan produk favoritmu.</p>
                <a href="{{ route('shop') }}" wire:navigate x-on:click="open = false" class="btn btn-primary mt-6">
                    Mulai Belanja
                </a>
            </div>
        @else
            <div class="flex-1 overflow-y-auto px-5 py-4 sm:px-6" x-ref="firstFocus" tabindex="-1">
                <ul class="space-y-4" aria-label="Item di keranjang">
                    @foreach ($items as $item)
                        <li class="flex gap-4 border-b border-line pb-4 last:border-0 last:pb-0">
                            <a href="{{ route('product.show', $item['product']->slug) }}" wire:navigate x-on:click="open = false" class="w-16 shrink-0 overflow-hidden rounded-md bg-surface-2 sm:w-20">
                                <img src="{{ $item['variant']?->image ?? $item['product']->coverImage() }}" alt="{{ $item['product']->name }}" class="aspect-square w-full object-cover" loading="lazy" width="80" height="80">
                            </a>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <a href="{{ route('product.show', $item['product']->slug) }}" wire:navigate x-on:click="open = false" class="line-clamp-2 text-sm font-semibold leading-snug text-ink hover:text-accent">
                                        {{ $item['product']->name }}
                                    </a>
                                    <button type="button" wire:click="removeItem('{{ $item['key'] }}')" wire:loading.attr="disabled" wire:target="removeItem"
                                            class="-m-1 shrink-0 rounded p-1 text-ink-3 transition-colors hover:text-negative"
                                            aria-label="Hapus {{ $item['product']->name }} dari keranjang">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                </div>

                                @if ($item['variant'])
                                    <p class="mt-0.5 text-xs text-ink-3">{{ $item['variant']->label() }}</p>
                                @endif

                                <div class="mt-2.5 flex items-center justify-between gap-3">
                                    <div class="flex items-center rounded-md border border-line-strong" role="group" aria-label="Ubah jumlah {{ $item['product']->name }}">
                                        <button type="button" wire:click="decrement('{{ $item['key'] }}')" wire:loading.attr="disabled" wire:target="decrement"
                                                class="flex h-8 w-8 items-center justify-center text-ink-2 transition-colors hover:text-ink disabled:opacity-40"
                                                aria-label="Kurangi jumlah {{ $item['product']->name }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5" aria-hidden="true"><path d="M3 10a.75.75 0 0 1 .75-.75h10.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 10Z"/></svg>
                                        </button>
                                        <span class="w-8 text-center text-sm font-semibold tabular-nums" aria-live="polite">{{ $item['qty'] }}</span>
                                        <button type="button" wire:click="increment('{{ $item['key'] }}')" wire:loading.attr="disabled" wire:target="increment"
                                                class="flex h-8 w-8 items-center justify-center text-ink-2 transition-colors hover:text-ink disabled:opacity-40"
                                                aria-label="Tambah jumlah {{ $item['product']->name }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/></svg>
                                        </button>
                                    </div>

                                    <div class="text-right">
                                        @php $original = (int) ($item['variant']->price ?? $item['product']->price); @endphp
                                        @if ($item['price'] < $original)
                                            <span class="block text-xs text-ink-3 line-through">{{ rupiah($original) }}</span>
                                        @endif
                                        <span class="text-sm font-bold tabular-nums text-ink">{{ rupiah($item['subtotal']) }}</span>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <!-- Coupon -->
                <div class="mt-5">
                    @if ($coupon)
                        <div class="flex items-center justify-between rounded-md bg-positive-soft px-4 py-3">
                            <span class="text-sm text-ink-2">Kupon <strong class="text-ink">{{ $coupon->code }}</strong> aktif</span>
                            <button type="button" wire:click="removeCoupon" class="text-xs font-semibold text-negative hover:underline">Hapus</button>
                        </div>
                    @else
                        <form wire:submit="applyCoupon" class="flex gap-2">
                            <label class="sr-only" for="drawer-coupon">Kode kupon</label>
                            <input id="drawer-coupon" type="text" wire:model="couponCode" placeholder="Punya kode kupon?" class="input flex-1 !py-2.5 text-sm">
                            <button type="submit" class="btn btn-outline btn-sm shrink-0">Pakai</button>
                        </form>
                    @endif
                    @if ($couponMessage)
                        <p class="mt-2 text-xs font-medium {{ $couponSuccess ? 'text-positive' : 'text-negative' }}" role="status">{{ $couponMessage }}</p>
                    @endif
                </div>
            </div>

            <!-- Footer -->
            <footer class="border-t border-line bg-surface px-5 py-4 sm:px-6">
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink-2">Subtotal</dt>
                        <dd class="font-semibold tabular-nums text-ink">{{ rupiah($subtotal) }}</dd>
                    </div>
                    @if ($discount > 0)
                        <div class="flex justify-between">
                            <dt class="text-ink-2">Hemat</dt>
                            <dd class="font-semibold tabular-nums text-positive">−{{ rupiah($discount) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between text-base font-bold">
                        <dt>Total</dt>
                        <dd class="tabular-nums">{{ rupiah(max(0, $subtotal - $discount)) }}</dd>
                    </div>
                    <p class="text-xs text-ink-3">Ongkir dihitung saat checkout.</p>
                </dl>

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <a href="{{ route('cart') }}" wire:navigate x-on:click="open = false" class="btn btn-outline" x-ref="lastFocus">
                        Lihat Keranjang
                    </a>
                    <a href="{{ route('checkout') }}" wire:navigate x-on:click="open = false" class="btn btn-accent">
                        Checkout
                    </a>
                </div>
            </footer>
        @endif
    </aside>
</div>
