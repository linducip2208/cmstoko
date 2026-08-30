@php
    $navLinks = [
        ['label' => 'Beranda', 'href' => route('home')],
        ['label' => 'Katalog', 'href' => route('shop')],
        ['label' => 'Lacak Pesanan', 'href' => route('track-order')],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('shop.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
    <script>document.documentElement.classList.add('js');</script>
</head>
<body class="grain min-h-[100dvh] font-sans antialiased" x-data="{ menuOpen: false }" x-bind:class="menuOpen ? 'menu-open overflow-hidden' : ''">

    <!-- Floating glass pill nav -->
    <header class="fixed inset-x-0 top-4 z-50 sm:top-6">
        <div class="mx-auto w-max max-w-[calc(100vw-2rem)]">
            <nav class="nav-pill flex items-center gap-1 rounded-full p-1.5 pl-5 sm:gap-2">
                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2 pr-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-ink text-[13px] font-extrabold text-white">T</span>
                    <span class="hidden text-[15px] font-extrabold tracking-tight sm:block">{{ config('shop.name') }}</span>
                </a>

                <div class="hidden items-center gap-1 md:flex">
                    @foreach ($navLinks as $link)
                        <a href="{{ $link['href'] }}" wire:navigate
                           class="rounded-full px-4 py-2 text-sm font-medium text-ink/60 transition-colors duration-500 ease-[cubic-bezier(0.32,0.72,0,1)] hover:bg-ink/5 hover:text-ink">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>

                @auth
                    <a href="/admin" class="hidden rounded-full px-4 py-2 text-sm font-medium text-ink/60 transition-colors duration-500 ease-[cubic-bezier(0.32,0.72,0,1)] hover:bg-ink/5 hover:text-ink md:block">Admin</a>
                @endauth

                <a href="{{ route('cart') }}" wire:navigate class="btn-pill relative flex items-center gap-2 rounded-full bg-ink px-4 py-2 text-sm font-semibold text-white hover:shadow-[0_12px_30px_-10px_rgba(16,16,20,0.5)]">
                    @livewire('cart-badge')
                </a>

                <button type="button" x-on:click="menuOpen = !menuOpen" class="relative flex h-10 w-10 items-center justify-center rounded-full text-ink md:hidden" aria-label="Menu">
                    <span class="burger-line absolute h-[1.5px] w-5 bg-ink" x-bind:class="menuOpen ? 'translate-y-0 rotate-45' : '-translate-y-[4px]'"></span>
                    <span class="burger-line absolute h-[1.5px] w-5 bg-ink" x-bind:class="menuOpen ? 'translate-y-0 -rotate-45' : 'translate-y-[4px]'"></span>
                </button>
            </nav>
        </div>
    </header>

    <!-- Fullscreen mobile menu -->
    <div x-cloak x-show="menuOpen" x-transition.opacity.duration.500ms
         class="nav-overlay fixed inset-0 z-40 flex min-h-[100dvh] flex-col justify-center px-6 pt-24 md:hidden">
        <div class="space-y-2">
            @foreach ($navLinks as $i => $link)
                <a href="{{ $link['href'] }}" wire:navigate x-on:click="menuOpen = false"
                   class="menu-item block text-4xl font-extrabold tracking-tight text-ink"
                   style="transition-delay: {{ 100 + $i * 70 }}ms">
                    {{ $link['label'] }}
                </a>
            @endforeach
            @auth
                <a href="/admin" x-on:click="menuOpen = false"
                   class="menu-item block text-4xl font-extrabold tracking-tight text-ink/50" style="transition-delay: 310ms">Admin</a>
            @endauth
        </div>
    </div>

    <main class="pt-28 sm:pt-32">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="mt-32 px-4 pb-10 sm:px-8">
        <div class="mx-auto max-w-7xl rounded-[2rem] bg-ink px-8 py-14 text-white sm:px-14">
            <div class="flex flex-col justify-between gap-12 md:flex-row md:items-end">
                <div class="max-w-md">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.25em] text-white/40">{{ config('shop.name') }}</p>
                    <h3 class="mt-4 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">{{ config('shop.tagline') }}</h3>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('shop') }}" wire:navigate class="btn-pill group flex items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-bold text-ink">
                        Mulai Belanja
                        <span class="btn-orbit flex h-7 w-7 items-center justify-center rounded-full bg-ink/5 text-xs">↗</span>
                    </a>
                    <a href="{{ route('track-order') }}" wire:navigate class="btn-pill rounded-full border border-white/15 px-6 py-3 text-sm font-semibold text-white/80 hover:bg-white/5">
                        Lacak Pesanan
                    </a>
                </div>
            </div>
            <div class="mt-14 flex flex-col gap-2 border-t border-white/10 pt-6 text-xs text-white/40 sm:flex-row sm:items-center sm:justify-between">
                <span>© {{ date('Y') }} {{ config('shop.name') }}. Semua hak dilindungi.</span>
                <span>Blade · Livewire · Filament · Dibangun dengan teliti</span>
            </div>
        </div>
    </footer>

    @livewireScripts
    @stack('scripts')
</body>
</html>
