<x-layouts.account>
    <x-slot name="title">Akun Saya</x-slot>

    <header class="mb-8">
        <h1 class="font-display text-3xl text-ink">Halo, {{ $user->name }}</h1>
        <p class="mt-1 text-sm text-ink-2">Ringkasan aktivitas belanja kamu.</p>
    </header>

    @if (session('status'))
        <div class="mb-6 rounded-md border border-positive/30 bg-positive-soft px-4 py-3 text-sm text-positive" role="status">{{ session('status') }}</div>
    @endif

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        @foreach ([
            ['label' => 'Total Pesanan', 'value' => number_format($stats['total_orders'])],
            ['label' => 'Sedang Berjalan', 'value' => number_format($stats['active_orders'])],
            ['label' => 'Wishlist', 'value' => number_format($stats['wishlist'])],
            ['label' => 'Ulasan', 'value' => number_format($stats['reviews'])],
        ] as $stat)
            <div class="card p-5">
                <p class="overline">{{ $stat['label'] }}</p>
                <p class="mt-2 text-2xl font-bold tabular-nums text-ink">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-10 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-ink">Pesanan Terbaru</h2>
        <a href="{{ route('account.orders') }}" wire:navigate class="text-sm font-medium text-accent hover:text-accent-ink">Lihat semua</a>
    </div>

    <div class="mt-4 space-y-3">
        @forelse ($orders as $order)
            <a href="{{ route('account.orders.show', $order->order_number) }}" wire:navigate
               class="card flex flex-col gap-3 p-5 transition-shadow hover:shadow-card sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-mono text-sm font-semibold text-ink">{{ $order->order_number }}</p>
                    <p class="mt-1 text-xs text-ink-3">{{ $order->created_at->translatedFormat('d M Y, H:i') }} · {{ $order->items_count ?? $order->items()->count() }} item</p>
                </div>
                <div class="flex items-center justify-between gap-4 sm:justify-end">
                    <x-ui.badge :tone="match ($order->status) {
                        App\Models\Order::STATUS_COMPLETED, App\Models\Order::STATUS_PAID => 'new',
                        App\Models\Order::STATUS_CANCELLED => 'sale',
                        default => 'muted',
                    }">{{ $order->statusLabel() }}</x-ui.badge>
                    <span class="text-sm font-bold text-ink">{{ rupiah($order->total) }}</span>
                </div>
            </a>
        @empty
            <x-ui.empty-state title="Belum ada pesanan" description="Pesanan pertamamu akan muncul di sini.">
                <a href="{{ route('shop') }}" wire:navigate class="btn btn-primary">Mulai Belanja</a>
            </x-ui.empty-state>
        @endforelse
    </div>
</x-layouts.account>
