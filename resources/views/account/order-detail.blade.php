<x-layouts.account>
    <x-slot name="title">Pesanan {{ $order->order_number }}</x-slot>

    <a href="{{ route('account.orders') }}" wire:navigate class="mb-6 inline-flex items-center gap-1 text-sm text-ink-2 hover:text-ink">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path fill-rule="evenodd" d="M11.78 4.72a.75.75 0 0 1 0 1.06L8.06 9.5h7.69a.75.75 0 0 1 0 1.5H8.06l3.72 3.72a.75.75 0 1 1-1.06 1.06L5.47 10.53a.75.75 0 0 1 0-1.06l5.25-5.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd"/></svg>
        Semua pesanan
    </a>

    <header class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="font-mono text-2xl font-semibold text-ink">{{ $order->order_number }}</h1>
            <p class="mt-1 text-sm text-ink-3">{{ $order->created_at->translatedFormat('d F Y, H:i') }}</p>
        </div>
        <x-ui.badge :tone="match ($order->status) {
            App\Models\Order::STATUS_COMPLETED, App\Models\Order::STATUS_PAID => 'new',
            App\Models\Order::STATUS_CANCELLED => 'sale',
            default => 'muted',
        }" class="text-xs">{{ $order->statusLabel() }}</x-ui.badge>
    </header>

    @if (session('status'))
        <div class="mb-6 rounded-md border border-positive/30 bg-positive-soft px-4 py-3 text-sm text-positive" role="status">{{ session('status') }}</div>
    @endif

    <div class="grid gap-8 lg:grid-cols-[1fr_320px]">
        <div class="space-y-8">
            <!-- Items -->
            <section class="card overflow-hidden">
                <h2 class="border-b border-line px-5 py-4 text-sm font-semibold text-ink">Produk</h2>
                <ul class="divide-y divide-line">
                    @foreach ($order->items as $item)
                        <li class="flex gap-4 p-5">
                            <img src="{{ $item->product_image ?? '/images/placeholder.svg' }}" alt="" class="h-20 w-16 rounded-md border border-line object-cover">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-ink">{{ $item->product_name }}</p>
                                @if ($item->variant_label)
                                    <p class="mt-0.5 text-xs text-ink-3">{{ $item->variant_label }}</p>
                                @endif
                                <p class="mt-1 text-sm text-ink-2">{{ $item->quantity }} × {{ rupiah($item->price) }}</p>

                                @if ($item->product && $order->status !== App\Models\Order::STATUS_PENDING && ! in_array($item->id, $reviewedItemIds))
                                    <details class="mt-3">
                                        <summary class="cursor-pointer text-sm font-medium text-accent hover:text-accent-ink">Tulis ulasan</summary>
                                        <form method="POST" action="{{ route('account.orders.review', $order->order_number) }}" class="mt-3 space-y-3">
                                            @csrf
                                            <input type="hidden" name="order_item_id" value="{{ $item->id }}">
                                            <div>
                                                <label class="label" for="rating-{{ $item->id }}">Rating</label>
                                                <select id="rating-{{ $item->id }}" name="rating" class="input">
                                                    @for ($i = 5; $i >= 1; $i--)
                                                        <option value="{{ $i }}">{{ $i }} bintang</option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div>
                                                <label class="label" for="review-title-{{ $item->id }}">Judul (opsional)</label>
                                                <input id="review-title-{{ $item->id }}" type="text" name="title" class="input" maxlength="120">
                                            </div>
                                            <div>
                                                <label class="label" for="review-content-{{ $item->id }}">Ulasan</label>
                                                <textarea id="review-content-{{ $item->id }}" name="content" class="input" rows="3" required maxlength="2000"></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-outline btn-sm">Kirim Ulasan</button>
                                        </form>
                                    </details>
                                @elseif (in_array($item->id, $reviewedItemIds))
                                    <p class="mt-2 text-xs text-ink-3">Ulasan sudah dibuat untuk item ini.</p>
                                @endif
                            </div>
                            <p class="whitespace-nowrap font-semibold text-ink">{{ rupiah($item->subtotal) }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>

            <!-- Tracking timeline -->
            <section class="card p-5">
                <h2 class="text-sm font-semibold text-ink">Linimasa</h2>
                <ol class="mt-4 space-y-4">
                    @forelse ($order->histories as $history)
                        <li class="flex gap-3">
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $loop->last ? 'bg-accent' : 'bg-line-strong' }}"></span>
                            <div>
                                <p class="text-sm font-medium text-ink">{{ $statuses[$history->to] ?? $history->to }}</p>
                                <p class="text-xs text-ink-3">{{ $history->created_at->translatedFormat('d M Y, H:i') }} @if ($history->note) — {{ $history->note }} @endif</p>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-ink-3">Belum ada aktivitas.</li>
                    @endforelse
                </ol>
            </section>

            @if ($order->shipments->isNotEmpty())
                <section class="card overflow-hidden">
                    <h2 class="border-b border-line px-5 py-4 text-sm font-semibold text-ink">Pengiriman</h2>
                    <ul class="divide-y divide-line">
                        @foreach ($order->shipments as $shipment)
                            <li class="flex flex-wrap items-center justify-between gap-2 p-5">
                                <div>
                                    <p class="font-mono text-sm text-ink">{{ $shipment->shipment_number }}</p>
                                    <p class="text-xs text-ink-3">{{ strtoupper($shipment->courier ?? '') }} · Resi: {{ $shipment->tracking_number ?? '—' }}</p>
                                </div>
                                <x-ui.badge tone="muted">{{ $shipment->status }}</x-ui.badge>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <!-- Sidebar -->
        <aside class="space-y-6">
            <section class="card p-5">
                <h2 class="text-sm font-semibold text-ink">Ringkasan Pembayaran</h2>
                <dl class="mt-4 space-y-2.5 text-sm">
                    <div class="flex justify-between"><dt class="text-ink-2">Subtotal</dt><dd class="font-medium">{{ rupiah($order->subtotal) }}</dd></div>
                    @if ($order->discount > 0)
                        <div class="flex justify-between"><dt class="text-ink-2">Diskon @if ($order->coupon_code)({{ $order->coupon_code }})@endif</dt><dd class="font-medium text-positive">−{{ rupiah($order->discount) }}</dd></div>
                    @endif
                    <div class="flex justify-between"><dt class="text-ink-2">Ongkir</dt><dd class="font-medium">{{ rupiah($order->shipping_cost) }}</dd></div>
                    <div class="flex justify-between border-t border-line pt-2.5 text-base font-bold"><dt>Total</dt><dd>{{ rupiah($order->total) }}</dd></div>
                </dl>
            </section>

            <section class="card p-5">
                <h2 class="text-sm font-semibold text-ink">Alamat Pengiriman</h2>
                <address class="mt-3 text-sm not-italic leading-relaxed text-ink-2">
                    <span class="font-medium text-ink">{{ $order->customer_name }}</span><br>
                    {{ $order->address }}<br>
                    {{ $order->city_name }}, {{ $order->province_name }} {{ $order->postal_code }}<br>
                    {{ $order->customer_phone }}
                </address>
            </section>

            @if ($returnable)
                <a href="{{ route('account.returns') }}" wire:navigate class="btn btn-outline w-full">Ajukan Pengembalian</a>
            @endif
        </aside>
    </div>
</x-layouts.account>
