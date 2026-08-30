<x-layouts.account>
    <x-slot name="title">Alamat Pengiriman</x-slot>

    <header class="mb-8">
        <h1 class="font-display text-3xl text-ink">Alamat</h1>
        <p class="mt-1 text-sm text-ink-2">Kelola alamat pengiriman untuk checkout yang lebih cepat.</p>
    </header>

    @if (session('status'))
        <div class="mb-6 rounded-md border border-positive/30 bg-positive-soft px-4 py-3 text-sm text-positive" role="status">{{ session('status') }}</div>
    @endif

    <div class="space-y-4">
        @forelse ($addresses as $address)
            <div class="card flex flex-col gap-3 p-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="font-semibold text-ink">{{ $address->label }}</p>
                        @if ($address->is_default)
                            <x-ui.badge tone="new">Utama</x-ui.badge>
                        @endif
                    </div>
                    <address class="mt-1.5 text-sm not-italic leading-relaxed text-ink-2">
                        {{ $address->name }} ({{ $address->phone }})<br>
                        {{ $address->address }}, {{ $address->city_name }}, {{ $address->province_name }} {{ $address->postal_code }}
                    </address>
                </div>
                <div class="flex shrink-0 gap-2">
                    @unless ($address->is_default)
                        <form method="POST" action="{{ route('account.addresses.default', $address) }}">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-sm">Jadikan Utama</button>
                        </form>
                    @endunless
                    <form method="POST" action="{{ route('account.addresses.destroy', $address) }}" x-data
                          x-on:submit.prevent="if (confirm('Hapus alamat ini?')) $root.submit()">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-sm text-negative">Hapus</button>
                    </form>
                </div>
            </div>
        @empty
            <x-ui.empty-state title="Belum ada alamat tersimpan" description="Simpan alamat agar checkout berikutnya lebih cepat." />
        @endforelse
    </div>

    <section class="card mt-8 p-6">
        <h2 class="text-lg font-semibold text-ink">Tambah Alamat</h2>
        <form method="POST" action="{{ route('account.addresses.store') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
            @csrf
            <div>
                <label class="label" for="label">Label</label>
                <input id="label" name="label" class="input @error('label') input-error @enderror" value="{{ old('label', 'Rumah') }}" maxlength="40" required>
                @error('label') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="name">Nama Penerima</label>
                <input id="name" name="name" class="input @error('name') input-error @enderror" value="{{ old('name') }}" required>
                @error('name') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="phone">Telepon</label>
                <input id="phone" name="phone" type="tel" class="input @error('phone') input-error @enderror" value="{{ old('phone') }}" required>
                @error('phone') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="postal_code">Kode Pos</label>
                <input id="postal_code" name="postal_code" class="input @error('postal_code') input-error @enderror" value="{{ old('postal_code') }}" maxlength="10">
                @error('postal_code') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="province_name">Provinsi</label>
                <input id="province_name" name="province_name" class="input @error('province_name') input-error @enderror" value="{{ old('province_name') }}" required>
                <input type="hidden" name="province_id" value="{{ old('province_id', 0) }}">
                @error('province_name') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="city_name">Kota/Kabupaten</label>
                <input id="city_name" name="city_name" class="input @error('city_name') input-error @enderror" value="{{ old('city_name') }}" required>
                <input type="hidden" name="city_id" value="{{ old('city_id', 0) }}">
                @error('city_name') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="label" for="address">Alamat Lengkap</label>
                <textarea id="address" name="address" rows="3" class="input @error('address') input-error @enderror" required>{{ old('address') }}</textarea>
                @error('address') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2.5 text-sm text-ink-2 sm:col-span-2">
                <input type="hidden" name="is_default" value="0">
                <input type="checkbox" name="is_default" value="1" class="h-4 w-4 rounded border-line-strong accent-accent" {{ old('is_default') ? 'checked' : '' }}>
                Jadikan alamat utama
            </label>
            <div class="sm:col-span-2">
                <button type="submit" class="btn btn-primary">Simpan Alamat</button>
            </div>
        </form>
    </section>
</x-layouts.account>
