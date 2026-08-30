<x-layouts.account>
    <x-slot name="title">Profil & Keamanan</x-slot>

    <header class="mb-8">
        <h1 class="font-display text-3xl text-ink">Profil & Keamanan</h1>
        <p class="mt-1 text-sm text-ink-2">Perbarui data diri dan kata sandi akun.</p>
    </header>

    @if (session('status'))
        <div class="mb-6 rounded-md border border-positive/30 bg-positive-soft px-4 py-3 text-sm text-positive" role="status">{{ session('status') }}</div>
    @endif

    <section class="card p-6">
        <h2 class="text-lg font-semibold text-ink">Data Diri</h2>
        <form method="POST" action="{{ route('account.profile.update') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
            @csrf
            @method('PUT')
            <div class="sm:col-span-2">
                <label class="label" for="name">Nama Lengkap</label>
                <input id="name" name="name" class="input @error('name') input-error @enderror" value="{{ old('name', $user->name) }}" required maxlength="120">
                @error('name') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="email">Email</label>
                <input id="email" name="email" type="email" class="input @error('email') input-error @enderror" value="{{ old('email', $user->email) }}" required maxlength="150">
                @error('email') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="phone">Telepon</label>
                <input id="phone" name="phone" type="tel" class="input @error('phone') input-error @enderror" value="{{ old('phone', $user->phone) }}" required maxlength="25">
                @error('phone') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </section>

    <section class="card mt-6 p-6">
        <h2 class="text-lg font-semibold text-ink">Ganti Kata Sandi</h2>
        <form method="POST" action="{{ route('account.password.update') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
            @csrf
            @method('PUT')
            <div class="sm:col-span-2">
                <label class="label" for="current_password">Kata Sandi Saat Ini</label>
                <input id="current_password" name="current_password" type="password" class="input @error('current_password') input-error @enderror" required autocomplete="current-password">
                @error('current_password') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="password">Kata Sandi Baru</label>
                <input id="password" name="password" type="password" class="input @error('password') input-error @enderror" required autocomplete="new-password">
                @error('password') <p class="mt-1 text-sm text-negative" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="password_confirmation">Konfirmasi Kata Sandi Baru</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="input" required autocomplete="new-password">
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="btn btn-outline">Ubah Kata Sandi</button>
            </div>
        </form>
    </section>
</x-layouts.account>
