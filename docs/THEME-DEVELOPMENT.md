# Pengembangan Tema

## Model tema

Satu sistem komponen; tema = **override token**, bukan templat terpisah.

1. Preset di `config/theme-presets.php` — array CSS variables:
   - palet: `--color-paper|surface|surface-2|ink|ink-2|ink-3|line|line-strong|accent|accent-ink|accent-soft`
   - font pairing: `--font-sans`, `--font-display`
   - radius: `--radius-md|lg|xl`
2. Override per-toko di settings `theme.preset` + `theme.custom` (3 warna inti) via **Tampilan → Tema**.
3. `App\Support\Theme::vars()` resolve (cached, flushed saat simpan) dan layout menuliskannya sebagai `:root { ... }` SETELAH stylesheet — last-declaration wins.

## Menambah preset

```php
'vintage' => [
    'label' => 'Vintage',
    'description' => '…',
    'vars' => ['--color-paper' => '#f5efe0', /* … */ '--radius-lg' => '0.25rem'],
],
```

Tambahkan ke `presets` — langsung muncul di admin (radio) dan storefront.

## Komponen yang WAJIB dipakai (jangan hard-code warna)

- Utility semantik: `text-ink`, `bg-paper`, `border-line`, `bg-accent-soft`…
- Primitif: `.btn .btn-primary|accent|outline|ghost`, `.input`, `.card`, `.badge`, `.icon-btn`, `.overline`, `.skeleton`, `.reveal`
- Komponen Blade: `<x-ui.badge tone>`, `<x-ui.money :amount>`, `<x-ui.rating :rating>`, `<x-ui.empty-state>`, `<x-ui.breadcrumb>`

## Konvensi

- Radius/shadow dari token (`--radius-lg`, `--shadow-card`) — bukan nilai literal.
- Konten dari settings/CMS; tidak ada teks marketing hard-coded.
- Reduced motion wajib dihormati (`.reveal` sudah; animasi baru ikut `prefers-reduced-motion`).
- Blade anonymous components WAJIB `@props`.
- Jangan cache model Eloquent — cache array/JSON saja.
