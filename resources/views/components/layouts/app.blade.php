<?php

use App\Support\Settings;

$storeName = Settings::get('store.name', config('shop.name', 'TokoKita'));
$announcement = Settings::get('header.announcement_enabled', true) ? Settings::get('header.announcement') : null;

// Cached for 5 minutes; invalidated by the Category observer.
$rootCategories = Illuminate\Support\Facades\Cache::remember('nav.root_categories', now()->addMinutes(5), function () {
    return App\Models\Category::active()->root()->with('activeChildren')->orderBy('sort_order')->orderBy('name')->get();
});

$whatsapp = Settings::get('store.whatsapp');
?>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? $storeName }}</title>
    @if (! empty($metaDescription))
        <meta name="description" content="{{ $metaDescription }}">
    @endif
    @if ($canonical ?? null)
        <link rel="canonical" href="{{ $canonical }}">
    @endif
    @stack('meta')
    <link rel="icon" href="{{ Settings::get('store.favicon') ?? asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
    <script>document.documentElement.classList.add('js');</script>
</head>
<body class="min-h-[100dvh] font-sans antialiased" x-data="{ mobileNav: false }" x-bind:class="mobileNav ? 'overflow-hidden' : ''">

    @if ($announcement)
        <div class="bg-ink text-paper">
            <p class="mx-auto max-w-7xl px-4 py-2.5 text-center text-xs font-medium tracking-wide sm:text-sm">
                {{ $announcement }}
            </p>
        </div>
    @endif

    <!-- ===== HEADER ===== -->
    <header class="sticky top-0 z-40 border-b border-line bg-paper/90 backdrop-blur-md" x-data="{ searchOpen: false }">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between gap-4 lg:h-20">
                <!-- Left: brand + desktop nav -->
                <div class="flex items-center gap-8">
                    <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2.5" aria-label="{{ $storeName }} — Beranda">
                        @if ($logo = Settings::get('store.logo'))
                            <img src="{{ $logo }}" alt="{{ $storeName }}" class="h-8 w-auto">
                        @else
                            <span class="flex h-9 w-9 items-center justify-center rounded-md bg-ink font-display text-lg text-paper">{{ mb_substr($storeName, 0, 1) }}</span>
                            <span class="hidden text-lg font-semibold tracking-tight sm:block">{{ $storeName }}</span>
                        @endif
                    </a>

                    <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigasi utama">
                        <a href="{{ route('home') }}" wire:navigate
                           class="rounded-md px-3 py-2 text-sm font-medium text-ink-2 transition-colors hover:bg-surface-2 hover:text-ink">
                            Beranda
                        </a>

                        @forelse ($rootCategories->take(4) as $category)
                            @if ($category->activeChildren->isNotEmpty())
                                <div class="group relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                    <button type="button" class="flex items-center gap-1 rounded-md px-3 py-2 text-sm font-medium text-ink-2 transition-colors hover:bg-surface-2 hover:text-ink"
                                        x-bind:aria-expanded="open.toString()">
                                        {{ $category->name }}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5 opacity-60" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" /></svg>
                                    </button>

                                    <div x-cloak x-show="open" x-transition.opacity.duration.150ms
                                         class="absolute left-0 top-full w-56 pt-2">
                                        <div class="card overflow-hidden p-2 shadow-card">
                                            <a href="{{ route('shop', ['category' => $category->slug]) }}" wire:navigate
                                               class="block rounded-md px-3 py-2 text-sm font-semibold text-ink hover:bg-surface-2">
                                                Semua {{ $category->name }}
                                            </a>
                                            @foreach ($category->activeChildren as $child)
                                                <a href="{{ route('shop', ['category' => $child->slug]) }}" wire:navigate
                                                   class="block rounded-md px-3 py-2 text-sm text-ink-2 hover:bg-surface-2 hover:text-ink">
                                                    {{ $child->name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @else
                                <a href="{{ route('shop', ['category' => $category->slug]) }}" wire:navigate
                                   class="rounded-md px-3 py-2 text-sm font-medium text-ink-2 transition-colors hover:bg-surface-2 hover:text-ink">
                                    {{ $category->name }}
                                </a>
                            @endif
                        @empty
                            <a href="{{ route('shop') }}" wire:navigate
                               class="rounded-md px-3 py-2 text-sm font-medium text-ink-2 transition-colors hover:bg-surface-2 hover:text-ink">
                                Katalog
                            </a>
                        @endforelse

                        <a href="{{ route('track-order') }}" wire:navigate
                           class="rounded-md px-3 py-2 text-sm font-medium text-ink-2 transition-colors hover:bg-surface-2 hover:text-ink">
                            Lacak Pesanan
                        </a>
                    </nav>
                </div>

                <!-- Right: actions -->
                <div class="flex items-center gap-1">
                    <button type="button" class="icon-btn" x-on:click="searchOpen = !searchOpen" aria-label="Cari produk" x-bind:aria-expanded="searchOpen.toString()">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-5 w-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    </button>

                    @auth
                        @if (auth()->user()->isStaff())
                            <a href="/admin" class="icon-btn hidden sm:inline-flex" aria-label="Panel Admin" title="Panel Admin">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-5 w-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/></svg>
                            </a>
                        @endif
                        <a href="{{ route('account.wishlist') }}" wire:navigate class="icon-btn" aria-label="Wishlist">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-5 w-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                        </a>
                        <a href="{{ route('account') }}" wire:navigate class="icon-btn" aria-label="Akun saya">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-5 w-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                        </a>
                    @else
                        <a href="{{ route('login') }}" wire:navigate class="hidden rounded-md px-3 py-2 text-sm font-medium text-ink-2 transition-colors hover:text-ink sm:block">Masuk</a>
                    @endauth

                    <button type="button" class="icon-btn" x-on:click="$dispatch('open-cart-drawer')" aria-label="Buka keranjang belanja">
                        @livewire('cart-badge')
                    </button>

                    <button type="button" class="icon-btn lg:hidden" x-on:click="mobileNav = true" aria-label="Buka menu">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-5 w-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Search bar (expandable) -->
        <div x-cloak x-show="searchOpen" x-transition.ease-out.duration.200ms>
            <div class="mx-auto max-w-7xl px-4 pb-4 sm:px-6 lg:px-8">
                <form action="{{ route('shop') }}" method="GET" role="search" class="flex gap-2">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari produk, merek, atau kategori…"
                           class="input" aria-label="Kata kunci pencarian" autofocus>
                    <button type="submit" class="btn btn-primary">Cari</button>
                </form>
            </div>
        </div>
    </header>

    <!-- ===== MOBILE DRAWER ===== -->
    <div x-cloak x-show="mobileNav" class="fixed inset-0 z-50 lg:hidden" aria-modal="true" role="dialog">
        <div class="absolute inset-0 bg-ink/40" x-on:click="mobileNav = false" x-transition.opacity.duration.200ms aria-hidden="true"></div>
        <div class="absolute inset-y-0 right-0 flex w-full max-w-sm flex-col bg-paper shadow-float"
             x-show="mobileNav" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <span class="text-lg font-semibold">{{ $storeName }}</span>
                <button type="button" class="icon-btn" x-on:click="mobileNav = false" aria-label="Tutup menu">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-5 w-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-5 py-4" aria-label="Menu mobile">
                <div class="space-y-1">
                    <a href="{{ route('home') }}" wire:navigate x-on:click="mobileNav = false" class="block rounded-md px-3 py-2.5 text-base font-medium text-ink hover:bg-surface-2">Beranda</a>
                    @foreach ($rootCategories as $category)
                        <a href="{{ route('shop', ['category' => $category->slug]) }}" wire:navigate x-on:click="mobileNav = false" class="block rounded-md px-3 py-2.5 text-base font-medium text-ink hover:bg-surface-2">{{ $category->name }}</a>
                        @foreach ($category->activeChildren as $child)
                            <a href="{{ route('shop', ['category' => $child->slug]) }}" wire:navigate x-on:click="mobileNav = false" class="block rounded-md pl-6 pr-3 py-2 text-sm text-ink-2 hover:bg-surface-2">{{ $child->name }}</a>
                        @endforeach
                    @endforeach
                    <a href="{{ route('track-order') }}" wire:navigate x-on:click="mobileNav = false" class="block rounded-md px-3 py-2.5 text-base font-medium text-ink hover:bg-surface-2">Lacak Pesanan</a>
                </div>
            </nav>

            <div class="border-t border-line px-5 py-4">
                @auth
                    <a href="{{ route('account') }}" wire:navigate x-on:click="mobileNav = false" class="btn btn-outline w-full">Akun Saya</a>
                @else
                    <a href="{{ route('login') }}" wire:navigate x-on:click="mobileNav = false" class="btn btn-primary w-full">Masuk / Daftar</a>
                @endauth
                @if ($whatsapp)
                    <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="mt-2 btn btn-outline w-full">Hubungi Kami via WhatsApp</a>
                @endif
            </div>
        </div>
    </div>

    <!-- ===== MAIN ===== -->
    <main id="content">
        {{ $slot }}
    </main>

    <livewire:cart-drawer />

    <!-- ===== FOOTER ===== -->
    <footer class="mt-24 border-t border-line bg-surface">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-4">
                <div class="max-w-xs">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-md bg-ink font-display text-lg text-paper">{{ mb_substr($storeName, 0, 1) }}</span>
                        <span class="text-lg font-semibold tracking-tight">{{ $storeName }}</span>
                    </a>
                    <p class="mt-4 text-sm leading-relaxed text-ink-2">{{ Settings::get('footer.about') }}</p>
                    <div class="mt-5 flex gap-2">
                        @foreach (['instagram' => 'Instagram', 'tiktok' => 'TikTok', 'facebook' => 'Facebook', 'youtube' => 'YouTube'] as $key => $label)
                            @if ($url = Settings::get('store.social.'.$key))
                                <a href="{{ $url }}" target="_blank" rel="noopener" class="icon-btn border border-line" aria-label="{{ $label }}">
                                    <span class="text-xs font-semibold">{{ mb_substr($label, 0, 2) }}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div>
                    <p class="overline">Jelajahi</p>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li><a href="{{ route('shop') }}" wire:navigate class="text-ink-2 hover:text-ink">Semua Produk</a></li>
                        @foreach ($rootCategories->take(3) as $category)
                            <li><a href="{{ route('shop', ['category' => $category->slug]) }}" wire:navigate class="text-ink-2 hover:text-ink">{{ $category->name }}</a></li>
                        @endforeach
                        <li><a href="{{ route('track-order') }}" wire:navigate class="text-ink-2 hover:text-ink">Lacak Pesanan</a></li>
                    </ul>
                </div>

                <div>
                    <p class="overline">Bantuan</p>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li><a href="{{ route('pages.show', 'tentang-kami') }}" wire:navigate class="text-ink-2 hover:text-ink">Tentang Kami</a></li>
                        <li><a href="{{ route('pages.show', 'syarat-ketentuan') }}" wire:navigate class="text-ink-2 hover:text-ink">Syarat & Ketentuan</a></li>
                        <li><a href="{{ route('pages.show', 'kebijakan-privasi') }}" wire:navigate class="text-ink-2 hover:text-ink">Kebijakan Privasi</a></li>
                        @if ($whatsapp)
                            <li><a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="text-ink-2 hover:text-ink">WhatsApp: {{ Settings::get('store.phone') }}</a></li>
                        @endif
                    </ul>
                </div>

                <div>
                    <p class="overline">Kontak</p>
                    <ul class="mt-4 space-y-2.5 text-sm text-ink-2">
                        <li>{{ Settings::get('store.email') }}</li>
                        <li>{{ Settings::get('store.phone') }}</li>
                        <li class="leading-relaxed">{{ Settings::get('store.address') }}</li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 flex flex-col gap-2 border-t border-line pt-6 text-xs text-ink-3 sm:flex-row sm:items-center sm:justify-between">
                <span>&copy; {{ date('Y') }} {{ $storeName }}. {{ Settings::get('footer.copyright') }}</span>
                <span>{{ $storeName }} — {{ Settings::get('store.tagline') }}</span>
            </div>
        </div>
    </footer>

    @livewireScripts
    @stack('scripts')
</body>
</html>
