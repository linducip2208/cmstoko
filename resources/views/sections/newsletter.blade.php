<div class="reveal rounded-xl bg-ink px-6 py-12 text-center text-paper sm:px-12">
    <h2 class="font-display text-3xl">{{ $section->title ?? 'Ikuti kabar terbaru' }}</h2>
    @if ($section->subtitle)
        <p class="mx-auto mt-3 max-w-md text-paper/70">{{ $section->subtitle }}</p>
    @endif
    <form action="{{ route('newsletter.subscribe') }}" method="POST" class="mx-auto mt-6 flex max-w-md gap-2">
        @csrf
        <label for="newsletter-email" class="sr-only">Alamat email</label>
        <input id="newsletter-email" type="email" name="email" required placeholder="nama@email.com"
               class="input flex-1 border-transparent bg-paper/10 text-paper placeholder:text-paper/40">
        <button type="submit" class="btn bg-paper text-ink hover:bg-surface-2">Daftar</button>
    </form>
    @if (session('newsletter_status'))
        <p class="mt-3 text-sm text-paper/80" role="status">{{ session('newsletter_status') }}</p>
    @endif
</div>
