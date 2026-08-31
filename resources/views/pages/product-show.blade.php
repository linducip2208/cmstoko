<x-layouts.app :title="$seo['title']">
    <x-seo.meta :seo="$seo" />
    @php
        $images = collect($product->images ?? [])->filter()->values();
        $inWishlist = auth()->check() && auth()->user()->wishlistProducts->contains('id', $product->id);
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <x-ui.breadcrumb :items="[($product->category->name ?? 'Katalog') => route('shop', ['category' => $product->category->slug ?? '']), $product->name => null]" />

        <div class="grid gap-10 lg:grid-cols-2 lg:gap-16">
            <!-- ===== Gallery ===== -->
            <div>
                <div class="overflow-hidden rounded-xl border border-line bg-surface-2">
                    <img src="{{ $images->first() ?? $product->coverImage() }}" alt="{{ $product->name }}"
                         class="aspect-[4/5] w-full object-cover lg:aspect-[5/6]"
                         width="900" height="1080" fetchpriority="high">
                </div>
                @if ($images->count() > 1)
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($images as $img)
                            <div class="h-16 w-14 overflow-hidden rounded-md border border-line">
                                <img src="{{ $img }}" alt="Galeri {{ $product->name }} {{ $loop->index + 1 }}" class="h-full w-full object-cover" loading="lazy">
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- ===== Info + Buy box ===== -->
            <div>
                @if ($product->brand)
                    <p class="overline">{{ $product->brand->name }}</p>
                @endif

                <h1 class="mt-2 font-display text-4xl text-ink sm:text-5xl">{{ $product->name }}</h1>

                @if ($ratingTotal > 0)
                    <div class="mt-3">
                        <x-ui.rating :rating="$ratingAverage" :count="$ratingTotal" />
                    </div>
                @endif

                @if ($product->short_description)
                    <p class="mt-4 leading-relaxed text-ink-2">{{ $product->short_description }}</p>
                @endif

                <div class="mt-6">
                    @if ($product->isGrouped())
                        <div class="rounded-md bg-surface-2 px-4 py-3 text-sm text-ink-2">
                            Produk ini adalah grup — pilih salah satu varian produk di bawah untuk dibeli.
                        </div>
                    @else
                        @livewire('add-to-cart', ['product' => $product], key('add-'.$product->id))
                    @endif
                </div>

                @if ($product->isGrouped() && $product->groupedChildren->isNotEmpty())
                    <div class="mt-6 border-t border-line pt-5">
                        <p class="overline">Produk dalam Grup</p>
                        <ul class="mt-3 space-y-3">
                            @foreach ($product->groupedChildren as $child)
                                <li class="flex items-center justify-between gap-3">
                                    <a href="{{ route('product.show', $child->slug) }}" class="text-sm font-medium text-ink hover:text-accent">
                                        {{ $child->name }}
                                    </a>
                                    <span class="text-sm font-bold tabular-nums text-ink">{{ rupiah($child->effectivePrice()) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-6 border-t border-line pt-5">
                    <form method="POST" action="{{ route('account.wishlist.toggle') }}">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <button type="submit" class="btn btn-outline w-full sm:w-auto">
                            {{ $inWishlist ? '★ Tersimpan di Wishlist' : '☆ Simpan ke Wishlist' }}
                        </button>
                    </form>
                </div>

                @if ($product->attribute_values && count($product->attribute_values))
                    <div class="mt-8 border-t border-line pt-6">
                        <h2 class="text-sm font-semibold text-ink">Detail</h2>
                        <dl class="mt-3 space-y-2 text-sm">
                            @foreach ($product->attribute_values as $key => $value)
                                <div class="flex gap-2">
                                    <dt class="w-32 shrink-0 text-ink-3">{{ ucfirst(str_replace(['_', '-'], ' ', (string) $key)) }}</dt>
                                    <dd class="text-ink-2">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif
            </div>
        </div>

        <!-- ===== Description ===== -->
        @if ($product->description)
            <section class="mt-16 border-t border-line pt-10">
                <h2 class="font-display text-2xl text-ink">Deskripsi Produk</h2>
                <div class="prose-content mt-4 max-w-3xl leading-relaxed text-ink-2 [&_a]:text-accent [&_li]:ml-5 [&_ol]:list-decimal [&_strong]:text-ink [&_ul]:list-disc">
                    {!! \App\Models\CmsPage::make(['content' => $product->description])->renderableContent() !!}
                </div>
            </section>
        @endif

        <!-- ===== Reviews ===== -->
        <section class="mt-16 border-t border-line pt-10" id="reviews">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h2 class="font-display text-2xl text-ink">Ulasan Pembeli</h2>
                @if ($ratingTotal > 0)
                    <div class="flex items-center gap-2">
                        <x-ui.rating :rating="$ratingAverage" />
                        <span class="text-sm text-ink-2">{{ number_format($ratingAverage, 1) }} dari {{ number_format($ratingTotal) }} ulasan</span>
                    </div>
                @endif
            </div>

            @if ($reviews->isNotEmpty())
                <ul class="mt-6 grid gap-4 sm:grid-cols-2">
                    @foreach ($reviews as $review)
                        <li class="card p-5">
                            <x-ui.rating :rating="$review->rating" />
                            @if ($review->title)
                                <p class="mt-2 font-semibold text-ink">{{ $review->title }}</p>
                            @endif
                            <p class="mt-1.5 text-sm leading-relaxed text-ink-2">{{ $review->content }}</p>
                            <p class="mt-3 text-xs text-ink-3">
                                {{ $review->user->name ?? 'Pembeli' }} · {{ $review->approved_at?->translatedFormat('d M Y') }}
                                @if ($review->is_verified) · <span class="font-medium text-positive">Pembelian Terverifikasi</span> @endif
                            </p>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-6">{{ $reviews->links() }}</div>
            @else
                <p class="mt-6 text-sm text-ink-3">Belum ada ulasan untuk produk ini. Ulasan hanya ditampilkan setelah dimoderasi toko.</p>
            @endif
        </section>

        <!-- ===== Related ===== -->
        @if ($related->isNotEmpty())
            <section class="mt-16 border-t border-line pt-10">
                <h2 class="font-display text-2xl text-ink">Produk Serupa</h2>
                <div class="mt-6 grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-4">
                    @foreach ($related as $rel)
                        <div class="reveal" style="--reveal-delay: {{ $loop->index * 60 }}ms">
                            <x-product-card :product="$rel" />
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.app>
