<x-filament-panels::page>
    <div x-data class="space-y-6">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Periode:</span>
            @foreach ([7 => '7 Hari', 30 => '30 Hari', 90 => '90 Hari'] as $days => $label)
                <button
                    wire:click="$set('range', '{{ $days }}')"
                    class="rounded-full px-4 py-2 text-sm font-medium transition-all duration-300 ease-[cubic-bezier(0.32,0.72,0,1)] active:scale-[0.98] {{
                        $range == $days
                            ? 'bg-primary-500 text-white shadow-lg shadow-primary-500/25'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                    }}"
                >{{ $label }}</button>
            @endforeach
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs uppercase tracking-[0.2em] text-gray-400">Pelanggan Terdaftar</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->totalCustomers(), 0, ',', '.') }}</p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs uppercase tracking-[0.2em] text-gray-400">Baru (periode)</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->newCustomers(), 0, ',', '.') }}</p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs uppercase tracking-[0.2em] text-gray-400">Pembeli Berulang</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->returningCustomers(), 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="mb-4 font-semibold text-gray-900 dark:text-white">Top 10 Pembelanja</h3>
                @forelse ($this->topSpenders() as $user)
                    <div class="flex items-center justify-between border-b border-gray-100 py-3 last:border-0 dark:border-gray-800">
                        <div class="min-w-0 pr-4">
                            <span class="block truncate text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</span>
                            <span class="text-xs text-gray-400">{{ $user->email }}</span>
                        </div>
                        <span class="whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                            Rp {{ number_format((int) $user->orders_sum_total, 0, ',', '.') }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Belum ada pembelian pada periode ini.</p>
                @endforelse
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="mb-4 font-semibold text-gray-900 dark:text-white">Sebaran Grup Pelanggan</h3>
                @forelse ($this->groupBreakdown() as $group)
                    <div class="flex items-center justify-between border-b border-gray-100 py-3 last:border-0 dark:border-gray-800">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $group->name }}</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $group->users_count }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Belum ada grup.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>
