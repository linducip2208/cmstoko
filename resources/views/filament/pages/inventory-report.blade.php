<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs uppercase tracking-[0.2em] text-gray-400">SKU Aktif</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->totalSkus(), 0, ',', '.') }}</p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs uppercase tracking-[0.2em] text-gray-400">Stok Habis</p>
                <p class="mt-2 text-2xl font-bold {{ $this->outOfStockCount() > 0 ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                    {{ number_format($this->outOfStockCount(), 0, ',', '.') }}
                </p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs uppercase tracking-[0.2em] text-gray-400">Nilai Stok</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($this->stockValue(), 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="mb-4 font-semibold text-gray-900 dark:text-white">Stok Menipis / Habis ({{ $this->lowStock()->count() }})</h3>
            @forelse ($this->lowStock() as $product)
                <div class="flex items-center justify-between border-b border-gray-100 py-3 last:border-0 dark:border-gray-800">
                    <div class="min-w-0 pr-4">
                        <span class="block truncate text-sm font-medium text-gray-900 dark:text-white">{{ $product->name }}</span>
                        <span class="text-xs text-gray-400">{{ $product->sku }}</span>
                    </div>
                    <span class="whitespace-nowrap text-sm font-bold {{ $product->stock === 0 ? 'text-red-600' : ($product->stock <= 2 ? 'text-amber-600' : 'text-gray-900 dark:text-white') }}">
                        {{ $product->stock }} unit
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-400">Semua stok aman di atas ambang.</p>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
