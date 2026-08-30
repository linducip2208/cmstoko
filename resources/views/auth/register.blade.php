<x-layouts.guest title="Daftar">
    <h1 class="text-3xl font-semibold tracking-tight text-ink">Buat akun baru</h1>
    <p class="mt-2 text-sm text-ink-2">Simpan alamat, lacak pesanan, dan belanja lebih cepat.</p>

    <form method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="name" class="label">Nama Lengkap</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name" maxlength="120"
                   class="input @error('name') input-error @enderror">
            @error('name')
                <p class="mt-1.5 text-sm text-negative" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" maxlength="150"
                   class="input @error('email') input-error @enderror" placeholder="nama@email.com">
            @error('email')
                <p class="mt-1.5 text-sm text-negative" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="phone" class="label">Nomor Telepon</label>
            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required autocomplete="tel" maxlength="25"
                   class="input @error('phone') input-error @enderror" placeholder="0812xxxxxxxx">
            @error('phone')
                <p class="mt-1.5 text-sm text-negative" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="label">Kata Sandi</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                   class="input @error('password') input-error @enderror">
            @error('password')
                <p class="mt-1.5 text-sm text-negative" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="label">Konfirmasi Kata Sandi</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                   class="input">
        </div>

        <button type="submit" class="btn btn-primary w-full">Daftar</button>
    </form>

    <p class="mt-8 text-center text-sm text-ink-2">
        Sudah punya akun?
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-accent hover:text-accent-ink">Masuk</a>
    </p>
    <p class="mt-3 text-center text-sm">
        <a href="{{ route('home') }}" wire:navigate class="text-ink-3 hover:text-ink">&larr; Kembali ke toko</a>
    </p>
</x-layouts.guest>
