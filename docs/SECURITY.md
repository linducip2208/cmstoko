# Keamanan

Model ancaman: storefront publik + panel multi-peran + webhook pembayaran.

## Kontrol akses

- **RBAC sendiri**: 11 peran, 104 izin dari `config/permissions.php`; policy via `AuthorizesByPermission`; super-admin bypass `Gate::before`. Filament resource otomatis tersembunyi bila izin tidak ada.
- **Panel staff-only** (`canAccessPanel`): pelanggan/tanpa-peran → 403.
- **Ownership**: pesanan (storefront, portal, API), alamat, wishlist, retur — semua difilter `user_id` (IDOR ditest).
- **Lacak pesanan tamu**: wajib email pemesan cocok (case-insensitive); salah email = "tidak ditemukan" (tanpa kebocoran eksistensi).

## Checkout & pembayaran

- Stok: `lockForUpdate` + conditional update — oversell mustahil (test varian + produk sederhana).
- Kupon & cart rules: atomic increment berbatas kuota; aturan dievaluasi server-side di dalam transaksi.
- Harga/totals: dihitung server dari DB saat checkout (test: harga berubah setelah masuk keranjang → tetap otoritatif).
- Midtrans webhook: signature hash_equals, verifikasi nominal, ledger idempoten (anti replay), anti-regresi status, expire→cancel+restock.

## Input & konten

- Semua output Blade di-escape; konten rich (CMS/blog) disanitasi saat render (`renderableContent()`: script + handler inline dibuang) — ditest.
- Validasi ketat di semua endpoint (Shop, API, Livewire).
- **Upload media**: MIME riil via finfo (bukan header client), blocklist ekstensi (php/phtml/phar/exe/sh/bat/js/html), maks 5 MB, nama file diacak, SVG disanitasi (script/on*/javascript:). Test: exe tersamar & txt ditolak.
- Redirect SEO: hanya path internal / domain sama; skema dibatasi http(s); anti loop & rantai >3. Test open-redirect + `javascript:`.
- Menu kustom: eksternal diblokir saat render.

## Rate limiting

| Titik | Batas |
|-------|-------|
| Checkout | 12 / 2 mnt / sesi |
| Kupon | 8 / 2 mnt / sesi |
| Ulasan & newsletter | 10–5 / mnt |
| API publik | 60 / mnt |
| API auth token | 5 / mnt |

## Audit

Tabel `audit_logs` mencatat: settings.update, inventory.adjust, refund.create, role.*, tema. Nilai secret (password/token/secret/key/signature) di-redaksi rekursif. Viewer read-only, izin `audit-logs.view`.

## Lain-lain

- Password bcrypt + minimal 8; reset via token (framework).
- CSRF aktif (web); API memakai bearer token.
- `APP_DEBUG=false` di produksi; secret hanya di env.
- Login rate-limited; sesi regenerate.
