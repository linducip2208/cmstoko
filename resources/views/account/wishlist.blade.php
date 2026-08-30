<x-layouts.account>
    <x-slot name="title">Wishlist</x-slot>

    <header class="mb-8">
        <h1 class="font-display text-3xl text-ink">Wishlist</h1>
        <p class="mt-1 text-sm text-ink-2">Produk yang kamu simpan untuk nanti.</p>
    </header>

    @if (session('wishlist_removed'))
        <div class="mb-6 rounded-md border border-line bg-surface-2 px-4 py-3 text-sm text-ink-2" role="status">
            {{ session('wishlist_removed') }} dihapus dari wishlist.
        </div>
    @endif

    <div class="grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-3 xl:grid-cols-4">
        @forelse ($wishlists as $wishlist)
            @php $product = $wishlist->product; @endphp
            <div class="space-y-2">
                <x-product-card :product="$product" />
                <form method="POST" action="{{ route('account.wishlist.destroy', $wishlist) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline btn-sm w-full">Hapus</button>
                </form>
            </div>
        @empty
            <div class="col-span-full">
                <x-ui.empty-state title="Wishlist kosong" description="Tandai produk favorit dengan tombol wishlist di halaman produk.">
                    <a href="{{ route('shop') }}" wire:navigate class="btn btn-primary">Jelajahi Katalog</a>
                </x-ui.empty-state>
            </div>
        @endforelse
    </div>
</x-layouts.account>
