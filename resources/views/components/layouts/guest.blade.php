<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Masuk' }} — {{ \App\Support\Settings::get('store.name', config('shop.name')) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-[100dvh] font-sans antialiased">
    <div class="flex min-h-[100dvh]">
        <!-- Left panel — brand -->
        <aside class="relative hidden w-[42%] bg-ink text-paper lg:block">
            <div class="absolute inset-0 opacity-[0.07]" style="background-image: radial-gradient(circle at 20% 30%, #fff 0, transparent 40%), radial-gradient(circle at 80% 70%, #fff 0, transparent 40%);" aria-hidden="true"></div>
            <div class="relative flex h-full flex-col justify-between p-12">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5" wire:navigate>
                    <span class="flex h-10 w-10 items-center justify-center rounded-md bg-paper font-display text-lg text-ink">{{ mb_substr(\App\Support\Settings::get('store.name', 'Toko'), 0, 1) }}</span>
                    <span class="text-lg font-semibold tracking-tight">{{ \App\Support\Settings::get('store.name', 'TokoKita') }}</span>
                </a>
                <div>
                    <p class="font-display text-4xl leading-tight">{{ \App\Support\Settings::get('store.tagline') }}</p>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-paper/60">Masuk untuk melihat riwayat pesanan, menyimpan alamat, dan menyimpan produk favorit.</p>
                </div>
                <p class="text-xs text-paper/40">&copy; {{ date('Y') }} {{ \App\Support\Settings::get('store.name', 'TokoKita') }}</p>
            </div>
        </aside>

        <!-- Right panel — form -->
        <main class="flex flex-1 items-center justify-center px-4 py-12 sm:px-8">
            <div class="w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5" wire:navigate>
                        <span class="flex h-9 w-9 items-center justify-center rounded-md bg-ink font-display text-lg text-paper">{{ mb_substr(\App\Support\Settings::get('store.name', 'Toko'), 0, 1) }}</span>
                        <span class="text-lg font-semibold">{{ \App\Support\Settings::get('store.name', 'TokoKita') }}</span>
                    </a>
                </div>

                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
