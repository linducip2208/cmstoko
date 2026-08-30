<x-layouts.guest title="Masuk">
    <h1 class="text-3xl font-semibold tracking-tight text-ink">Selamat datang kembali</h1>
    <p class="mt-2 text-sm text-ink-2">Masuk untuk melanjutkan belanja.</p>

    @if (session('status'))
        <div class="mt-6 rounded-md border border-positive/30 bg-positive-soft px-4 py-3 text-sm text-positive" role="status">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="email" class="label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                   class="input @error('email') input-error @enderror" placeholder="nama@email.com">
            @error('email')
                <p class="mt-1.5 text-sm text-negative" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="label">Kata Sandi</label>
                <a href="{{ route('password.request') }}" wire:navigate class="text-sm font-medium text-accent hover:text-accent-ink">Lupa kata sandi?</a>
            </div>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="input @error('password') input-error @enderror">
            @error('password')
                <p class="mt-1.5 text-sm text-negative" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2.5 text-sm text-ink-2">
            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-line-strong text-accent accent-accent">
            Ingat saya
        </label>

        <button type="submit" class="btn btn-primary w-full">Masuk</button>
    </form>

    <p class="mt-8 text-center text-sm text-ink-2">
        Belum punya akun?
        <a href="{{ route('register') }}" wire:navigate class="font-semibold text-accent hover:text-accent-ink">Daftar</a>
    </p>
    <p class="mt-3 text-center text-sm">
        <a href="{{ route('home') }}" wire:navigate class="text-ink-3 hover:text-ink">&larr; Kembali ke toko</a>
    </p>
</x-layouts.guest>
