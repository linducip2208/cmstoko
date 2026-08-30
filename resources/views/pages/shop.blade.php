<x-layouts.app>
<div class="px-4 pb-24 sm:px-8">
    <div class="mx-auto max-w-7xl">
        <!-- header -->
        <div class="reveal py-10">
            <span class="text-[10px] font-semibold uppercase tracking-[0.25em] text-ink/40">Katalog</span>
            <h1 class="mt-3 text-4xl font-extrabold tracking-tight sm:text-5xl">Semua produk</h1>
        </div>

        <!-- filter bar -->
        <div class="reveal sticky top-24 z-30 -mx-4 mb-12 px-4 sm:mx-0 sm:px-0">
            <div class="nav-pill flex flex-col gap-3 rounded-[1.75rem] p-3 sm:flex-row sm:items-center">
                <form action="{{ route('shop') }}" method="GET" class="flex flex-1 items-center gap-2">
                    @if ($activeCategory)
                        <input type="hidden" name="category" value="{{ $activeCategory }}">
                    @endif
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari produk…"
                           class="w-full rounded-full bg-transparent px-4 py-2.5 text-sm font-medium outline-none placeholder:text-ink/35">
                    <button type="submit" class="btn-pill flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-ink text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.4" stroke="currentColor" class="h-4.5 w-4.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    </button>
                </form>
                <div class="flex gap-1.5 overflow-x-auto pb-1 sm:pb-0">
                    <a href="{{ route('shop') }}" wire:navigate
                       class="whitespace-nowrap rounded-full px-4 py-2 text-xs font-bold transition-colors duration-500 ease-[cubic-bezier(0.32,0.72,0,1)] {{ ! $activeCategory ? 'bg-ink text-white' : 'bg-ink/5 text-ink/60 hover:bg-ink/10' }}">
                        Semua
                    </a>
                    @foreach ($categories as $category)
                        <a href="{{ route('shop', ['category' => $category->slug]) }}" wire:navigate
                           class="whitespace-nowrap rounded-full px-4 py-2 text-xs font-bold transition-colors duration-500 ease-[cubic-bezier(0.32,0.72,0,1)] {{ $activeCategory === $category->slug ? 'bg-ink text-white' : 'bg-ink/5 text-ink/60 hover:bg-ink/10' }}">
                            {{ $category->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- sort -->
        <div class="mb-8 flex items-center gap-2 text-sm text-ink/50">
            <span class="font-medium">Urutkan:</span>
            <a href="{{ route('shop', array_merge(request()->query(), ['sort' => null])) }}" wire:navigate class="rounded-full px-3 py-1.5 text-xs font-bold {{ ! request('sort') ? 'bg-ink/10 text-ink' : 'hover:bg-ink/5' }}">Terbaru</a>
            <a href="{{ route('shop', array_merge(request()->query(), ['sort' => 'price_asc'])) }}" wire:navigate class="rounded-full px-3 py-1.5 text-xs font-bold {{ request('sort') === 'price_asc' ? 'bg-ink/10 text-ink' : 'hover:bg-ink/5' }}">Harga ↑</a>
            <a href="{{ route('shop', array_merge(request()->query(), ['sort' => 'price_desc'])) }}" wire:navigate class="rounded-full px-3 py-1.5 text-xs font-bold {{ request('sort') === 'price_desc' ? 'bg-ink/10 text-ink' : 'hover:bg-ink/5' }}">Harga ↓</a>
        </div>

        <!-- grid -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($products as $product)
                <div class="group reveal">
                    <x-product-card :product="$product" />
                </div>
            @empty
                <div class="col-span-full py-20 text-center">
                    <p class="text-2xl font-extrabold tracking-tight text-ink/70">Tidak ada produk ditemukan</p>
                    <p class="mt-2 text-sm text-ink/40">Coba kata kunci atau kategori lain.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-14">
            {{ $products->links('pagination::tailwind') }}
        </div>
    </div>
</div>

</x-layouts.app>