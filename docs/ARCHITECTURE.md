# Arsitektur

## Prinsip

1. **Additive migrations** — data existing selamat; kolom ditambah, tidak diubah/dihapus.
2. **Shared domain services** — storefront, admin, dan API memanggil service yang sama.
3. **CMS-configurable** — tidak ada klaim/konten hard-coded di Blade.
4. **Uang integer IDR** — `rupiah()` hanya format.
5. **Server-authoritative** — harga, diskon, ongkir, total dihitung server; klien tidak pernah mengirim total.
6. **Livewire hanya untuk interaktivitas** — halaman SEO SSR Blade.

## Lapisan

```
HTTP (Blade SSR / Livewire / API v1)
        │
Controllers + Livewire Components
        │
app/Services/*        ← logika bisnis bersama
  CartService         keranjang session, kupon
  InventoryService    stok: lock baris, ledger, restock
  OrderFulfillmentService  invoice, shipment, refund
  PaymentService      Midtrans (webhook idempoten) + contract PaymentGateway
  MediaService        upload di-hardening
  ShippingService     RajaOngkir / fallback flat
  DatabaseSearchEngine ← implement SearchEngine contract
        │
Eloquent Models (ledger: StockMovement, OrderStatusHistory, PaymentTransaction, AuditLog)
        │
MySQL
```

## Keputusan kunci (lihat docs/13 untuk daftar lengkap)

- RBAC sendiri, bukan spatie: registry di `config/permissions.php`, policy via trait `AuthorizesByPermission`, super-admin bypass via `Gate::before`.
- Stok = cached balance (`products.stock` / `product_variants.stock`) + ledger wajib; perubahan hanya via `InventoryService` (lock + conditional update).
- Status pesanan hanya berubah via `Order::transitionTo()` (state machine + history + event `OrderStatusChanged`).
- Webhook Midtrans = satu-satunya sumber kebenaran pembayaran; ledger `payment_transactions` idempoten per transaction_id.
- Event listener Laravel auto-discovered — JANGAN mendaftarkan manual lagi (double-send).
- Cache hanya menyimpan array/JSON — model Eloquent dilarang di cache jangka panjang (incomplete-object).

## Promosi

- **Coupon** — kode, masuk via input pengguna, atomic `max_uses`.
- **CartRule** — otomatis di checkout: kondisi (subtotal/produk/kategori/merek/jumlah/grup) + aksi (persen/nominal/gratis ongkir), stacking dibatasi subtotal, tercatat di `orders.applied_rules`.
- **FlashSale** — harga per produk, aktif per jendela waktu, masuk ke `Product::effectivePrice()` (cached map 30 dtk) sehingga semua permukaan (PDP, keranjang, checkout, API) konsisten.

## Frontend

- Design tokens Tailwind v4 `@theme` di `resources/css/app.css`; tema preset = override CSS variables di `<head>`.
- Komponen anonim wajib `@props` (bug attribute-bag).
- Keranjang: Livewire `CartPage`, `CartDrawer`, `CartBadge` — semua lewat `CartService`; key item string `"{productId}"` / `"{productId}:{variantId}"`.
