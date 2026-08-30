# cmstoko

CMS-driven Laravel commerce platform: sebuah toko online lengkap dengan panel admin Filament v5, storefront yang dapat dikurasi pemilik toko, dan arsitektur domain yang bersih.

## Fitur inti

**Katalog** — produk sederhana & configurable (varian cartesian generator, harga/stok/SKU per varian), kategori bertingkat, merek, atribut + opsi (select/warna), koleksi (manual + rule-based resolver).

**Inventaris** — gudang, kartu stok (ledger 8 jenis pergerakan dengan snapshot before/after), penyesuaian & restock otomatis (batal/retur), kunci baris + update kondisional (anti oversell).

**Pesanan** — state machine dengan status history, invoice, pengiriman parsial/penuh + resi, refund (dibatasi refundable amount), retur RMA (window, approve → restock → refund), catatan internal.

**Pembayaran** — kontrak `PaymentGateway`, driver Midtrans (webhook terverifikasi signature + nominal, ledger idempoten, anti-regresi status) & transfer manual (instruksi dari settings).

**Pelanggan** — auth storefront (rate-limited), portal akun (dashboard, pesanan, alamat, wishlist, ulasan terverifikasi, retur), checkout dengan alamat tersimpan, lacak pesanan dengan verifikasi email tamu.

**CMS** — halaman CMS (sanitized), blog (kategori/tag/status terjadwal), menu navigasi header/footer (target entitas, nested), media library (upload di-hardening: MIME riil, blocklist ekstensi, sanitasi SVG, guard delete saat file dipakai), FAQ & testimoni, settings ter-typed + UI admin, tema preset (7 preset + override warna), homepage builder (10 tipe section, jadwal tayang, reorder, duplikasi).

**Pemasaran** — kupon (fixed/persen, atomic max_uses), flash sale (harga server-authoritative, otomatis kedaluwarsa), newsletter + unsubscribe token.

**SEO** — meta per entitas + fallback aman, canonical/OG/Twitter, schema.org (Organization, WebSite+SearchAction, BreadcrumbList, Product+Offer+AggregateRating dari ulasan approved nyata, Article), sitemap.xml, robots.txt, redirect manager (cached, anti open-redirect/loop).

**API** — `/api/v1` (Sanctum): katalog publik + auth + pesanan/wishlist/alamat (ownership di-enforce). Lihat `docs/API.md`.

**Keamanan** — RBAC sendiri (11 peran, 104 izin dari `config/permissions.php`, policy via trait), panel staff-only, rate limiting (checkout/kupon/review/newsletter/auth-token), upload validation, audit log (redaksi secret), CSV export streaming.

## Stack

Laravel 12 · Filament v5 · Livewire 3 · Tailwind CSS v4 (@theme tokens) · MySQL · Sanctum

## Mulai cepat

```sh
composer install
cp .env.example .env          # atur DB + (opsional) MIDTRANS_*, RAJAONGKIR_API_KEY
php artisan key:generate
php artisan migrate --seed    # seeder: peran/izin, settings, katalog demo, homepage, CMS
npm install && npm run build
php artisan serve
```

Akun demo: `admin@tokokita.test` (panel `/admin`) dan `customer@tokokita.test` — keduanya password `password`.

## Struktur domain

- `app/Services/*` — CartService, InventoryService, OrderFulfillmentService, PaymentService, MediaService, ShippingService (bersama storefront & API).
- `app/Support/*` — Settings (cached), Theme, Seo, Audit (redacted), Csv (streamed), Redirects (404 handler aman).
- `app/Shop/SectionResolver.php` — sumber data section homepage.
- `app/Livewire/*` — pulau interaktif (cart page/drawer/badge, checkout, buy-box, lacak pesanan).
- `resources/views/sections/*` + `components/*` — sistem komponen satu-untuk-semua-tema.

## Dokumentasi lanjutan

| Dokumen | Isi |
|---------|-----|
| `docs/01-MASTER-PLAN.md` | Rencana batch & arsitektur target |
| `docs/02-PROGRESS.md` | Status kontinu (baca ini dulu saat lanjut kerja) |
| `docs/04-DATABASE-MAP.md` | Peta tabel & relasi |
| `docs/05-FEATURE-MATRIX.md` | Status fitur live |
| `docs/08-SECURITY-AUDIT.md` | Temuan & mitigasi keamanan |
| `docs/12-TEST-MATRIX.md` | Cakupan test |
| `docs/13-DECISIONS.md` | Keputusan arsitektur (ADR ringkas) |
| `docs/15-KNOWN-LIMITATIONS.md` | Batasan jujur |
| `docs/API.md` | Endpoint API v1 |

## Testing

```sh
php artisan test        # 135 test — RBAC, checkout security, webhook, inventory, katalog,
                        # fulfillment, journey, track-order, cart drawer, notifikasi,
                        # audit/settings, konkuensi adversarial, SEO, API, flash sale,
                        # menu, blog, media, tema, newsletter, FAQ/testimoni
npm run build
```

## Konvensi penting

- Uang selalu integer IDR; `rupiah()` hanya memformat.
- Harga/totals dihitung server-side (klien tidak pernah mengirim total).
- Semua perubahan stok lewat `InventoryService` (menulis ledger).
- Transisi status pesanan hanya via `Order::transitionTo()`.
- Cache cache-able hanya payload array/JSON — jangan pernah menyimpan model Eloquent di cache jangka panjang.
- Semua konten klaim pemasaran dari settings/CMS, tidak pernah hard-coded di Blade.
