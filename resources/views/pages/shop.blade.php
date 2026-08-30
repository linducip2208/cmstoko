<x-layouts.app :title="$seo['title']">
    <x-seo.meta :seo="$seo" />
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14"
         x-data="{ filtersOpen: false }">

        <!-- Header -->
        <header class="mb-10">
            <p class="overline">Katalog</p>
            <h1 class="mt-2 font-display text-4xl text-ink sm:text-5xl">
                {{ $category ? $category->name : 'Semua Produk' }}
            </h1>
            @if ($category?->description)
                <p class="mt-3 max-w-2xl text-ink-2">{{ $category->description }}</p>
            @endif
        </header>

        @if ($category?->activeChildren->isNotEmpty())
            <!-- Subcategories -->
            <div class="mb-8 flex flex-wrap gap-2">
                @foreach ($category->activeChildren as $child)
                    <a href="{{ route('shop', ['category' => $child->slug]) }}" wire:navigate
                       class="badge bg-surface-2 text-ink-2 hover:text-ink">{{ $child->name }}</a>
                @endforeach
            </div>
        @endif

        <div class="grid gap-10 lg:grid-cols-[240px_1fr]">
            <!-- ===== Filters (desktop sidebar / mobile drawer) ===== -->
            <aside class="lg:block" x-show="filtersOpen || window.innerWidth >= 1024" x-cloak
                   x-bind:class="filtersOpen ? 'fixed inset-0 z-50 bg-ink/40 p-4 lg:relative lg:inset-auto lg:bg-transparent lg:p-0' : 'hidden'">
                <div class="h-fit rounded-lg border border-line bg-surface p-5 lg:sticky lg:top-28 lg:border-0 lg:bg-transparent lg:p-0 lg:shadow-none">
                    <div class="mb-4 flex items-center justify-between lg:hidden">
                        <span class="font-semibold text-ink">Filter</span>
                        <button type="button" x-on:click="filtersOpen = false" class="icon-btn" aria-label="Tutup filter">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-5 w-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form method="GET" action="{{ route('shop') }}">
                        @if ($category)
                            <input type="hidden" name="category" value="{{ $category->slug }}">
                        @endif

                        <div class="space-y-6">
                            <div>
                                <p class="overline mb-3">Merek</p>
                                <div class="space-y-2">
                                    @foreach ($brands as $b)
                                        <label class="flex items-center gap-2.5 text-sm text-ink-2">
                                            <input type="checkbox" name="brand" value="{{ $b->slug }}"
                                                   @checked($brand?->slug === $b->slug)
                                                   class="h-4 w-4 rounded border-line-strong accent-accent"
                                                   x-on:change="$root.requestSubmit()">
                                            {{ $b->name }}
                                            <span class="text-xs text-ink-3">({{ $b->products_count }})</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <p class="overline mb-3">Harga</p>
                                <div class="flex items-center gap-2">
                                    <input type="number" name="min" value="{{ request('min') }}" placeholder="Min"
                                           min="0" class="input" aria-label="Harga minimum">
                                    <span class="text-ink-3">—</span>
                                    <input type="number" name="max" value="{{ request('max') }}" placeholder="Maks"
                                           min="0" class="input" aria-label="Harga maksimum">
                                </div>
                            </div>

                            <label class="flex items-center gap-2.5 text-sm text-ink-2">
                                <input type="checkbox" name="stock" value="in"
                                       @checked(request('stock') === 'in')
                                       class="h-4 w-4 rounded border-line-strong accent-accent"
                                       x-on:change="$root.requestSubmit()">
                                Hanya stok tersedia
                            </label>

                            <button type="submit" class="btn btn-primary btn-sm w-full">Terapkan Filter</button>
                            <a href="{{ route('shop') }}" wire:navigate class="btn btn-ghost btn-sm w-full">Reset</a>
                        </div>
                    </form>
                </div>
            </aside>

            <!-- ===== Results ===== -->
            <section>
                <!-- Toolbar -->
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                    <button type="button" x-on:click="filtersOpen = true"
                            class="btn btn-outline btn-sm lg:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-4 w-4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/></svg>
                        Filter
                    </button>

                    <p class="text-sm text-ink-3">{{ number_format($products->total()) }} produk</p>

                    <label class="flex items-center gap-2 text-sm">
                        <span class="text-ink-3">Urutkan</span>
                        <select x-on:change="sortRedirect($event.target.value)" class="input py-2" aria-label="Urutkan produk">
                            @foreach ([
                                'recommended' => 'Rekomendasi',
                                'latest' => 'Terbaru',
                                'best' => 'Terlaris',
                                'price_asc' => 'Harga terendah',
                                'price_desc' => 'Harga tertinggi',
                                'discount' => 'Diskon',
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(request('sort') === $value || ($value === 'recommended' && ! request('sort')))>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <!-- Active filters -->
                @if ($activeFilters->isNotEmpty())
                    <div class="mb-6 flex flex-wrap items-center gap-2">
                        @foreach ($activeFilters as $type => $value)
                            <span class="badge bg-accent-soft text-accent-ink">{{ $type }}: {{ $value }}</span>
                        @endforeach
                        <a href="{{ route('shop') }}" wire:navigate class="text-xs font-medium text-ink-3 hover:text-negative">Hapus semua</a>
                    </div>
                @endif

                <!-- Grid -->
                <div class="grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-3 xl:grid-cols-4">
                    @forelse ($products as $product)
                        <div class="reveal" style="--reveal-delay: {{ $loop->index % 8 * 50 }}ms">
                            <x-product-card :product="$product" />
                        </div>
                    @empty
                        <div class="col-span-full">
                            <x-ui.empty-state title="Tidak ada produk ditemukan" description="Coba ubah filter atau kata kunci pencarian.">
                                <a href="{{ route('shop') }}" wire:navigate class="btn btn-primary">Reset Filter</a>
                            </x-ui.empty-state>
                        </div>
                    @endforelse
                </div>

                <div class="mt-12">{{ $products->links() }}</div>
            </section>
        </div>
    </div>

    <script>
        function sortRedirect(value) {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', value);
            window.location.href = url.toString();
        }
    </script>
</x-layouts.app>
