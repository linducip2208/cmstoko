<x-layouts.app>
<div class="px-4 pb-24 sm:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="reveal pt-6">
            <a href="{{ route('shop') }}" wire:navigate class="text-sm font-semibold text-ink/40 transition-colors duration-500 hover:text-ink">← Kembali ke katalog</a>
        </div>

        <div class="mt-8 grid gap-14 lg:grid-cols-2">
            <!-- gallery -->
            <div class="reveal">
                <div class="bezel" x-data="{ active: 0 }">
                    <div class="bezel-inner">
                        <div class="aspect-[4/5] overflow-hidden bg-paper">
                            @if ($product->images)
                                <div class="relative h-full w-full">
                                    @foreach ($product->images as $i => $image)
                                        <img src="{{ $image }}" alt="{{ $product->name }} {{ $i + 1 }}" x-show="active === {{ $i }}" x-cloak
                                             class="absolute inset-0 h-full w-full object-cover transition-opacity duration-700 ease-[cubic-bezier(0.32,0.72,0,1)]"
                                             x-bind:class="active === {{ $i }} ? 'opacity-100' : 'opacity-0'">
                                    @endforeach
                                </div>
                            @else
                                <img src="{{ $product->coverImage() }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                            @endif
                        </div>
                        @if ($product->images && count($product->images) > 1)
                            <div class="flex gap-2 p-4">
                                @foreach ($product->images as $i => $image)
                                    <button type="button" x-on:click="active = {{ $i }}"
                                            class="h-16 w-16 overflow-hidden rounded-2xl ring-1 transition-all duration-500 ease-[cubic-bezier(0.32,0.72,0,1)]"
                                            x-bind:class="active === {{ $i }} ? 'ring-ink ring-offset-2' : 'ring-ink/10 hover:ring-ink/40'">
                                        <img src="{{ $image }}" alt="" class="h-full w-full object-cover">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- info -->
            <div class="reveal flex flex-col" style="--reveal-delay: 120ms">
                <div class="flex items-center gap-3">
                    <a href="{{ route('shop', ['category' => $product->category->slug]) }}" wire:navigate
                       class="rounded-full bg-accent-soft px-4 py-1.5 text-[11px] font-bold uppercase tracking-[0.15em] text-accent">
                        {{ $product->category->name }}
                    </a>
                    @if ($product->stock > 0)
                        <span class="text-[11px] font-semibold uppercase tracking-[0.15em] text-emerald-600">Stok tersedia</span>
                    @else
                        <span class="text-[11px] font-semibold uppercase tracking-[0.15em] text-red-500">Stok habis</span>
                    @endif
                </div>

                <h1 class="mt-5 text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">{{ $product->name }}</h1>

                <div class="mt-6 flex items-baseline gap-4">
                    <span class="text-3xl font-extrabold tracking-tight">{{ rupiah($product->effectivePrice()) }}</span>
                    @if ($product->hasDiscount())
                        <span class="text-lg font-medium text-ink/30 line-through">{{ rupiah($product->price) }}</span>
                        <span class="rounded-full bg-ink px-3 py-1 text-xs font-bold text-white">Hemat {{ $product->discountPercent() }}%</span>
                    @endif
                </div>

                <div class="mt-10">
                    @if ($product->stock > 0)
                        @livewire('add-to-cart', ['productId' => $product->id, 'stock' => $product->stock], key('atc-'.$product->id))
                    @else
                        <span class="inline-block rounded-full bg-ink/5 px-6 py-3 text-sm font-semibold text-ink/40">Produk tidak tersedia</span>
                    @endif
                </div>

                @if ($product->description)
                    <div class="bezel mt-10">
                        <div class="bezel-inner p-7">
                            <span class="text-[10px] font-semibold uppercase tracking-[0.25em] text-ink/40">Deskripsi</span>
                            <div class="prose-sm mt-4 text-[15px] leading-relaxed text-ink/70 [&_p]:my-2">{!! $product->description !!}</div>
                        </div>
                    </div>
                @endif

                <div class="mt-8 grid grid-cols-3 gap-3 text-center">
                    @foreach ([
                        ['icon' => '🚚', 'title' => 'Kirim Cepat', 'sub' => 'Hari yang sama'],
                        ['icon' => '🛡️', 'title' => 'Garansi', 'sub' => '100% original'],
                        ['icon' => '↩️', 'title' => 'Retur Mudah', 'sub' => '7 hari'],
                    ] as $perk)
                        <div class="rounded-3xl bg-white p-4 shadow-press ring-1 ring-ink/5">
                            <span class="text-xl">{{ $perk['icon'] }}</span>
                            <p class="mt-1 text-xs font-bold">{{ $perk['title'] }}</p>
                            <p class="text-[10px] text-ink/40">{{ $perk['sub'] }}</p>
                        </div>
                    @endforeach
                </div>

                <p class="mt-6 text-xs text-ink/40">SKU: {{ $product->sku ?? '-' }} · Berat {{ number_format($product->weight, 0, ',', '.') }} gram</p>
            </div>
        </div>

        @if ($related->isNotEmpty())
            <div class="mt-32">
                <div class="reveal">
                    <span class="text-[10px] font-semibold uppercase tracking-[0.25em] text-ink/40">Kamu mungkin suka</span>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">Produk sejenis</h2>
                </div>
                <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($related as $i => $relatedProduct)
                        <div class="group reveal" style="--reveal-delay: {{ $i * 80 }}ms">
                            <x-product-card :product="$relatedProduct" />
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

</x-layouts.app>