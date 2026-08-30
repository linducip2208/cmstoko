<x-layouts.guest title="Lupa Kata Sandi">
    <h1 class="text-3xl font-semibold tracking-tight text-ink">Lupa kata sandi?</h1>
    <p class="mt-2 text-sm text-ink-2">Masukkan email kamu dan kami akan mengirim tautan untuk mengatur ulang kata sandi.</p>

    @if (session('status'))
        <div class="mt-6 rounded-md border border-positive/30 bg-positive-soft px-4 py-3 text-sm text-positive" role="status">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="email" class="label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" maxlength="150"
                   class="input @error('email') input-error @enderror" placeholder="nama@email.com">
            @error('email')
                <p class="mt-1.5 text-sm text-negative" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-full">Kirim Tautan Reset</button>
    </form>

    <p class="mt-8 text-center text-sm">
        <a href="{{ route('login') }}" wire:navigate class="text-ink-3 hover:text-ink">&larr; Kembali ke halaman masuk</a>
    </p>
</x-layouts.guest>
