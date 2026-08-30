<x-layouts.app>
<div class="px-4 pb-24 sm:px-8">
    <div class="mx-auto max-w-2xl">
        <div class="reveal py-10 text-center">
            <span class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 text-4xl">✓</span>
            <h1 class="mt-8 text-4xl font-extrabold tracking-tight sm:text-5xl">Pesanan diterima!</h1>
            <p class="mt-4 text-sm leading-relaxed text-ink/50">
                Terima kasih, {{ $order->customer_name }}. Pesananmu
                <span class="font-bold text-ink">{{ $order->order_number }}</span> sudah kami terima.
            </p>
        </div>

        <div class="reveal bezel" style="--reveal-delay: 120ms">
            <div class="bezel-inner p-8">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-[0.2em] text-ink/40">Status</span>
                    <span class="rounded-full bg-amber-100 px-4 py-2 text-xs font-bold text-amber-700">{{ $order->statusLabel() }}</span>
                </div>

                <div class="mt-6 space-y-3 border-t border-ink/5 pt-6">
                    @foreach ($order->items as $item)
                        <div class="flex items-center gap-3">
                            <div class="aspect-square w-12 shrink-0 overflow-hidden rounded-2xl bg-paper">
                                <img src="{{ $item->product_image }}" alt="" class="h-full w-full object-cover">
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold">{{ $item->product_name }}</p>
                                <p class="text-xs text-ink/40">{{ $item->quantity }} × {{ rupiah($item->price) }}</p>
                            </div>
                            <p class="text-sm font-extrabold">{{ rupiah($item->subtotal) }}</p>
                        </div>
                    @endforeach
                </div>

                <dl class="mt-6 space-y-2 border-t border-ink/5 pt-6 text-sm">
                    <div class="flex justify-between"><dt class="text-ink/50">Subtotal</dt><dd class="font-bold">{{ rupiah($order->subtotal) }}</dd></div>
                    @if ($order->discount > 0)
                        <div class="flex justify-between"><dt class="text-ink/50">Diskon {{ $order->coupon_code }}</dt><dd class="font-bold text-emerald-600">−{{ rupiah($order->discount) }}</dd></div>
                    @endif
                    <div class="flex justify-between"><dt class="text-ink/50">Ongkir ({{ $order->shipping_service }})</dt><dd class="font-bold">{{ rupiah($order->shipping_cost) }}</dd></div>
                    <div class="flex justify-between pt-2 text-lg"><dt class="font-extrabold">Total</dt><dd class="font-extrabold">{{ rupiah($order->total) }}</dd></div>
                </dl>

                @if ($order->payment_method === 'manual_transfer' && $order->isPending())
                    <div class="mt-6 rounded-2xl bg-accent-soft p-5">
                        <p class="text-xs font-bold uppercase tracking-[0.15em] text-accent">Silakan transfer ke:</p>
                        @foreach (config('shop.bank_accounts') as $account)
                            <p class="mt-2 text-sm font-medium">{{ $account['bank'] }} · {{ $account['number'] }} · a.n. {{ $account['holder'] }}</p>
                        @endforeach
                        <p class="mt-3 text-xs text-ink/50">Jumlah: <span class="font-bold text-ink">{{ rupiah($order->total) }}</span></p>
                    </div>
                @elseif ($order->payment_method === 'midtrans')
                    <div class="mt-6 rounded-2xl bg-ink/5 p-5 text-sm text-ink/60">
                        Pembayaran diproses melalui Midtrans. Kamu akan diarahkan ke halaman pembayaran.
                        @if ($order->isPending())
                            Belum bayar? <a href="{{ route('track-order') }}" wire:navigate class="font-bold text-accent">Lacak pesanan</a> untuk info lanjut.
                        @endif
                    </div>
                @endif

                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('shop') }}" wire:navigate class="btn-pill group flex items-center gap-2 rounded-full bg-ink px-7 py-3.5 text-sm font-bold text-white">
                        Lanjut Belanja
                        <span class="btn-orbit flex h-7 w-7 items-center justify-center rounded-full bg-white/10 text-xs">↗</span>
                    </a>
                    <a href="{{ route('track-order') }}" wire:navigate class="btn-pill rounded-full border border-ink/15 px-7 py-3.5 text-sm font-semibold">Lacak Pesanan</a>
                </div>
            </div>
        </div>
    </div>
</div>

</x-layouts.app>