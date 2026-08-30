<x-layouts.app>
    <div class="mx-auto max-w-2xl px-4 py-14 sm:px-6 lg:px-8">
        <!-- Confirmation head -->
        <div class="text-center">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-positive-soft">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-8 w-8 text-positive" aria-hidden="true"><path fill-rule="evenodd" d="M19.916 4.626a.75.75 0 0 1 .208 1.04l-9 13.5a.75.75 0 0 1-1.154.114l-6-6a.75.75 0 0 1 1.06-1.06l5.353 5.353 8.493-12.739a.75.75 0 0 1 1.04-.208Z" clip-rule="evenodd"/></svg>
            </span>
            <p class="overline mt-6">Pesanan Diterima</p>
            <h1 class="mt-2 font-display text-4xl text-ink sm:text-5xl">Terima kasih, {{ $order->customer_name }}!</h1>
            <p class="mt-3 text-sm leading-relaxed text-ink-2">
                Pesanan <span class="font-bold text-ink">{{ $order->order_number }}</span> sudah kami terima
                @if ($order->customer_email)
                    — konfirmasi dikirim ke <span class="font-semibold text-ink">{{ $order->customer_email }}</span>.
                @endif
            </p>
        </div>

        <!-- Status + next steps -->
        <div class="card mt-10 overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line bg-surface px-6 py-5">
                <div>
                    <p class="overline">Status Pesanan</p>
                    <p class="mt-1 text-base font-bold text-ink">{{ $order->statusLabel() }}</p>
                </div>
                <div class="text-right">
                    <p class="overline">Pembayaran</p>
                    <p class="mt-1 text-base font-bold {{ $order->isPaid() ? 'text-positive' : 'text-warning' }}">
                        {{ $order->isPaid() ? 'Terkonfirmasi' : ($order->payment_method === 'midtrans' ? 'Menunggu pembayaran' : 'Menunggu transfer') }}
                    </p>
                </div>
            </div>

            <!-- Next steps -->
            <div class="border-b border-line px-6 py-5">
                <p class="overline">Langkah Selanjutnya</p>
                <ol class="mt-3 space-y-2.5 text-sm text-ink-2">
                    @if ($order->isPending() && $order->payment_method === 'manual_transfer')
                        <li class="flex gap-2.5"><span class="font-bold text-ink">1.</span> Lakukan transfer sesuai jumlah dan rekening di bawah.</li>
                        <li class="flex gap-2.5"><span class="font-bold text-ink">2.</span> Kami verifikasi pembayaran (maks. 1×24 jam kerja).</li>
                        <li class="flex gap-2.5"><span class="font-bold text-ink">3.</span> Pesanan dikemas dan dikirim beserta nomor resi.</li>
                    @elseif ($order->payment_method === 'midtrans')
                        <li class="flex gap-2.5"><span class="font-bold text-ink">1.</span> Selesaikan pembayaran melalui halaman Midtrans.</li>
                        <li class="flex gap-2.5"><span class="font-bold text-ink">2.</span> Status pesanan diperbarui otomatis setelah pembayaran terkonfirmasi.</li>
                        <li class="flex gap-2.5"><span class="font-bold text-ink">3.</span> Pesanan dikemas dan dikirim beserta nomor resi.</li>
                    @else
                        <li class="flex gap-2.5"><span class="font-bold text-ink">1.</span> Pembayaran terkonfirmasi — pesanan masuk antrean pengemasan.</li>
                        <li class="flex gap-2.5"><span class="font-bold text-ink">2.</span> Pantau status pengiriman lewat halaman lacak pesanan.</li>
                    @endif
                </ol>
            </div>

            <!-- Manual payment instructions -->
            @if ($order->isPending() && $order->payment_method === 'manual_transfer')
                <div class="border-b border-line bg-accent-soft px-6 py-5">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-accent-ink">Silakan transfer ke:</p>
                    <div class="mt-3 space-y-1.5">
                        @foreach (\App\Support\Settings::get('payments.bank_accounts', config('shop.bank_accounts')) as $account)
                            <p class="text-sm font-medium text-ink">{{ $account['bank'] }} · <span class="font-mono">{{ $account['number'] }}</span> · a.n. {{ $account['holder'] }}</p>
                        @endforeach
                    </div>
                    <p class="mt-3 text-sm text-ink-2">Jumlah transfer: <strong class="text-ink">{{ rupiah($order->total) }}</strong></p>
                </div>
            @elseif ($order->payment_method === 'midtrans' && $order->isPending())
                <div class="border-b border-line bg-surface-2 px-6 py-5 text-sm text-ink-2">
                    Pembayaran diproses melalui Midtrans. Status akan diperbarui otomatis — gunakan
                    <a href="{{ route('track-order') }}" wire:navigate class="font-semibold text-accent hover:underline">lacak pesanan</a> untuk cek terkini.
                </div>
            @endif

            <!-- Items -->
            <div class="border-b border-line px-6 py-5">
                <p class="overline">Ringkasan Item</p>
                <ul class="mt-3 divide-y divide-line">
                    @foreach ($order->items as $item)
                        <li class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                            <div class="w-12 shrink-0 overflow-hidden rounded-md bg-surface-2">
                                <img src="{{ $item->product_image }}" alt="" class="aspect-square w-full object-cover" loading="lazy" width="48" height="48">
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-ink">{{ $item->product_name }}</p>
                                <p class="text-xs text-ink-3">
                                    {{ $item->quantity }} × {{ rupiah($item->price) }}
                                    @if ($item->variant_label) · {{ $item->variant_label }} @endif
                                </p>
                            </div>
                            <p class="text-sm font-bold tabular-nums text-ink">{{ rupiah($item->subtotal) }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Shipping + totals -->
            <div class="grid gap-6 px-6 py-5 sm:grid-cols-2">
                <div>
                    <p class="overline">Dikirim Ke</p>
                    <address class="mt-2 text-sm not-italic leading-relaxed text-ink-2">
                        <span class="font-semibold text-ink">{{ $order->customer_name }}</span><br>
                        {{ $order->address }}<br>
                        {{ $order->city_name }}{{ $order->province_name ? ', '.$order->province_name : '' }}
                        @if ($order->postal_code) {{ $order->postal_code }} @endif<br>
                        {{ $order->customer_phone }}
                    </address>
                    <p class="mt-2 text-xs text-ink-3">
                        {{ strtoupper($order->shipping_courier ?? 'kurir') }} {{ $order->shipping_service }} · estimasi {{ $order->shipping_etd }} hari
                    </p>
                </div>

                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-ink-2">Subtotal</dt><dd class="font-medium tabular-nums">{{ rupiah($order->subtotal) }}</dd></div>
                    @if ($order->discount > 0)
                        <div class="flex justify-between"><dt class="text-ink-2">Diskon {{ $order->coupon_code }}</dt><dd class="font-medium tabular-nums text-positive">−{{ rupiah($order->discount) }}</dd></div>
                    @endif
                    <div class="flex justify-between"><dt class="text-ink-2">Ongkir ({{ $order->shipping_service }})</dt><dd class="font-medium tabular-nums">{{ rupiah($order->shipping_cost) }}</dd></div>
                    <div class="flex justify-between border-t border-line pt-2 text-base font-bold"><dt>Total</dt><dd class="tabular-nums">{{ rupiah($order->total) }}</dd></div>
                </dl>
            </div>
        </div>

        <!-- CTAs -->
        <div class="mt-8 grid gap-2 sm:grid-cols-3">
            <a href="{{ route('shop') }}" wire:navigate class="btn btn-accent">Lanjut Belanja</a>
            <a href="{{ route('track-order') }}" wire:navigate class="btn btn-outline">Lacak Pesanan</a>
            @auth
                <a href="{{ route('account.orders') }}" wire:navigate class="btn btn-outline">Pesanan Saya</a>
            @else
                <a href="{{ route('register') }}" wire:navigate class="btn btn-outline">Buat Akun</a>
            @endauth
        </div>
    </div>
</x-layouts.app>
