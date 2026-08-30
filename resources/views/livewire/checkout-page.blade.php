<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
    <header class="mb-10">
        <p class="overline">Checkout</p>
        <h1 class="mt-2 font-display text-4xl text-ink sm:text-5xl">Selesaikan pesanan</h1>
    </header>

    @error('stock')
        <div class="mb-6 rounded-md border border-negative/30 bg-negative-soft px-4 py-3 text-sm text-negative" role="alert">{{ $message }}</div>
    @enderror
    @error('order')
        <div class="mb-6 rounded-md border border-negative/30 bg-negative-soft px-4 py-3 text-sm text-negative" role="alert">{{ $message }}</div>
    @enderror
    @error('service')
        <div class="mb-6 rounded-md border border-negative/30 bg-negative-soft px-4 py-3 text-sm text-negative" role="alert">{{ $message }}</div>
    @enderror

    <form wire:submit="placeOrder" class="grid items-start gap-10 lg:grid-cols-[1fr_380px]">
        <!-- ===== Left: details ===== -->
        <div class="space-y-8">
            <!-- Contact -->
            <section class="card p-6">
                <h2 class="text-sm font-semibold text-ink">Kontak</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="customer_name" class="label">Nama Lengkap</label>
                        <input id="customer_name" type="text" wire:model="customer_name"
                               class="input @error('customer_name') input-error @enderror" autocomplete="name" required>
                        @error('customer_name') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="customer_email" class="label">Email</label>
                        <input id="customer_email" type="email" wire:model="customer_email"
                               class="input @error('customer_email') input-error @enderror" autocomplete="email" required>
                        @error('customer_email') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="customer_phone" class="label">Nomor Telepon</label>
                        <input id="customer_phone" type="tel" wire:model="customer_phone"
                               class="input @error('customer_phone') input-error @enderror" autocomplete="tel" required>
                        @error('customer_phone') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <!-- Address -->
            <section class="card p-6">
                <h2 class="text-sm font-semibold text-ink">Alamat Pengiriman</h2>

                @if ($savedAddresses->isNotEmpty())
                    <fieldset class="mt-4">
                        <legend class="label">Kirim ke alamat tersimpan</legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($savedAddresses as $address)
                                <label class="flex cursor-pointer items-start gap-3 rounded-md border p-3.5 transition-colors {{ $addressId === $address->id ? 'border-ink bg-surface-2' : 'border-line hover:border-line-strong' }}">
                                    <input type="radio" name="saved_address" value="{{ $address->id }}"
                                           wire:click="applyAddress({{ $address->id }})"
                                           @checked($addressId === $address->id)
                                           class="mt-0.5 h-4 w-4 accent-accent">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold text-ink">
                                            {{ $address->label }}
                                            @if ($address->is_default)
                                                <span class="badge ml-1 bg-accent-soft text-accent-ink">Utama</span>
                                            @endif
                                        </span>
                                        <span class="mt-0.5 block truncate text-xs text-ink-3">{{ $address->name }} · {{ $address->phone }}</span>
                                        <span class="mt-0.5 block text-xs leading-relaxed text-ink-3">{{ $address->address }}, {{ $address->city_name }}</span>
                                    </span>
                                </label>
                            @endforeach
                            <label class="flex cursor-pointer items-start gap-3 rounded-md border p-3.5 transition-colors {{ $addressId === null ? 'border-ink bg-surface-2' : 'border-line hover:border-line-strong' }}">
                                <input type="radio" name="saved_address" value="new"
                                       wire:click="useNewAddress"
                                       @checked($addressId === null)
                                       class="mt-0.5 h-4 w-4 accent-accent">
                                <span class="text-sm font-semibold text-ink">Alamat baru / lain</span>
                            </label>
                        </div>
                    </fieldset>
                @endif

                @if ($useApiShipping)
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="province_id" class="label">Provinsi</label>
                            <select id="province_id" wire:model="province_id"
                                    class="input @error('province_id') input-error @enderror">
                                <option value="">Pilih provinsi</option>
                                @foreach ($provinces as $province)
                                    <option value="{{ $province->id }}">{{ $province->name }}</option>
                                @endforeach
                            </select>
                            @error('province_id') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="city_id" class="label">Kota/Kabupaten</label>
                            <select id="city_id" wire:model="city_id"
                                    class="input @error('city_id') input-error @enderror" @disabled(! $province_id)>
                                <option value="">Pilih kota/kabupaten</option>
                                @foreach ($cities as $city)
                                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                                @endforeach
                            </select>
                            @error('city_id') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @else
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="province_name_manual" class="label">Provinsi</label>
                            <input id="province_name_manual" type="text" wire:model="province_name_manual"
                                   class="input @error('province_name_manual') input-error @enderror">
                            @error('province_name_manual') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="city_name_manual" class="label">Kota/Kabupaten</label>
                            <input id="city_name_manual" type="text" wire:model="city_name_manual"
                                   class="input @error('city_name_manual') input-error @enderror">
                            @error('city_name_manual') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif

                <div class="mt-4">
                    <label for="address" class="label">Alamat Lengkap</label>
                    <textarea id="address" rows="3" wire:model="address"
                              class="input @error('address') input-error @enderror" required
                              placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan"></textarea>
                    @error('address') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="postal_code" class="label">Kode Pos (opsional)</label>
                        <input id="postal_code" type="text" wire:model="postal_code"
                               class="input @error('postal_code') input-error @enderror" maxlength="10">
                        @error('postal_code') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="notes" class="label">Catatan Pesanan (opsional)</label>
                        <input id="notes" type="text" wire:model="notes"
                               class="input @error('notes') input-error @enderror" maxlength="500">
                        @error('notes') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <!-- Shipping -->
            <section class="card p-6">
                <h2 class="text-sm font-semibold text-ink">Pengiriman</h2>

                @if ($useApiShipping)
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach (config('shop.couriers') as $code)
                            <button type="button" wire:click="$set('courier', '{{ $code }}')"
                                    class="rounded-md border px-4 py-2 text-sm font-medium transition-colors @if ($courier === $code) border-ink bg-ink text-paper @else border-line-strong bg-surface text-ink hover:border-ink @endif"
                                    aria-pressed="{{ $courier === $code ? 'true' : 'false' }}">
                                {{ strtoupper($code) }}
                            </button>
                        @endforeach
                    </div>

                    <div class="mt-4 space-y-3" wire:loading.class="opacity-50" wire:target="updatedCityId, updatedCourier">
                        @foreach ($shippingOptions as $option)
                            <label class="flex cursor-pointer items-center justify-between gap-3 rounded-md border p-4 transition-colors {{ $service === $option['service'] ? 'border-ink bg-surface-2' : 'border-line hover:border-line-strong' }}">
                                <span class="flex items-center gap-3">
                                    <input type="radio" wire:model="service" value="{{ $option['service'] }}" class="h-4 w-4 accent-accent">
                                    <span>
                                        <span class="block text-sm font-semibold text-ink">{{ $option['service'] }} — {{ $option['description'] }}</span>
                                        <span class="block text-xs text-ink-3">Estimasi {{ $option['etd'] }} hari</span>
                                    </span>
                                </span>
                                <span class="text-sm font-bold tabular-nums text-ink">{{ rupiah($option['cost']) }}</span>
                            </label>
                        @endforeach

                        <p class="text-sm text-ink-3" wire:loading wire:target="updatedCityId, updatedCourier">Menghitung ongkir…</p>

                        @if ($useApiShipping && $city_id && $shippingOptions === [])
                            <p class="text-sm text-ink-3">Tidak ada layanan tersedia untuk kurir ini. Coba kurir lain.</p>
                        @endif
                    </div>
                @else
                    @foreach ($shippingOptions as $option)
                        <p class="mt-4 text-sm text-ink-2">
                            {{ $option['service'] }} — estimasi {{ $option['etd'] }} hari:
                            <strong class="text-ink">{{ rupiah($option['cost']) }}</strong>
                        </p>
                    @endforeach
                @endif
            </section>
        </div>

        <!-- ===== Right: summary ===== -->
        <aside class="h-fit lg:sticky lg:top-28">
            <div class="card p-6">
                <h2 class="text-sm font-semibold text-ink">Ringkasan Pesanan</h2>

                <ul class="mt-4 space-y-3 border-b border-line pb-4">
                    @foreach ($items as $item)
                        <li class="flex items-center gap-3">
                            <img src="{{ $item['product']->coverImage() }}" alt="" class="h-12 w-10 rounded border border-line object-cover" loading="lazy">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ink">{{ $item['product']->name }}</p>
                                <p class="text-xs text-ink-3">
                                    {{ $item['qty'] }} × {{ rupiah($item['price']) }}
                                    @if ($item['variant']) · {{ $item['variant']->label() }} @endif
                                </p>
                            </div>
                            <span class="text-sm font-semibold tabular-nums text-ink">{{ rupiah($item['subtotal']) }}</span>
                        </li>
                    @endforeach
                </ul>

                <!-- Coupon -->
                <div class="mt-4 border-b border-line pb-4">
                    @if ($coupon)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-ink-2">Kupon <strong class="text-ink">{{ $coupon->code }}</strong></span>
                            <button type="button" wire:click="removeCoupon" class="text-xs font-medium text-negative hover:underline">Hapus</button>
                        </div>
                    @else
                        <div class="flex gap-2">
                            <label for="couponCode" class="sr-only">Kode kupon</label>
                            <input id="couponCode" type="text" wire:model="couponCode" placeholder="Kode kupon" class="input flex-1">
                            <button type="button" wire:click="applyCoupon" class="btn btn-outline btn-sm shrink-0">Pakai</button>
                        </div>
                    @endif
                    @if ($couponMessage)
                        <p class="mt-2 text-sm {{ $couponSuccess ? 'text-positive' : 'text-negative' }}" role="status">{{ $couponMessage }}</p>
                    @endif
                </div>

                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink-2">Subtotal</dt>
                        <dd class="font-medium tabular-nums">{{ rupiah($subtotal) }}</dd>
                    </div>
                    @if ($discount > 0)
                        <div class="flex justify-between">
                            <dt class="text-ink-2">Diskon</dt>
                            <dd class="font-medium tabular-nums text-positive">−{{ rupiah($discount) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-ink-2">Ongkir</dt>
                        <dd class="font-medium tabular-nums">{{ $service ? rupiah(collect($shippingOptions)->firstWhere('service', $service)['cost'] ?? config('shop.flat_shipping_cost')) : '—' }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-line pt-3 text-base font-bold">
                        <dt>Total</dt>
                        <dd class="tabular-nums">{{ rupiah(max(0, $subtotal - $discount)) }}</dd>
                    </div>
                    <p class="text-xs text-ink-3">Total akhir termasuk ongkir dikonfirmasi saat pembayaran.</p>
                </dl>

                <button type="submit" wire:loading.attr="disabled" wire:target="placeOrder"
                        class="btn btn-accent btn-lg mt-6 w-full">
                    <span wire:loading.remove wire:target="placeOrder">Bayar Sekarang</span>
                    <span wire:loading wire:target="placeOrder">Memproses…</span>
                </button>
                <p class="mt-3 text-center text-xs text-ink-3">Dengan melanjutkan, kamu menyetujui syarat &amp; ketentuan toko.</p>
            </div>
        </aside>
    </form>
</div>
