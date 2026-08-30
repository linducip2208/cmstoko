<div class="px-4 pb-24 sm:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="reveal py-10">
            <span class="text-[10px] font-semibold uppercase tracking-[0.25em] text-ink/40">Checkout</span>
            <h1 class="mt-3 text-4xl font-extrabold tracking-tight sm:text-5xl">Hampir selesai</h1>
        </div>

        <form wire:submit="placeOrder" class="grid items-start gap-8 lg:grid-cols-[1fr_400px]">
            <div class="space-y-6">
                <!-- customer -->
                <div class="reveal bezel">
                    <div class="bezel-inner p-7">
                        <h2 class="text-lg font-extrabold tracking-tight">Data Pembeli</h2>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.15em] text-ink/40">Nama Lengkap</label>
                                <input type="text" wire:model="customer_name" class="w-full rounded-2xl bg-ink/5 px-4 py-3 text-sm font-medium outline-none transition-shadow duration-500 focus:ring-2 focus:ring-ink/20">
                                @error('customer_name') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.15em] text-ink/40">Email</label>
                                <input type="email" wire:model="customer_email" class="w-full rounded-2xl bg-ink/5 px-4 py-3 text-sm font-medium outline-none transition-shadow duration-500 focus:ring-2 focus:ring-ink/20">
                                @error('customer_email') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.15em] text-ink/40">Telepon / WA</label>
                                <input type="tel" wire:model="customer_phone" class="w-full rounded-2xl bg-ink/5 px-4 py-3 text-sm font-medium outline-none transition-shadow duration-500 focus:ring-2 focus:ring-ink/20">
                                @error('customer_phone') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- address -->
                <div class="reveal bezel" style="--reveal-delay: 80ms">
                    <div class="bezel-inner p-7">
                        <h2 class="text-lg font-extrabold tracking-tight">Alamat Pengiriman</h2>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            @if ($useApiShipping)
                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.15em] text-ink/40">Provinsi</label>
                                    <select wire:model="province_id" class="w-full rounded-2xl bg-ink/5 px-4 py-3 text-sm font-medium outline-none transition-shadow duration-500 focus:ring-2 focus:ring-ink/20">
                                        <option value="">— Pilih Provinsi —</option>
                                        @foreach ($provinces as $province)
                                            <option value="{{ $province->id }}">{{ $province->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('province_id') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.15em] text-ink/40">Kota / Kabupaten</label>
                                    <select wire:model="city_id" class="w-full rounded-2xl bg-ink/5 px-4 py-3 text-sm font-medium outline-none transition-shadow duration-500 focus:ring-2 focus:ring-ink/20">
                                        <option value="">— Pilih Kota —</option>
                                        @foreach ($cities as $city)
                                            <option value="{{ $city->id }}">{{ $city->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('city_id') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                                </div>
                            @else
                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.15em] text-ink/40">Provinsi</label>
                                    <input type="text" wire:model="province_name_manual" class="w-full rounded-2xl bg-ink/5 px-4 py-3 text-sm font-medium outline-none transition-shadow duration-500 focus:ring-2 focus:ring-ink/20">
                                    @error('province_name_manual') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.15em] text-ink/40">Kota / Kabupaten</label>
                                    <input type="text" wire:model="city_name_manual" class="w-full rounded-2xl bg-ink/5 px-4 py-3 text-sm font-medium outline-none transition-shadow duration-500 focus:ring-2 focus:ring-ink/20">
                                    @error('city_name_manual') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                                </div>
                            @endif
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.15em] text-ink/40">Alamat Lengkap</label>
                                <textarea wire:model="address" rows="3" class="w-full rounded-2xl bg-ink/5 px-4 py-3 text-sm font-medium outline-none transition-shadow duration-500 focus:ring-2 focus:ring-ink/20" placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan"></textarea>
                                @error('address') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.15em] text-ink/40">Kode Pos</label>
                                <input type="text" wire:model="postal_code" class="w-full rounded-2xl bg-ink/5 px-4 py-3 text-sm font-medium outline-none transition-shadow duration-500 focus:ring-2 focus:ring-ink/20">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.15em] text-ink/40">Catatan (opsional)</label>
                                <input type="text" wire:model="notes" class="w-full rounded-2xl bg-ink/5 px-4 py-3 text-sm font-medium outline-none transition-shadow duration-500 focus:ring-2 focus:ring-ink/20">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- shipping -->
                <div class="reveal bezel" style="--reveal-delay: 120ms">
                    <div class="bezel-inner p-7">
                        <h2 class="text-lg font-extrabold tracking-tight">Pengiriman</h2>
                        @if ($useApiShipping)
                            <div class="mt-5 flex flex-wrap gap-2">
                                @foreach (config('shop.couriers') as $code)
                                    <button type="button" wire:click="$set('courier', '{{ $code }}')"
                                            class="rounded-full px-5 py-2.5 text-xs font-bold uppercase tracking-[0.1em] transition-all duration-500 ease-[cubic-bezier(0.32,0.72,0,1)] {{ $courier === $code ? 'bg-ink text-white' : 'bg-ink/5 text-ink/60 hover:bg-ink/10' }}">
                                        {{ $code }}
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-4 space-y-3" wire:loading.class="opacity-50">
                            @forelse ($shippingOptions as $option)
                                <label class="flex cursor-pointer items-center justify-between rounded-2xl bg-ink/5 px-5 py-4 transition-all duration-500 ease-[cubic-bezier(0.32,0.72,0,1)] hover:bg-ink/10 {{ $service === $option['service'] ? 'ring-2 ring-ink' : '' }}">
                                    <div class="flex items-center gap-4">
                                        <input type="radio" wire:model="service" value="{{ $option['service'] }}" class="h-4 w-4 accent-black">
                                        <div>
                                            <p class="text-sm font-bold">{{ $option['service'] }} <span class="font-medium text-ink/40">· {{ $option['etd'] }} hari</span></p>
                                            <p class="text-xs text-ink/40">{{ $option['description'] }}</p>
                                        </div>
                                    </div>
                                    <span class="text-sm font-extrabold">{{ rupiah($option['cost']) }}</span>
                                </label>
                            @empty
                                <p class="text-sm text-ink/40">
                                    {{ $useApiShipping ? 'Pilih kota tujuan untuk melihat tarif kurir.' : 'Tarif flat nasional akan diterapkan.' }}
                                </p>
                            @endforelse
                        </div>
                        @error('service') <p class="mt-2 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                        @error('stock') <p class="mt-2 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                @error('customer_name') @enderror
            </div>

            <!-- summary -->
            <div class="reveal lg:sticky lg:top-28" style="--reveal-delay: 160ms">
                <div class="bezel">
                    <div class="bezel-inner p-7">
                        <h2 class="text-lg font-extrabold tracking-tight">Ringkasan Pesanan</h2>

                        <div class="mt-5 max-h-64 space-y-4 overflow-y-auto pr-1">
                            @foreach ($items as $item)
                                <div class="flex items-center gap-3">
                                    <div class="aspect-square w-12 shrink-0 overflow-hidden rounded-2xl bg-paper">
                                        <img src="{{ $item['product']->coverImage() }}" alt="" class="h-full w-full object-cover">
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-bold">{{ $item['product']->name }}</p>
                                        <p class="text-[11px] text-ink/40">{{ $item['qty'] }} × {{ rupiah($item['price']) }}</p>
                                    </div>
                                    <p class="text-xs font-extrabold">{{ rupiah($item['subtotal']) }}</p>
                                </div>
                            @endforeach
                        </div>

                        <!-- coupon -->
                        <div class="mt-5 border-t border-ink/5 pt-5">
                            @if ($coupon)
                                <div class="flex items-center justify-between rounded-2xl bg-accent-soft px-4 py-3">
                                    <span class="text-sm font-bold text-accent">{{ $coupon->code }}</span>
                                    <button type="button" wire:click="removeCoupon" class="text-xs font-semibold text-ink/40 hover:text-ink">Hapus</button>
                                </div>
                            @else
                                <div class="flex gap-2">
                                    <input type="text" wire:model="couponCode" placeholder="Kode kupon"
                                           class="w-full rounded-full bg-ink/5 px-4 py-2.5 text-sm font-medium uppercase outline-none transition-shadow duration-500 focus:ring-2 focus:ring-ink/20 placeholder:normal-case placeholder:text-ink/35">
                                    <button type="button" wire:click="applyCoupon" class="btn-pill rounded-full bg-ink px-5 text-xs font-bold text-white">Pakai</button>
                                </div>
                                @if ($couponMessage)
                                    <p class="mt-2 text-xs font-medium {{ $couponSuccess ? 'text-emerald-600' : 'text-red-500' }}">{{ $couponMessage }}</p>
                                @endif
                            @endif
                        </div>

                        <dl class="mt-5 space-y-3 border-t border-ink/5 pt-5 text-sm">
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
                                <dt class="text-ink/50">Ongkir</dt>
                                <dd class="font-bold">
                                    @php $selectedOption = collect($shippingOptions)->firstWhere('service', $service); @endphp
                                    {{ $selectedOption ? rupiah($selectedOption['cost']) : '—' }}
                                </dd>
                            </div>
                            <div class="flex justify-between border-t border-ink/5 pt-3 text-lg">
                                <dt class="font-extrabold">Total</dt>
                                <dd class="font-extrabold">{{ rupiah(max(0, $subtotal - $discount + ($selectedOption['cost'] ?? 0))) }}</dd>
                            </div>
                        </dl>

                        <button type="submit" wire:loading.attr="disabled"
                                class="btn-pill group mt-7 flex w-full items-center justify-center gap-3 rounded-full bg-ink px-7 py-4 text-sm font-bold text-white hover:shadow-[0_20px_50px_-14px_rgba(16,16,20,0.6)] disabled:opacity-50">
                            <span wire:loading.remove wire:target="placeOrder">Bayar Sekarang</span>
                            <span wire:loading wire:target="placeOrder">Memproses…</span>
                            <span class="btn-orbit flex h-7 w-7 items-center justify-center rounded-full bg-white/10 text-xs" wire:loading.remove wire:target="placeOrder">↗</span>
                        </button>
                        <p class="mt-4 text-center text-[11px] leading-relaxed text-ink/35">
                            Dengan menekan "Bayar Sekarang", kamu menyetujui syarat & ketentuan {{ config('shop.name') }}.
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    @if (config('shop.midtrans.server_key'))
        <script src="{{ config('shop.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('shop.midtrans.client_key') }}"></script>
    @endif
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('open-snap', ({ token, orderNumber }) => {
                window.snap.pay(token, {
                    onSuccess: () => window.location.href = '/pesanan/' + orderNumber,
                    onPending: () => window.location.href = '/pesanan/' + orderNumber,
                    onError: () => window.location.href = '/pesanan/' + orderNumber,
                    onClose: () => window.location.href = '/pesanan/' + orderNumber,
                });
            });
        });
    </script>
@endpush
