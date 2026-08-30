@props(['seo'])
@php
    $siteName = \App\Support\Settings::get('store.name', config('shop.name', 'TokoKita'));
@endphp
@push('meta')
    @if (! empty($seo['description']))
        <meta name="description" content="{{ $seo['description'] }}">
    @endif

    @if (! empty($seo['robots']))
        <meta name="robots" content="{{ $seo['robots'] }}">
    @endif

    @if (! empty($seo['canonical']))
        <link rel="canonical" href="{{ $seo['canonical'] }}">
    @endif

    {{-- Open Graph --}}
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $seo['title'] }}">
    @if (! empty($seo['description']))
        <meta property="og:description" content="{{ $seo['description'] }}">
    @endif
    @if (! empty($seo['canonical']))
        <meta property="og:url" content="{{ $seo['canonical'] }}">
    @endif
    @if (! empty($seo['image']))
        <meta property="og:image" content="{{ $seo['image'] }}">
    @endif

    {{-- Twitter --}}
    <meta name="twitter:card" content="{{ ! empty($seo['image']) ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $seo['title'] }}">
    @if (! empty($seo['description']))
        <meta name="twitter:description" content="{{ $seo['description'] }}">
    @endif
    @if (! empty($seo['image']))
        <meta name="twitter:image" content="{{ $seo['image'] }}">
    @endif

    {{-- JSON-LD --}}
    @foreach (($seo['schema'] ?? []) as $graph)
        <script type="application/ld+json">@json($graph)</script>
    @endforeach
@endpush
