<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
    <header class="mb-10">
        <p class="overline">Keranjang</p>
        <h1 class="mt-2 font-display text-4xl text-ink sm:text-5xl">Belanja kamu</h1>
    </header>

    @if ($items->isEmpty())
        <x-ui.empty-state title="Keranjang masih kosong" description="Yuk cari sesuatu yang bagus untuk kamu.">
            <a href="{{ route('shop') }}" wire:navigate class="btn btn-primary">Jelajahi Katalog</a>
        </x-ui.empty-state>
    @else
        <div class="grid gap-10 lg:grid-cols-[1fr_360px]">
            <!-- Items -->
            <section aria-label="Item di keranjang">
                <ul class="divide-y divide-line border-y border-line">
                    @foreach ($items as $item)
                        <li class="flex gap-4 py-6 sm:gap-6">
                            <a href="{{ route('product.show', $item['product']->slug) }}" wire:navigate
                               class="h-28 w-24 shrink-0 overflow-hidden rounded-md border border-line bg-surface-2 sm:h-32 sm:w-28">
                                <img src="{{ $item['product']->coverImage() }}" alt="{{ $item['product']->name }}"
                                     class="h-full w-full object-cover" loading="lazy">
                            </a>

                            <div class="flex min-w-0 flex-1 flex-col">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        @if ($item['product']->brand)
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">{{ $item['product']->brand->name }}</p>
                                        @endif
                                        <a href="{{ route('product.show', $item['product']->slug) }}" wire:navigate
                                           class="mt-0.5 block truncate font-semibold text-ink hover:text-accent">
                                            {{ $item['product']->name }}
                                        </a>
                                        @if ($item['variant'])
                                            <p class="mt-1 text-xs text-ink-3">{{ $item['variant']->label() }}</p>
                                        @endif
                                    </div>
                                    <button type="button" wire:click="removeItem('{{ $item['key'] }}')"
                                            class="icon-btn h-9 w-9 shrink-0" aria-label="Hapus {{ $item['product']->name }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-4 w-4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>

                                <div class="mt-auto flex flex-wrap items-center justify-between gap-3 pt-3">
                                    <div class="inline-flex items-center rounded-md border border-line-strong bg-surface">
                                        <button type="button" wire:click="decrement('{{ $item['key'] }}')"
                                                class="flex h-9 w-9 items-center justify-center rounded-l-md text-ink-2 hover:bg-surface-2 hover:text-ink"
                                                aria-label="Kurangi jumlah {{ $item['product']->name }}">−</button>
                                        <span class="min-w-8 text-center text-sm font-semibold tabular-nums">{{ $item['qty'] }}</span>
                                        <button type="button" wire:click="increment('{{ $item['key'] }}')"
                                                class="flex h-9 w-9 items-center justify-center rounded-r-md text-ink-2 hover:bg-surface-2 hover:text-ink"
                                                aria-label="Tambah jumlah {{ $item['product']->name }}">+</button>
                                    </div>
                                    <p class="text-base font-bold tabular-nums text-ink">{{ rupiah($item['subtotal']) }}</p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-6">
                    <a href="{{ route('shop') }}" wire:navigate class="text-sm font-medium text-accent hover:text-accent-ink">&larr; Lanjut belanja</a>
                </div>
            </section>

            <!-- Summary -->
            <aside class="h-fit lg:sticky lg:top-28">
                <div class="card p-6">
                    <h2 class="text-sm font-semibold text-ink">Ringkasan</h2>

                    <form wire:submit="applyCoupon" class="mt-4 flex gap-2">
                        <label for="coupon" class="sr-only">Kode kupon</label>
                        <input id="coupon" type="text" wire:model="couponCode" placeholder="Kode kupon"
                               class="input flex-1">
                        <button type="submit" class="btn btn-outline btn-sm shrink-0">Pakai</button>
                    </form>

                    @if ($couponMessage)
                        <p class="mt-2 text-sm {{ $couponSuccess ? 'text-positive' : 'text-negative' }}" role="status">{{ $couponMessage }}</p>
                    @endif

                    <dl class="mt-5 space-y-3 border-t border-line pt-5 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-ink-2">Subtotal</dt>
                            <dd class="font-medium tabular-nums">{{ rupiah($subtotal) }}</dd>
                        </div>
                        @if ($coupon && $discount > 0)
                            <div class="flex justify-between">
                                <dt class="text-ink-2">Diskon ({{ $coupon->code }})</dt>
                                <dd class="font-medium tabular-nums text-positive">−{{ rupiah($discount) }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <dt class="text-ink-2">Ongkos kirim</dt>
                            <dd class="text-ink-3">dihitung saat checkout</dd>
                        </div>
                        <div class="flex justify-between border-t border-line pt-3 text-base font-bold">
                            <dt>Total</dt>
                            <dd class="tabular-nums">{{ rupiah($subtotal - $discount) }}</dd>
                        </div>
                    </dl>

                    <a href="{{ route('checkout') }}" wire:navigate class="btn btn-primary btn-lg mt-6 w-full">
                        Lanjut ke Pembayaran
                    </a>
                    <p class="mt-3 text-center text-xs text-ink-3">Pembayaran aman — data kartu tidak pernah menyentuh server kami.</p>
                </div>
            </aside>
        </div>
    @endif
</div>
