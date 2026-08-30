# 02 — PROGRESS (continuation state — READ THIS FIRST)

Last updated: 2026-08-30 · End of session 1 (large build session)

## CURRENT STATE (verified: 55 tests pass, all key pages HTTP 200, `npm run build` OK)

### DONE (implemented + tested)
- B0 Forensic audit (docs/00), plan (docs/01).
- B1 P0 SECURITY:
  - RBAC: roles/permissions tables, `config/permissions.php` registry, Role/Permission models, 11 seeded roles, 104 permissions, `Gate::before` super-admin bypass, policies (Product/Category/Coupon/User/Role/Order/ReturnRequest via `AuthorizesByPermission` trait), Filament `canAccessPanel()` fixed (staff only), UserResource + RoleResource (users/roles management), SalesReport gated `reports.view`.
  - Checkout races: `lockForUpdate` on products+variants, conditional decrement → now via InventoryService; atomic coupon `used_count` capped by max_uses; rate limiters (checkout 12/2min per session, coupon apply 8/2min, reviews/newsletter throttled).
  - Order page ownership: guests only orders placed in same session (`session shop.orders`), customers own orders, staff via permission.
  - Midtrans webhook: signature + amount verification, `payment_transactions` ledger with unique (gateway, transaction_id) idempotency, no paid→pending regression, cancelled stays cancelled, challenge handling, expire→cancel restocks.
  - Upload MIME whitelists (jpeg/png/webp/avif) on product/category/brand/collection uploads.
  - Order state machine: `Order::TRANSITIONS` + `transitionTo()` with `order_status_history` rows; admin actions use transitions; invalid transitions throw.
- B2 CATALOG: brands, nested categories (parent_id, covers, short_description), attributes + options (select/color), product_variants + variant attribute values, `ProductVariant::findMatching`, variant pricing fallback, collections (manual + rule-based `resolveProducts()`), product types simple|configurable, product search scope (name/desc/sku/brand/category), `attribute_values` JSON for non-variant attributes. Filament: BrandResource, AttributeResource (options repeater), CollectionResource (rules/manual sync), ProductResource v2 (tabs + VariantsRelationManager + "Buat Varian Otomatis" cartesian generator).
- B3 INVENTORY: warehouses (default MAIN seeded), stock_movements ledger (8 types, before/after snapshots, polymorphic reference), InventoryService (deduct/increase/adjust with locks, no negative stock, history query). Order cancel → restock via `sale_cancel` movements. StockMovementResource (read-only kartu stok).
- B4 ORDERS/FULFILLMENT: invoices, shipments + items (partial/full shipping with per-item quantities, auto status transitions), refunds + items (capped at refundable, partial_refunded/refunded statuses), return_requests + items (RMA admin approve/reject/received-restock/refunded), order_notes. OrderFulfillmentService. OrderResource ViewOrder: timeline, shipments, notes, invoice/ship/refund actions (permission-gated). PaymentGateway contract + MidtransGateway + ManualTransferGateway + PaymentManager.
- B5 CUSTOMERS: storefront auth (login/register/forgot/reset — custom controllers, rate-limited login, Password::min(8)), customer role auto-assign, guest layout (split editorial design), customer portal: dashboard (stats + recent orders), orders list w/ status filter, order detail (items/timeline/shipments/address summary + review form per item), addresses CRUD + default, wishlist (toggle/index/remove), returns (create from order window, list), profile + password change. Reviews engine: model, verified-purchase flag, moderation (pending→approved), aggregate rating from real approved data only.
- B7 (partial) CMS + SETTINGS: `settings` table + cached `App\Support\Settings` service (branding, header announcement, footer, policies, trust bar items, socials, bank accounts), CmsPage model (draft/scheduled/published/archived, sanitized render), pages controller + view, seeded Tentang/S&K/Privacy, CMS pages in footer.
- B8 APPEARANCE: `homepage_sections` table, HomepageSection model (active scope with scheduling), `SectionResolver` (product sources: featured/new/best/discount/collection/category/ids), section partials (hero, product_grid, category_grid, rich_text, banner, trust_bar, newsletter, cta), HomeController renders DB-driven sections (empty-state safe), HomepageSectionResource Filament (type-conditional fields, schedule, replicate, reorder), HomepageSeeder (hero/trust/category/editor picks/new arrivals/cta).
- B9 STOREFRONT REBUILD (majority): new design system in `resources/css/app.css` (paper/ink/terracotta tokens, Instrument Sans + Instrument Serif, btn/input/card/badge/rating/skeleton/reveal primitives, prefers-reduced-motion respected), new app layout (sticky header, announcement from settings, mega-ish dropdown nav from nested categories, expandable search, account/wishlist/cart icons — admin icon only for staff, mobile drawer nav, CMS/settings-driven footer with socials + WhatsApp), new ProductCard (second-image hover, badges, variant count, low stock), Breadcrumb/Rating/Money/Badge/EmptyState/Button components, guest + account layouts, rebuilt: home, shop (sidebar filters: category tree, brands, price, stock + sort + active filter chips + mobile filter drawer), PDP (gallery, real ratings, variant selector via Livewire buy-box with live price/stock/sku, wishlist toggle, sanitized description, approved reviews, related), cart page (variant-aware keys, coupon, sticky summary), checkout page (restyle, variant-aware), auth pages, portal pages. Mojibake purged from all blade files.
- MIGRATIONS: all additive; existing data survives (products/categories/orders kept, columns added).
- SEEDERS: RbacSeeder, SettingsSeeder (warehouse + settings), CatalogSeeder (brands, nested cats, attributes, 13 products incl. 3 configurable w/ variants, collections, coupons), CmsSeeder, HomepageSeeder, admin+customer demo users (`admin@tokokita.test` / `customer@tokokita.test`, password `password`).

