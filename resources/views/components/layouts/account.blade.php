<x-layouts.app>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-[240px_1fr]">
            <!-- Side nav -->
            <aside>
                <div class="card p-2">
                    <nav class="space-y-1" aria-label="Menu akun">
                        {{ $nav ?? '' }}
                        <a href="{{ route('account') }}" wire:navigate
                           class="block rounded-md px-4 py-2.5 text-sm font-medium {{ request()->routeIs('account') && ! request()->routeIs('account.orders') ? 'bg-ink text-paper' : 'text-ink-2 hover:bg-surface-2 hover:text-ink' }}">
                            Ringkasan
                        </a>
                        <a href="{{ route('account.orders') }}" wire:navigate
                           class="block rounded-md px-4 py-2.5 text-sm font-medium {{ request()->routeIs('account.orders') ? 'bg-ink text-paper' : 'text-ink-2 hover:bg-surface-2 hover:text-ink' }}">
                            Pesanan Saya
                        </a>
                        <a href="{{ route('account.addresses') }}" wire:navigate
                           class="block rounded-md px-4 py-2.5 text-sm font-medium {{ request()->routeIs('account.addresses') ? 'bg-ink text-paper' : 'text-ink-2 hover:bg-surface-2 hover:text-ink' }}">
                            Alamat
                        </a>
                        <a href="{{ route('account.wishlist') }}" wire:navigate
                           class="block rounded-md px-4 py-2.5 text-sm font-medium {{ request()->routeIs('account.wishlist') ? 'bg-ink text-paper' : 'text-ink-2 hover:bg-surface-2 hover:text-ink' }}">
                            Wishlist
                        </a>
                        <a href="{{ route('account.returns') }}" wire:navigate
                           class="block rounded-md px-4 py-2.5 text-sm font-medium {{ request()->routeIs('account.returns') ? 'bg-ink text-paper' : 'text-ink-2 hover:bg-surface-2 hover:text-ink' }}">
                            Pengembalian
                        </a>
                        <a href="{{ route('account.profile') }}" wire:navigate
                           class="block rounded-md px-4 py-2.5 text-sm font-medium {{ request()->routeIs('account.profile') ? 'bg-ink text-paper' : 'text-ink-2 hover:bg-surface-2 hover:text-ink' }}">
                            Profil & Keamanan
                        </a>
                    </nav>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-4 px-4">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-ink-3 hover:text-negative">Keluar dari akun</button>
                </form>
            </aside>

            <!-- Content -->
            <section class="min-w-0">
                {{ $slot }}
            </section>
        </div>
    </div>
</x-layouts.app>
