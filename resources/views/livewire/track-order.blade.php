<div class="px-4 pb-24 sm:px-8">
    <div class="mx-auto max-w-3xl">
        <div class="reveal py-10 text-center">
            <span class="text-[10px] font-semibold uppercase tracking-[0.25em] text-ink/40">Lacak Pesanan</span>
            <h1 class="mt-3 text-4xl font-extrabold tracking-tight sm:text-5xl">Di mana pesananku?</h1>
            <p class="mt-4 text-sm text-ink/45">Masukkan nomor pesanan (contoh: INV-20260830-ABC123) untuk melihat statusnya.</p>
        </div>

        <form wire:submit="search" class="reveal nav-pill flex gap-2 rounded-full p-2">
            <input type="text" wire:model="number" placeholder="INV-…"
                   class="w-full rounded-full bg-transparent px-5 py-3 text-sm font-bold uppercase tracking-wide outline-none placeholder:font-medium placeholder:normal-case placeholder:tracking-normal placeholder:text-ink/35">
            <button type="submit" wire:loading.attr="disabled"
                    class="btn-pill group flex shrink-0 items-center gap-2 rounded-full bg-ink px-6 text-sm font-bold text-white disabled:opacity-50">
                <span wire:loading.remove wire:target="search">Cari</span>
                <span wire:loading wire:target="search">Mencari…</span>
            </button>
        </form>

        @if ($searched)
            <div class="reveal mt-10">
                @if ($order)
                    <div class="bezel">
                        <div class="bezel-inner p-8">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.25em] text-ink/40">Nomor Pesanan</p>
                                    <p class="mt-1 text-xl font-extrabold tracking-tight">{{ $order->order_number }}</p>
                                </div>
                                <span class="rounded-full px-4 py-2 text-xs font-bold {{ [
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'paid' => 'bg-indigo-100 text-indigo-700',
                                    'processing' => 'bg-sky-100 text-sky-700',
                                    'shipped' => 'bg-sky-100 text-sky-700',
                                    'completed' => 'bg-emerald-100 text-emerald-700',
                                    'cancelled' => 'bg-red-100 text-red-600',
                                ][$order->status] ?? 'bg-ink/10' }}">{{ $order->statusLabel() }}</span>
                            </div>

                            <!-- timeline -->
                            <div class="mt-8 flex items-center">
                                @foreach (['pending', 'paid', 'processing', 'shipped', 'completed'] as $i => $step)
                                    @php
                                        $currentIndex = array_search($order->status, ['pending', 'paid', 'processing', 'shipped', 'completed']);
                                        $done = $currentIndex !== false && $i <= $currentIndex;
                                    @endphp
                                    <div class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                                        <div class="flex flex-col items-center">
                                            <span class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold transition-colors duration-500 {{ $done ? 'bg-ink text-white' : 'bg-ink/5 text-ink/30' }}">
                                                {{ $i + 1 }}
                                            </span>
                                            <span class="mt-2 hidden text-[10px] font-bold uppercase tracking-[0.1em] text-ink/40 sm:block">{{ ['Pesanan', 'Dibayar', 'Diproses', 'Dikirim', 'Selesai'][$i] }}</span>
                                        </div>
                                        @if (! $loop->last)
                                            <div class="mx-2 h-[2px] flex-1 rounded {{ $done ? 'bg-ink' : 'bg-ink/10' }}"></div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-8 space-y-3 border-t border-ink/5 pt-6">
                                @foreach ($order->items as $item)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-medium">{{ $item->product_name }} <span class="text-ink/40">× {{ $item->quantity }}</span></span>
                                        <span class="font-bold">{{ rupiah($item->subtotal) }}</span>
                                    </div>
                                @endforeach
                                <div class="flex justify-between border-t border-ink/5 pt-3 text-base">
                                    <span class="font-extrabold">Total</span>
                                    <span class="font-extrabold">{{ rupiah($order->total) }}</span>
                                </div>
                            </div>

                            @if ($order->isPending() && $order->payment_method === 'manual_transfer')
                                <div class="mt-6 rounded-2xl bg-accent-soft p-5">
                                    <p class="text-xs font-bold uppercase tracking-[0.15em] text-accent">Instruksi Pembayaran</p>
                                    @foreach (config('shop.bank_accounts') as $account)
                                        <p class="mt-2 text-sm font-medium">{{ $account['bank'] }} · {{ $account['number'] }} · {{ $account['holder'] }}</p>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="bezel mt-10">
                        <div class="bezel-inner p-10 text-center">
                            <p class="text-2xl font-extrabold tracking-tight text-ink/60">Pesanan tidak ditemukan</p>
                            <p class="mt-2 text-sm text-ink/40">Periksa kembali nomor pesanamu.</p>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