### TESTS (55 passing)
RbacTest(8), AdminPanelTest(7), CheckoutSecurityTest(6), MidtransWebhookTest(8), InventoryTest(5), CatalogTest(8), FulfillmentTest(8), CustomerJourneyTest(3), ExampleTest(2).

## IN PROGRESS
- Nothing mid-flight; next batch not started.

## NEXT QUEUE (in order)
1. B9 tail: cart drawer in header (component + Livewire), track-order view restyle (old view remains), order-success restyle, checkout: address book integration (saved addresses prefill).
2. B10 SEO: meta manager (per-entity `seo` JSON already on products/categories/brands/collections/pages), layout `@stack('meta')` renderer, canonical/OG/Twitter tags, sitemap.xml route, robots.txt, schema.org (Product, BreadcrumbList, Organization, Article), redirect manager (seo_redirects table + middleware).
3. B11 API: Sanctum install, /api/v1 routes (products/categories/brands/collections read, auth, account, wishlist, orders), resources + rate limiting + tests.
4. B12 ADMIN: reports +CSV export (sales/products/inventory/customers), settings UI page, dashboard widgets (use Order::PAID_STATUSES), audit log table for sensitive actions (orders.refund, inventory.adjust, users/roles changes, settings.update).
5. B13 EVENTS/NOTIFICATIONS: OrderPlaced/PaymentReceived/OrderShipped events + mail templates + queue; WhatsApp-ready notification channel stub.
6. B14 TESTS: concurrency (parallel final-stock), API tests, flash sale/campaign tests after B6 remainder.
7. B6 REMAINDER: flash sales (flash_sales + products + countdown, server-validated pricing), banners table, cart rules (conditions JSON), newsletter admin listing.
8. B15 hostile audit + visual second pass + docs finalization (README, INSTALLATION, DEPLOYMENT, CMS-GUIDE, STORE-ADMIN-GUIDE, API, PAYMENT, SHIPPING, THEME-DEVELOPMENT).

## Batch log
| Batch | Status | Evidence |
|-------|--------|----------|
| B0 audit | DONE | docs/00 |
| B1 RBAC+security | DONE | 55 tests pass; C1–C8,C12 fixed |
| B2 catalog | DONE | CatalogTest |
| B3 inventory | DONE | InventoryTest |
| B4 orders/fulfillment | DONE | FulfillmentTest + MidtransWebhookTest |
| B5 customers | DONE | CustomerJourneyTest |
| B6 marketing | PARTIAL (coupons v2 done; flash sale/banners/cart rules TODO) | — |
| B7 CMS | PARTIAL (pages+settings done; blog/menus/media/FAQ/testimonials/redirects TODO) | — |
| B8 appearance | PARTIAL (sections+builder done; theme presets TODO) | — |
| B9 storefront | MOSTLY DONE (cart drawer, track-order, order-success restyle TODO) | HTTP smoke 200 |
| B10 SEO | TODO (seo columns exist, renderer/sitemap/schema TODO) | — |
| B11 API | TODO | — |
| B12 admin | PARTIAL (users/roles/inventory/reports-basic done; CSV/settings UI/audit TODO) | — |
| B13 events | TODO | — |
| B14 tests | PARTIAL (55 pass; concurrency/API tests TODO) | — |
| B15 audit | TODO | — |

## Verification commands
- `php artisan test` (55 passing)
- `npm run build`
- `php artisan serve` + smoke pages: /, /produk (+filters), /produk/{slug}, /keranjang, /checkout (302 empty cart), /masuk, /daftar, /lupa-kata-sandi, /lacak, /halaman/{slug}, /pesanan/{n} (404 unknown), /akun (302 guest), /admin (403 customer)
- `storage\strip-bom.ps1` after any Set-Content writes (BOM killer)
- `storage\fix-mojibake.php` for stray mojibake

## Conventions (stable)
- Filament v5: Schemas\Schema, Schemas\Components\Section/Tabs, Actions\Action, infolist = schema, policies via `AuthorizesByPermission` trait + `permissionPrefix()`.
- Relations: Role->users is hasMany (not belongsToMany).
- Cart session format: `{"{productId}" or "{productId}:{variantId}" => qty}`.
- Computed props in Livewire views: use `$this->prop`.
- Money: int IDR; `rupiah()` helper.
- Blade anonymous components REQUIRE @props for array props (else attribute-bag trim() crash).
- All blade content claims must come from settings/CMS — never hard-coded marketing claims.
