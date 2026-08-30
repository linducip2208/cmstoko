<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
    <header class="text-center">
        <p class="overline">Lacak Pesanan</p>
        <h1 class="mt-2 font-display text-4xl text-ink sm:text-5xl">Di mana pesananku?</h1>
        <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-ink-2">
            Masukkan nomor pesanan untuk melihat status terkini.
            @unless (auth()->check())
                Verifikasi email juga diperlukan untuk melindungi data pelanggan.
            @endunless
        </p>
    </header>

    <form wire:submit="search" class="card mt-8 p-5 sm:p-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="track-number" class="label">Nomor Pesanan</label>
                <input id="track-number" type="text" wire:model="number" placeholder="INV-20260830-XXXXXX"
                       class="input uppercase @error('number') input-error @enderror" autocomplete="off" required>
                @error('number') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
            </div>
            @unless (auth()->check())
                <div>
                    <label for="track-email" class="label">Email Pemesan</label>
                    <input id="track-email" type="email" wire:model="email" placeholder="nama@email.com"
                           class="input @error('email') input-error @enderror" autocomplete="email" required>
                    @error('email') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
                </div>
            @endunless
        </div>
        <button type="submit" wire:loading.attr="disabled" wire:target="search" class="btn btn-primary mt-4 w-full sm:w-auto">
            <span wire:loading.remove wire:target="search">Lacak Pesanan</span>
            <span wire:loading wire:target="search">Mencari…</span>
        </button>
    </form>

    @if ($searched)
        <div class="mt-8">
            @if ($order)
                <article class="card overflow-hidden">
                    @php
                        $tone = match (true) {
                            $order->status === App\Models\Order::STATUS_COMPLETED => 'new',
                            in_array($order->status, [App\Models\Order::STATUS_CANCELLED, App\Models\Order::STATUS_REFUNDED]) => 'sale',
                            $order->status === App\Models\Order::STATUS_PENDING => 'low',
                            default => 'default',
                        };
                    @endphp
                    <!-- Head -->
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-line bg-surface px-6 py-5">
                        <div>
                            <p class="overline">Nomor Pesanan</p>
                            <p class="mt-1 text-lg font-bold tracking-tight text-ink">{{ $order->order_number }}</p>
                            <p class="mt-0.5 text-xs text-ink-3">Dibuat {{ $order->created_at->translatedFormat('d M Y, H:i') }}</p>
                        </div>
                        <x-ui.badge :tone="$tone">{{ $order->statusLabel() }}</x-ui.badge>
                    </div>

                    @if ($order->status === App\Models\Order::STATUS_CANCELLED)
                        <div class="border-b border-line bg-negative-soft px-6 py-4 text-sm font-medium text-negative">
                            Pesanan ini dibatalkan. Stok dikembalikan dan tidak ada pembayaran yang diproses.
                        </div>
                    @elseif (! in_array($order->status, [App\Models\Order::STATUS_REFUNDED, App\Models\Order::STATUS_PARTIALLY_REFUNDED]))
                        <!-- Progress -->
                        <div class="border-b border-line px-6 py-6">
                            @php
                                $steps = ['Dipesan', 'Dibayar', 'Diproses', 'Dikirim', 'Selesai'];
                                $index = match (true) {
                                    $order->status === App\Models\Order::STATUS_PENDING => 0,
                                    $order->status === App\Models\Order::STATUS_PAID => 1,
                                    in_array($order->status, [App\Models\Order::STATUS_PROCESSING, App\Models\Order::STATUS_READY_TO_SHIP]) => 2,
                                    in_array($order->status, [App\Models\Order::STATUS_PARTIALLY_SHIPPED, App\Models\Order::STATUS_SHIPPED]) => 3,
                                    $order->status === App\Models\Order::STATUS_COMPLETED => 4,
                                    default => 0,
                                };
                            @endphp
                            <ol class="flex items-start" aria-label="Progres pesanan">
                                @foreach ($steps as $i => $label)
                                    <li class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                                        <div class="flex flex-col items-center gap-1.5">
                                            <span class="flex h-8 w-8 items-center justify-center rounded-full border text-xs font-bold transition-colors
                                                {{ $i <= $index ? 'border-ink bg-ink text-paper' : 'border-line-strong bg-surface text-ink-3' }}"
                                                aria-current="{{ $i === $index ? 'step' : 'false' }}">
                                                @if ($i < $index)
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                                                @else
                                                    {{ $i + 1 }}
                                                @endif
                                            </span>
                                            <span class="text-[11px] font-semibold uppercase tracking-wide {{ $i <= $index ? 'text-ink' : 'text-ink-3' }}">{{ $label }}</span>
                                        </div>
                                        @if (! $loop->last)
                                            <div class="mx-2 mb-6 h-0.5 flex-1 rounded {{ $i < $index ? 'bg-ink' : 'bg-line' }}"></div>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endif

                    <!-- Shipments -->
                    @if ($order->shipments->isNotEmpty())
                        <div class="border-b border-line px-6 py-5">
                            <p class="overline">Pengiriman</p>
                            <ul class="mt-3 space-y-3">
                                @foreach ($order->shipments as $shipment)
                                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-md bg-surface-2 px-4 py-3">
                                        <div class="text-sm">
                                            <span class="font-semibold text-ink">{{ strtoupper($shipment->courier ?? '-') }} {{ $shipment->service }}</span>
                                            @if ($shipment->tracking_number)
                                                <span class="block text-xs text-ink-2">No. resi: <span class="font-mono font-semibold text-ink">{{ $shipment->tracking_number }}</span></span>
                                            @endif
                                        </div>
                                        <div class="text-right text-xs text-ink-2">
                                            <span class="badge bg-surface text-ink">{{ [
                                                $shipment::STATUS_PENDING => 'Disiapkan',
                                                $shipment::STATUS_SHIPPED => 'Dikirim',
                                                $shipment::STATUS_IN_TRANSIT => 'Dalam Perjalanan',
                                                $shipment::STATUS_DELIVERED => 'Terkirim',
                                                $shipment::STATUS_CANCELLED => 'Dibatalkan',
                                            ][$shipment->status] ?? $shipment->status }}</span>
                                            @if ($shipment->shipped_at)
                                                <span class="mt-1 block">{{ $shipment->shipped_at->translatedFormat('d M Y, H:i') }}</span>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                            @if ($order->shipping_etd)
                                <p class="mt-3 text-xs text-ink-3">Estimasi tiba: {{ $order->shipping_etd }} hari setelah dikirim.</p>
                            @endif
                        </div>
                    @endif

                    <!-- Items -->
                    <div class="border-b border-line px-6 py-5">
                        <p class="overline">Item</p>
                        <ul class="mt-3 divide-y divide-line">
                            @foreach ($order->items as $item)
                                <li class="flex items-center justify-between gap-3 py-2.5 first:pt-0 last:pb-0">
                                    <span class="text-sm font-medium text-ink">
                                        {{ $item->product_name }}
                                        @if ($item->variant_label) <span class="text-xs text-ink-3">· {{ $item->variant_label }}</span> @endif
                                        <span class="text-ink-3">× {{ $item->quantity }}</span>
                                    </span>
                                    <span class="text-sm font-bold tabular-nums text-ink">{{ rupiah($item->subtotal) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Payment + totals -->
                    <div class="grid gap-6 px-6 py-5 sm:grid-cols-2">
                        <div>
                            <p class="overline">Pembayaran</p>
                            <p class="mt-2 text-sm text-ink-2">
                                {{ $order->payment_method === 'midtrans' ? 'Midtrans (payment gateway)' : 'Transfer Manual' }}
                                @if ($order->isPaid()) — <span class="font-semibold text-positive">terkonfirmasi</span>
                                @elseif ($order->isPending()) — <span class="font-semibold text-warning">menunggu pembayaran</span>
                                @endif
                            </p>

                            @if ($order->isPending() && $order->payment_method === 'manual_transfer')
                                <div class="mt-3 rounded-md bg-accent-soft p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-accent-ink">Transfer ke:</p>
                                    @foreach (\App\Support\Settings::get('payments.bank_accounts', config('shop.bank_accounts')) as $account)
                                            <p class="mt-1.5 text-sm font-medium text-ink">{{ $account['bank'] }} · {{ $account['number'] }} · a.n. {{ $account['holder'] }}</p>
                                    @endforeach
                                    <p class="mt-2 text-xs text-ink-2">Jumlah: <strong class="text-ink">{{ rupiah($order->total) }}</strong></p>
                                </div>
                            @endif
                        </div>

                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-ink-2">Subtotal</dt><dd class="font-medium tabular-nums">{{ rupiah($order->subtotal) }}</dd></div>
                            @if ($order->discount > 0)
                                <div class="flex justify-between"><dt class="text-ink-2">Diskon {{ $order->coupon_code }}</dt><dd class="font-medium tabular-nums text-positive">−{{ rupiah($order->discount) }}</dd></div>
                            @endif
                            <div class="flex justify-between"><dt class="text-ink-2">Ongkir</dt><dd class="font-medium tabular-nums">{{ rupiah($order->shipping_cost) }}</dd></div>
                            <div class="flex justify-between border-t border-line pt-2 text-base font-bold"><dt>Total</dt><dd class="tabular-nums">{{ rupiah($order->total) }}</dd></div>
                        </dl>
                    </div>
                </article>
            @else
                <div class="card flex flex-col items-center px-6 py-14 text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-surface-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-ink-3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    </span>
                    <h2 class="mt-4 text-lg font-bold text-ink">Pesanan tidak ditemukan</h2>
                    <p class="mt-1 max-w-sm text-sm leading-relaxed text-ink-2">
                        Periksa kembali nomor pesanan{{ auth()->check() ? '' : ' dan email pemesan' }}. Pastikan keduanya sesuai dengan konfirmasi pesanan.
                    </p>
                </div>
            @endif
        </div>
    @endif
</div>
