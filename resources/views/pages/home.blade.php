<x-layouts.app>
<div>
    <!-- ===== HERO : Editorial Split ===== -->
    <section class="px-4 pb-20 pt-10 sm:px-8 sm:pb-28">
        <div class="mx-auto grid max-w-7xl items-center gap-16 lg:grid-cols-2">
            <div class="reveal">
                <span class="inline-flex items-center gap-2 rounded-full border border-ink/10 bg-white px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.25em] text-ink/60 shadow-press">
                    <span class="h-1.5 w-1.5 rounded-full bg-accent"></span>
                    Koleksi Terbaru
                </span>
                <h1 class="mt-6 text-5xl font-extrabold leading-[1.02] tracking-tight text-ink sm:text-6xl lg:text-7xl">
                    Belanja<br>
                    <span class="text-ink/30">tanpa kompromi.</span>
                </h1>
                <p class="mt-6 max-w-md text-base leading-relaxed text-ink/55 sm:text-lg">
                    Produk pilihan dengan kualitas premium, pengiriman cepat, dan pembayaran aman. Sekali coba, pasti balik lagi.
                </p>
                <div class="mt-10 flex flex-wrap items-center gap-4">
                    <a href="{{ route('shop') }}" wire:navigate class="btn-pill group flex items-center gap-3 rounded-full bg-ink px-7 py-4 text-sm font-bold text-white hover:shadow-[0_20px_50px_-14px_rgba(16,16,20,0.6)]">
                        Jelajahi Katalog
                        <span class="btn-orbit flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-sm">↗</span>
                    </a>
                    <a href="{{ route('track-order') }}" wire:navigate class="btn-pill rounded-full border border-ink/15 bg-white px-7 py-4 text-sm font-semibold text-ink hover:border-ink/30">
                        Lacak Pesanan
                    </a>
                </div>

                <div class="mt-14 flex items-center gap-10">
                    @foreach ([
                        ['value' => '4.9', 'label' => 'Rating Pembeli'],
                        ['value' => '1k+', 'label' => 'Pesanan Selesai'],
                        ['value' => '24/7', 'label' => 'Layanan CS'],
                    ] as $stat)
                        <div>
                            <p class="text-2xl font-extrabold tracking-tight text-ink">{{ $stat['value'] }}</p>
                            <p class="mt-1 text-[11px] font-medium uppercase tracking-[0.15em] text-ink/40">{{ $stat['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="reveal relative" style="--reveal-delay: 150ms">
                <div class="absolute -left-8 -top-8 h-64 w-64 rounded-full bg-accent-soft blur-3xl"></div>
                <div class="absolute -bottom-10 -right-6 h-72 w-72 rounded-full bg-ink/5 blur-3xl"></div>
                @if ($featured->first())
                    <div class="group relative z-10 rotate-[-2deg]">
                        <div class="bezel">
                            <div class="bezel-inner">
                                <a href="{{ route('product.show', $featured->first()->slug) }}" wire:navigate class="zoom-media block aspect-[4/5] overflow-hidden">
                                    <img src="{{ $featured->first()->coverImage() }}" alt="{{ $featured->first()->name }}" class="h-full w-full object-cover">
                                </a>
                                <div class="flex items-center justify-between p-6">
                                    <div>
                                        <span class="text-[10px] font-semibold uppercase tracking-[0.2em] text-ink/40">Produk Unggulan</span>
                                        <p class="mt-1 text-lg font-extrabold tracking-tight">{{ $featured->first()->name }}</p>
                                    </div>
                                    <span class="rounded-full bg-ink px-4 py-2 text-sm font-extrabold text-white">{{ rupiah($featured->first()->effectivePrice()) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                @if ($featured->get(1))
                    <div class="group absolute -bottom-12 -right-2 z-20 w-44 rotate-[3deg] sm:w-52">
                        <div class="bezel">
                            <div class="bezel-inner">
                                <a href="{{ route('product.show', $featured->get(1)->slug) }}" wire:navigate class="zoom-media block aspect-square overflow-hidden">
                                    <img src="{{ $featured->get(1)->coverImage() }}" alt="{{ $featured->get(1)->name }}" class="h-full w-full object-cover">
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <!-- ===== MARQUEE STRIP ===== -->
    <section class="overflow-hidden border-y border-ink/5 bg-white py-5">
        <div class="animate-marquee flex w-max items-center gap-10 whitespace-nowrap">
            @foreach (array_merge($m = ['Gratis Ongkir Min. 300rb', 'Garansi 100% Original', 'Pengiriman Hari Ini', 'Pembayaran Aman', 'Retur Mudah 7 Hari'], $m) as $item)
                <span class="flex items-center gap-10 text-sm font-bold uppercase tracking-[0.2em] text-ink/35">
                    {{ $item }} <span class="text-accent">✦</span>
                </span>
            @endforeach
        </div>
    </section>

    <!-- ===== KATEGORI ===== -->
    <section class="px-4 py-24 sm:px-8 sm:py-32">
        <div class="mx-auto max-w-7xl">
            <div class="reveal flex flex-wrap items-end justify-between gap-6">
                <div>
                    <span class="text-[10px] font-semibold uppercase tracking-[0.25em] text-ink/40">Jelajahi</span>
                    <h2 class="mt-3 text-4xl font-extrabold tracking-tight text-ink sm:text-5xl">Pilih kategori favoritmu</h2>
                </div>
                <a href="{{ route('shop') }}" wire:navigate class="btn-pill group flex items-center gap-2 rounded-full border border-ink/15 px-6 py-3 text-sm font-semibold text-ink hover:border-ink/30">
                    Semua Produk
                    <span class="btn-orbit flex h-6 w-6 items-center justify-center rounded-full bg-ink/5 text-[10px]">↗</span>
                </a>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @forelse ($categories as $i => $category)
                    <a href="{{ route('shop', ['category' => $category->slug]) }}" wire:navigate
                       class="group reveal block" style="--reveal-delay: {{ $i * 80 }}ms">
                        <div class="bezel">
                            <div class="bezel-inner flex flex-col gap-4 p-7">
                                <span class="text-3xl">{{ ['✦', '◆', '●', '▲'][$i % 4] }}</span>
                                <h3 class="text-lg font-extrabold tracking-tight">{{ $category->name }}</h3>
                                <p class="text-xs font-medium uppercase tracking-[0.15em] text-ink/40">{{ $category->active_products_count }} produk</p>
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="text-ink/40">Belum ada kategori.</p>
                @endforelse
            </div>
        </div>
    </section>

    <!-- ===== BENTO UNGGULAN ===== -->
    <section class="px-4 pb-24 sm:px-8 sm:pb-32">
        <div class="mx-auto max-w-7xl">
            <div class="reveal">
                <span class="text-[10px] font-semibold uppercase tracking-[0.25em] text-ink/40">Pilihan Kurator</span>
                <h2 class="mt-3 max-w-2xl text-4xl font-extrabold tracking-tight text-ink sm:text-5xl">Produk unggulan minggu ini</h2>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-6">
                @forelse ($featured->take(5) as $i => $product)
                    @php
                        $span = match ($i) { 0 => 'md:col-span-4 md:row-span-2', 1 => 'md:col-span-2', 2 => 'md:col-span-2', default => 'md:col-span-2' };
                    @endphp
                    <div class="group reveal {{ $span }}" style="--reveal-delay: {{ $i * 90 }}ms">
                        <a href="{{ route('product.show', $product->slug) }}" wire:navigate class="block h-full">
                            <div class="bezel h-full">
                                <div class="bezel-inner flex h-full flex-col">
                                    <div class="zoom-media relative overflow-hidden {{ $i === 0 ? 'aspect-[16/10] md:aspect-auto md:flex-1' : 'aspect-[4/3]' }} bg-paper">
                                        <img src="{{ $product->coverImage() }}" alt="{{ $product->name }}" loading="lazy" class="h-full w-full object-cover">
                                        @if ($product->hasDiscount())
                                            <span class="absolute left-4 top-4 rounded-full bg-ink px-3 py-1 text-[10px] font-bold text-white">-{{ $product->discountPercent() }}%</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center justify-between gap-4 p-5">
                                        <div class="min-w-0">
                                            <h3 class="truncate text-[15px] font-bold tracking-tight">{{ $product->name }}</h3>
                                            <p class="mt-0.5 text-xs text-ink/40">{{ $product->category->name }}</p>
                                        </div>
                                        <span class="whitespace-nowrap text-sm font-extrabold">{{ rupiah($product->effectivePrice()) }}</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @empty
                    <p class="text-ink/40">Belum ada produk unggulan.</p>
                @endforelse
            </div>
        </div>
    </section>

    <!-- ===== NEW ARRIVALS ===== -->
    <section class="px-4 pb-24 sm:px-8 sm:pb-32">
        <div class="mx-auto max-w-7xl">
            <div class="reveal flex flex-wrap items-end justify-between gap-6">
                <div>
                    <span class="text-[10px] font-semibold uppercase tracking-[0.25em] text-ink/40">Fresh Drop</span>
                    <h2 class="mt-3 text-4xl font-extrabold tracking-tight sm:text-5xl">Baru datang</h2>
                </div>
                <a href="{{ route('shop') }}" wire:navigate class="btn-pill group flex items-center gap-2 rounded-full bg-ink px-6 py-3 text-sm font-bold text-white">
                    Lihat Semua
                    <span class="btn-orbit flex h-6 w-6 items-center justify-center rounded-full bg-white/10 text-[10px]">↗</span>
                </a>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($newArrivals->take(4) as $i => $product)
                    <div class="group reveal" style="--reveal-delay: {{ $i * 80 }}ms">
                        <x-product-card :product="$product" />
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- ===== CTA ===== -->
    <section class="px-4 pb-10 sm:px-8">
        <div class="reveal mx-auto max-w-7xl overflow-hidden rounded-[2.5rem] bg-ink px-8 py-24 text-center text-white sm:px-16">
            <span class="text-[10px] font-semibold uppercase tracking-[0.25em] text-white/40">Mulai Hari Ini</span>
            <h2 class="mx-auto mt-5 max-w-2xl text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">
                Dapatkan diskon <span class="text-white/40">10%</span> untuk pembelian pertamamu
            </h2>
            <p class="mx-auto mt-5 max-w-md text-white/55">Gunakan kode <span class="rounded-md bg-white/10 px-2 py-1 font-mono font-bold text-white">WELCOME10K</span> saat checkout.</p>
            <a href="{{ route('shop') }}" wire:navigate class="btn-pill group mx-auto mt-10 flex w-max items-center gap-3 rounded-full bg-white px-8 py-4 text-sm font-bold text-ink">
                Klaim Sekarang
                <span class="btn-orbit flex h-8 w-8 items-center justify-center rounded-full bg-ink/5 text-sm">↗</span>
            </a>
        </div>
    </section>

    <!-- toast listener -->
    <div class="pointer-events-none fixed bottom-6 left-1/2 z-50 -translate-x-1/2 px-4">
        <div wire:ignore class="relative">
            <div x-data="{ show: false, message: '', type: 'success' }"
                 x-on:cart-notify.window="message = $event.detail.message; type = $event.detail.type; show = true; setTimeout(() => show = false, 2600)"
                 x-cloak x-show="show"
                 x-transition:enter="transition duration-500 ease-[cubic-bezier(0.32,0.72,0,1)]"
                 x-transition:enter-start="translate-y-4 opacity-0"
                 x-transition:enter-end="translate-y-0 opacity-100"
                 x-transition:leave="transition duration-300"
                 x-transition:leave-end="opacity-0"
                 class="nav-pill pointer-events-none rounded-full px-6 py-3 text-sm font-semibold text-ink">
                <span x-text="message"></span>
            </div>
        </div>
    </div>
</div>

</x-layouts.app>