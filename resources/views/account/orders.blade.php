<x-layouts.account>
    <x-slot name="title">Pesanan Saya</x-slot>

    <header class="mb-8">
        <h1 class="font-display text-3xl text-ink">Pesanan Saya</h1>
        <p class="mt-1 text-sm text-ink-2">Riwayat dan status seluruh pesanan kamu.</p>
    </header>

    <div class="mb-6 flex flex-wrap gap-2">
        <a href="{{ route('account.orders') }}" wire:navigate
           class="badge {{ ! request('status') ? 'bg-ink text-paper' : 'bg-surface-2 text-ink-2 hover:text-ink' }}">Semua</a>
        @foreach ($statuses as $key => $label)
            <a href="{{ route('account.orders', ['status' => $key]) }}" wire:navigate
               class="badge {{ request('status') === $key ? 'bg-ink text-paper' : 'bg-surface-2 text-ink-2 hover:text-ink' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse ($orders as $order)
            <a href="{{ route('account.orders.show', $order->order_number) }}" wire:navigate
               class="card flex flex-col gap-3 p-5 transition-shadow hover:shadow-card sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-mono text-sm font-semibold text-ink">{{ $order->order_number }}</p>
                    <p class="mt-1 text-xs text-ink-3">{{ $order->created_at->translatedFormat('d M Y, H:i') }} · {{ $order->items_count }} item</p>
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
            <x-ui.empty-state title="Tidak ada pesanan" description="Coba pilih filter status lain atau mulai belanja.">
                <a href="{{ route('shop') }}" wire:navigate class="btn btn-primary">Jelajahi Katalog</a>
            </x-ui.empty-state>
        @endforelse
    </div>

    <div class="mt-8">{{ $orders->links() }}</div>
</x-layouts.account>
