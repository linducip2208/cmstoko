# cmstoko

Toko online / commerce CMS berbasis **Laravel 13 + Filament 5 + Livewire 4 + Tailwind CSS 4** dengan storefront editorial dan panel admin lengkap (RBAC, katalog varian, inventaris, pengiriman, pembayaran Midtrans, CMS beranda builder).

> Status: pengembangan aktif. Lihat `docs/02-PROGRESS.md` untuk peta pengerjaan dan `docs/01-MASTER-PLAN.md` untuk rencana.

## Fitur utama

**Storefront**
- Beranda dinamis dari section builder (hero, product grid, kategori, banner, newsletter, CTA — dapat dijadwalkan)
- Katalog dengan filter (kategori bertingkat, merek, harga, stok) + sorting + pencarian
- Halaman produk: galeri, varian live (harga/stok/SKU), wishlist, ulasan termoderasi + rating agregat nyata
- Keranjang + checkout (ongkir RajaOngkir atau flat, kupon, Midtrans Snap atau transfer manual)
- Akun pelanggan: pesanan + linimasa, ulasan terverifikasi, alamat, wishlist, pengembalian (RMA)
- CMS pages (Tentang, S&K, Privasi) + identitas toko dari settings (tanpa hard-code)

**Admin**
- RBAC: 11 peran bawaan (Super Admin … Customer Support, Customer) × 104 izin granular
- Katalog: produk (simple/configurable) + generator varian otomatis, atribut, merek, kategori bertingkat, koleksi (manual/rule-based)
- Persediaan: kartu stok (ledger pergerakan), penyesuaian stok, gudang
- Penjualan: pesanan (state machine + linimasa), invoice, kirim sebagian/penuh + resi, refund, pengembalian
- Pengguna & peran, laporan penjualan, homepage builder

**Kualitas**
- Idempotensi webhook Midtrans (signature + amount + ledger), anti-oversell (row locks + conditional decrement), kupon atomik
- Uang = integer IDR di semua tempat
- 55 automated tests (RBAC, checkout race, webhook, inventory, catalog, fulfillment, journey pelanggan)

## Menjalankan

```bash
composer install
cp .env.example .env          # isi DB + MIDTRANS_SERVER_KEY (opsional) + RAJAONGKIR_API_KEY (opsional)
php artisan key:generate
php artisan migrate --seed    # membuat data + akun demo
npm install && npm run build
php artisan serve
```

Akun demo (setelah seed):
- Admin: `admin@tokokita.test` / `password` → panel `/admin`
- Pelanggan: `customer@tokokita.test` / `password`

Tanpa `RAJAONGKIR_API_KEY`, ongkir memakai tarif flat (checkout tetap jalan). Tanpa `MIDTRANS_SERVER_KEY`, pesanan memakai alur transfer manual dengan instruksi dari settings.

## Struktur domain

```
app/
  Contracts/PaymentGateway.php      # kontrak gateway pembayaran
  Services/                         # CartService, InventoryService, OrderFulfillmentService,
    Payments/                       # PaymentManager, MidtransGateway, ManualTransferGateway
  Shop/SectionResolver.php          # sumber konten section beranda
  Policies/Concerns/AuthorizesByPermission.php
  Livewire/                         # AddToCart (buy-box varian), CartPage, CheckoutPage, CartBadge
resources/css/app.css               # design tokens (@theme) + primitif
docs/                               # audit, plan, progress, db map, decisions, dst.
```

## Testing

```bash
php artisan test
```

Suite mencakup RBAC, akses panel, race stok/kupon, webhook Midtrans (replay/amount/regresi), ledger inventaris, varian & koleksi, state machine pesanan, invoice/kirim/refund, dan perjalanan pelanggan end-to-end.

## Dokumentasi

- `docs/00-MASTER-AUDIT.md` — audit forensik awal
- `docs/01-MASTER-PLAN.md` — rencana batch
- `docs/02-PROGRESS.md` — status berjalan (baca ini dulu saat melanjutkan)
- `docs/04-DATABASE-MAP.md`, `docs/05-FEATURE-MATRIX.md`, `docs/13-DECISIONS.md`, `docs/15-KNOWN-LIMITATIONS.md`
