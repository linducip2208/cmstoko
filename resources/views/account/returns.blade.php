<x-layouts.account>
    <x-slot name="title">Pengembalian</x-slot>

    <header class="mb-8">
        <h1 class="font-display text-3xl text-ink">Pengembalian</h1>
        <p class="mt-1 text-sm text-ink-2">Ajukan dan pantau permintaan pengembalian barang.</p>
    </header>

    @if (session('status'))
        <div class="mb-6 rounded-md border border-positive/30 bg-positive-soft px-4 py-3 text-sm text-positive" role="status">{{ session('status') }}</div>
    @endif
    @if (session('return_error'))
        <div class="mb-6 rounded-md border border-negative/30 bg-negative-soft px-4 py-3 text-sm text-negative" role="alert">{{ session('return_error') }}</div>
    @endif

    <div class="space-y-4">
        @forelse ($returns as $return)
            <div class="card p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-mono text-sm font-semibold text-ink">{{ $return->return_number }}</p>
                        <p class="mt-0.5 text-xs text-ink-3">
                            Pesanan <a href="{{ route('account.orders.show', $return->order->order_number) }}" wire:navigate class="text-accent hover:underline">{{ $return->order->order_number }}</a>
                            · {{ $return->created_at->translatedFormat('d M Y') }}
                        </p>
                    </div>
                    <x-ui.badge :tone="match ($return->status) {
                        App\Models\ReturnRequest::STATUS_REFUNDED, App\Models\ReturnRequest::STATUS_RECEIVED => 'new',
                        App\Models\ReturnRequest::STATUS_REJECTED => 'sale',
                        default => 'muted',
                    }">{{ $statuses[$return->status] ?? $return->status }}</x-ui.badge>
                </div>
                <p class="mt-3 text-sm text-ink-2">{{ $return->reason }}</p>
                <ul class="mt-3 space-y-1 text-xs text-ink-3">
                    @foreach ($return->items as $item)
                        <li>{{ $item->quantity }}× {{ $item->orderItem->product_name }}</li>
                    @endforeach
                </ul>
            </div>
        @empty
            <x-ui.empty-state title="Belum ada pengajuan" description="Pengembalian dapat diajukan dari halaman detail pesanan." />
        @endforelse
    </div>

    @if (request()->has('new'))
        <section class="card mt-8 p-6">
            <h2 class="text-lg font-semibold text-ink">Ajukan Pengembalian</h2>
            <form method="POST" action="{{ route('account.returns.store') }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="label" for="order_number">Nomor Pesanan</label>
                    <input id="order_number" name="order_number" class="input @error('order_number') input-error @enderror" value="{{ old('order_number') }}" required>
                    @error('order_number') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="reason">Alasan Pengembalian</label>
                    <textarea id="reason" name="reason" rows="3" class="input @error('reason') input-error @enderror" required>{{ old('reason') }}</textarea>
                    @error('reason') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
                </div>
                <p class="text-xs text-ink-3">Jumlah item yang dikembalikan akan dikonfirmasi oleh tim kami saat peninjauan.</p>
                <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
            </form>
        </section>
    @endif
</x-layouts.account>
