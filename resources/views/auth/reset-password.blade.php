<x-layouts.guest title="Atur Ulang Kata Sandi">
    <h1 class="text-3xl font-semibold tracking-tight text-ink">Atur ulang kata sandi</h1>
    <p class="mt-2 text-sm text-ink-2">Buat kata sandi baru untuk akun kamu.</p>

    <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email" maxlength="150"
                   class="input @error('email') input-error @enderror">
            @error('email')
                <p class="mt-1.5 text-sm text-negative" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="label">Kata Sandi Baru</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                   class="input @error('password') input-error @enderror">
            @error('password')
                <p class="mt-1.5 text-sm text-negative" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="label">Konfirmasi Kata Sandi Baru</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                   class="input">
        </div>

        <button type="submit" class="btn btn-primary w-full">Simpan Kata Sandi</button>
    </form>

    <p class="mt-8 text-center text-sm">
        <a href="{{ route('login') }}" wire:navigate class="text-ink-3 hover:text-ink">&larr; Kembali ke halaman masuk</a>
    </p>
</x-layouts.guest>
