<x-layouts.app :title="$seo['title']">
    <x-seo.meta :seo="$seo" />
    @forelse ($sections as $data)
        @php $section = $data['section']; @endphp
        <x-sections.renderer :section="$section" :config="$data['config']" :products="$data['products']" :categories="$data['categories']" :faqs="$data['faqs']" :testimonials="$data['testimonials']" />
    @empty
        <div class="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="overline">Belum ada konten</p>
                <h1 class="mt-3 font-display text-4xl text-ink sm:text-5xl">Selamat datang</h1>
                <p class="mt-4 text-ink-2">Halaman beranda akan terisi setelah pemilik toko menyusun konten dari panel admin.</p>
                <a href="{{ route('shop') }}" wire:navigate class="btn btn-primary mt-8">Jelajahi Katalog</a>
            </div>
        </div>
    @endforelse
</x-layouts.app>
