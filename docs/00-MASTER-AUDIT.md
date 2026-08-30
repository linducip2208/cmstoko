# 00 — MASTER AUDIT (Forensic baseline, verified in source)

Date: 2026-08-30 · Baseline commit: `c0aea40` (initial). All findings verified by reading source.

## Stack (verified)
- PHP 8.3.30, Laravel 13.29, Filament 5.7.6, Livewire 4.4.2, Tailwind CSS 4 (vite), Midtrans PHP 2.6.2.
- Test DB: sqlite :memory:. 7 tests passing at baseline.

## Existing entities
- `Category` (flat, name/slug/description/image/is_active/sort_order)
- `Product` (category_id, name, slug, sku, description, price/sale_price (int IDR), stock, weight, images json, is_active, is_featured)
- `Coupon` (fixed/percent, min_purchase, max_uses, used_count, window)
- `Province`/`City` (RajaOngkir mirror)
- `Order` (order_number INV-Ymd-6rand, guest or user, address snapshot inline, subtotal/discount/shipping/total, status pending|paid|processing|shipped|completed|cancelled, midtrans fields)
- `OrderItem` (snapshot product_name/image/price/qty/subtotal)
- `User` (name/email/phone/password; FilamentUser)

## CRITICAL (P0) — security / data integrity
| # | Finding | Evidence |
|---|---------|----------|
| C1 | **Every authenticated user can access the admin panel.** `User::canAccessPanel()` returns `true` unconditionally. Any storefront customer = full admin. | app/Models/User.php |
| C2 | **No RBAC whatsoever.** No roles/permissions/policies. Any panel user can manage products, orders, coupons, users. | whole app |
| C3 | **Oversell race.** Checkout validates stock then decrements with plain `decrement()` inside a transaction but **without row locks**; two concurrent final-stock checkouts both succeed → negative stock. | CheckoutPage::placeOrder |
| C4 | **Coupon race.** `used_count` incremented unconditionally; max_uses can be exceeded concurrently. | CheckoutPage |
| C5 | **Order success page is public IDOR-lite.** `/pesanan/{orderNumber}` renders full PII for anyone with the number (numbers are guessable date + 6 alnum). No auth/ownership check. | OrderController::success |
| C6 | **Webhook regressions & no idempotency.** `pending` webhook can downgrade a paid order; `markPaid` re-fires on every replay; no transaction record; no state-machine guard. | PaymentService::handleNotification |
| C7 | **Upload validation gap.** Product FileUpload allows any MIME (no `acceptedFileTypes`) → SVG stored-XSS / arbitrary file hosting. | ProductResource |
| C8 | **No rate limiting** on checkout (`placeOrder`), coupon apply, or order success probing. | CheckoutPage |
| C9 | **`order.success` reachable for pending orders with no payment configured** → order "placed" state leaks PII + no payment gate; manual-transfer path has no proof-of-payment workflow. | OrderController |
| C10 | **Fake trust claims hard-coded** on production homepage: "4.9 Rating", "1k+ Pesanan Selesai", "24/7 CS", "Garansi 100% Original", "Retur Mudah 7 Hari", "Gratis Ongkir" — none backed by data/settings. | home.blade.php |
| C11 | **Dev credits hard-coded in storefront footer** ("Blade • Livewire • Filament"). | layout app.blade.php |
| C12 | **No inventory ledger.** Stock is a bare column decremented; cancels/refunds never restore stock. | Order flow |

## HIGH
- H1 No storefront auth at all: no login/register/forgot routes; checkout is guest-only.
- H2 No customer portal (orders/addresses/wishlist/returns).
- H3 Product engine minimal: no variants, attributes, brands, product types, collections.
- H4 Order domain lacks invoices, shipments, refunds, returns, status history, notes; admin "status" actions are free-form `forceFill` with no transition guards.
- H5 Payment is hard-wired Midtrans-or-nothing; no gateway contract; manual transfer has no instructions/proof flow.
- H6 Shipping is RajaOngkir-or-flat with no provider abstraction, no free-shipping/pickup rules.
- H7 SEO: zero meta system, no sitemap, no robots customization, no schema.org, no canonicals, no OG tags.
- H8 CMS: zero (no pages, blog, menus, media, banners, FAQ, testimonials). Homepage fully hard-coded in Blade.
- H9 Appearance: no theme system, no homepage builder, no branding settings; store name/tagline scattered.
- H10 Settings: no settings architecture (hard-coded config/shop.php including bank accounts).
- H11 No marketing engine beyond coupons (no flash sale, banners, newsletter).
- H12 No API.
- H13 Reports: one simplistic sales page; no exports.
- H14 Admin UX: barebones; no users/roles management; widgets aggregate including cancelled logic edge (revenueToday mixes created_at OR paid_at).
- H15 No events/notifications (no order emails).

## MEDIUM
- M1 Search: `LIKE '%q%'` on name+description only; no SKU/brand; no index; no predictive UX.
- M2 Category: flat (no parent_id), symbol-only cards on homepage, no covers.
- M3 Filters: category + sort only; no price/brand/attribute/availability.
- M4 Blade files contain UTF-8 mojibake (broken arrow/bullet glyphs) — visual defect.
- M5 `Category::updating` slug hook is buggy (only regenerates when original slug is blank).
- M6 ProductResource table shows `coverImage()` via ImageColumn `images` state — fragile.
- M7 Cart is session-only; no merge on login; qty cap 999 unvalidated against stock until checkout.
- M8 `rupiah()` helper formats floats; prices are ints — keep ints, format via Number::currency-style helper.
- M9 Tests: 7 smoke tests only; no domain/feature coverage.
- M10 Accessibility: contrast ok-ish but focus states, aria, dialog semantics absent; headings hierarchy in home is h1→h2→h3 ok but icon-only buttons lack labels in places.
- M11 No `robots.txt` customization, no 404/500 pages.
- M12 `config('shop.name')` used as default page title for every page — no per-page SEO.

## What is GOOD and will be kept
- Integer IDR money everywhere (no floats in DB). Keep.
- Midtrans signature verification via `hash_equals` with correct formula. Keep + harden.
- RajaOngkir fallback-to-flat pattern (outage does not kill checkout). Keep + abstract.
- Cart items re-resolve from DB at render (no price trusting from client). Keep.
- Order/item price snapshots. Keep.
- Session cart for guests. Keep + add account merge.
- Filament v5 schema-based resources (modern API). Keep conventions.

## Hard-code purge ledger
| Location | Content | Disposition |
|----------|---------|-------------|
| home.blade.php stats row | 4.9 / 1k+ / 24-7 | REMOVE (fake) |
| home.blade.php marquee | shipping/warranty/returns claims | settings-driven |
| layout footer | dev credits, tagline | settings + CMS menu |
| config shop.bank_accounts | BCA/Mandiri numbers | settings |
| config shop.tagline | tagline | settings (branding) |
| welcome.blade.php (72KB default Laravel page) | unused demo | delete route/usage |
