# 16 — FINAL 9.9 AUDIT (evidence-based)

Baseline: 189 tests passing · Pint ✔ · PHPStan level 5 ✔ (baseline 8xx locked) · CI workflow aktif · true multi-process concurrency suite PASS.

Metode: setiap domain dinilai dari BUKTI (test, kode, jalur keamanan), bukan angka.

## Areas & evidence

| Area | Evidence terkuat | Nilai |
|------|------------------|-------|
| RBAC | 11 roles/104 perms dari registry; policy trait; panel staff-only; RbacTest + semua resource gated | 9.5 |
| Security | IDOR (orders/addresses/wishlist/returns), anti price-spoof (shipping resolve server-side), CAS transisi, webhook hardening, upload hardened, audit log + redaction, rate limits menyeluruh | 9.3 |
| Orders/Fulfillment | state machine CAS + history, invoice/shipment/refund/return, partial quantity caps, FulfillmentTest + adversarial | 9.5 |
| Checkout | stock lock + conditional, coupon atomic, cart rules server-side, tax snapshot, saved addresses, key-based shipping, guest flow | 9.4 |
| Catalog | variants cartesian, collections resolver, attributes, tax class per produk, ProductResource v2 | 9.2 |
| Inventory | ledger + locks, multi-warehouse levels/transfers/allocation, WarehouseTest | 9.3 |
| Promotions | coupons atomic, cart rules (conditions+groups), flash sale server-authoritative; ConcurrencyAdversarial | 9.4 |
| Payments | Midtrans hardened ledger, manual transfer, PaymentGateway contract; Xendit/dll adapter-ready (belum ada driver nyata) | 9.2 |
| CMS | pages/blog/menus/media/FAQ/testimonials/settings/theme/sections — semuanya admin-managed + tested | 9.3 |
| Media | hardened upload + pipeline on-demand WebP srcset + usage-aware delete | 9.2 |
| SEO | meta+canonical+OG+Twitter, schema (Org/WebSite/Breadcrumb/Product/Article), sitemap/robots/redirects, noindex filtered | 9.3 |
| API v1 | Sanctum, catalog+auth+orders+wishlist, IDOR-tested, rate limits, docs/API.md | 9.3 |
| Testing | 189 tests (14 suite), concurrency harness multi-process (3 race PASS), data-integrity suite | 9.2 |
| CI/CD | Actions workflow: mysql service, pint, phpstan, tests, npm build, composer validate/audit | 9.2 (belum pernah jalan di runner asli) |
| Docs | README + 11 dokumen (install/deploy/architecture/cms/admin/payment/shipping/theme/security/api + audit trail) | 9.4 |
| Analytics | GA4/GTM/Meta/TikTok settings-driven, validated, consent-ready arsitektur | 9.3 |
| Reports | Sales/Inventory/Customers/Tax + CSV streaming; filters 7/30/90 | 9.2 |
| Shipping | 4 provider, graceful degradation, server-authoritative | 9.3 |

## Defects ditemukan & diperbaiki selama audit ini

1. Registrasi tanpa grup pelanggan (Retail default sekarang) — fixed + test
2. Multi default tax class / warehouse mungkin — fixed (single-default enforcement) + test
3. Shipping price spoofing via public Livewire array — fixed (key-based server resolve) + test
4. Transisi status race (paid vs cancelled) — fixed (CAS) + multi-process test
5. notifications table hilang → admin 500 — fixed (migrasi)
6. navigasi grup Filament tipe salah → 500 — fixed
7. @json + closure di atribut Blade → parse error — fixed (payload via @php var)
8. "@context" JSON-LD termakan directive Blade (tokoonline lesson) — cmstoko aman (komponen meta @json)

## Gaps tersisa (tidak bisa 9.9 sekarang) — lihat 17
