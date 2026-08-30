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

## SESSION 2 ADDITIONS (2026-08-30, later)
- B9 TAIL DONE: header cart drawer (Livewire CartDrawer: variant-aware, qty/remove, coupon, savings, focus-trap/Esc/scroll-lock/ARIA, empty+loading states, auto-open on add), track-order rebuilt (guest email verification — wrong email = not-found, timeline, shipments w/ resi, payment instructions from settings), order-success rebuilt (status+payment state, next steps, transfer instructions from settings, shipping summary, CTAs), checkout saved-address prefill (default address autofill, radio selector, "alamat baru" reset, guest checkout preserved).
- Latent bug fixed: `x-ui.badge` multi-line match inside `{{ }}` broke Blade compiler (never used before). a-button/b-button given proper @props.
- B10 SEO DONE: `App\Support\Seo` (meta payloads + entity seo JSON meta_title/meta_description), `x-seo.meta` renderer (description/robots/canonical/OG/Twitter/JSON-LD), schema.org Organization + WebSite(+SearchAction) + BreadcrumbList + Product (Offer + AggregateRating ONLY from real approved reviews), sitemap.xml (cached 1h, active/published only), robots.txt route, filtered shop pages noindex.
- B10 redirect manager DONE: seo_redirects table + SeoRedirect model (cached lookup, flushed on save) + 404 render hook in bootstrap/app.php (safe: internal paths only, scheme whitelist, external hosts blocked, self/loop/chain>3 blocked, hit_count/last_hit_at tracked) + Filament SeoRedirectResource (SEO group) + SeoRedirectPolicy (redirects.*).
- Layout: base description meta only when explicit (no dupes); pages pass :title + x-seo.meta.
- B13 EVENTS/NOTIFICATIONS DONE: OrderPlaced + OrderStatusChanged events (Dispatchable), OrderStatusMail queued notification (placed/paid/shipped/completed/cancelled; transfer instructions in placed mail; resi in shipped mail; Order::routeNotificationForMail targets guest+member email), listeners auto-discovered (Laravel 11+ discovers Listeners — do NOT also Event::listen manually, it double-sends), dispatched from checkout + Order::transitionTo.
- B12 ADMIN DONE (core): ManageSettings Filament page (Store/Header/Footer/SEO/Policies/Payments tabs, FileUploads, bank accounts repeater, settings.update gated, audit on save, cache flush); audit_logs table + Audit support (redacts password/token/secret/key values recursively, never throws) + AuditLogResource (read-only, audit-logs.view gated); wired into settings.save, InventoryService::adjust, OrderFulfillmentService::refund, RoleObserver; CSV exports (SalesReport orders + StockMovements kartu stok, streamed w/ cursor + BOM via App\Support\Csv).
- AUDIT FIXES (hostile pass 1):
  - CRITICAL cart bug: PHP casts numeric-string array keys to int → `$item['key']` was int but UI passed string → ALL cart qty buttons (page + drawer) silently no-op'd for simple products. Fixed in CartService::items() ('key' => (string) $key).
  - Return over-return: return quantity now capped against previously requested quantities (rejected/cancelled excluded) across attempts.
  - empty-state component missing @props → spilled title/description into invalid HTML attributes.
  - Header nav categories cached 5 min + CategoryObserver invalidation.
  - ShopController mojibake fixed; price filter separator.
- Tests: +34 (TrackOrder 5, Seo 10, OrderNotification 3, AuditAndSettings 7, ConcurrencyAdversarial 6, CartDrawer 3) + ApiTest(9) → 98 passing. Sanctum migrations published; personal_access_tokens table.
- B6 REMAINDER DONE (core): Flash sales (flash_sales + flash_sale_products pivot, server-authoritative pricing via cached 30s price map wired into Product::effectivePrice/hasDiscount/discountPercent; FlashSaleResource admin w/ product repeater + price/limit; FlashSaleTest 5 — expiry revert, cheapest-overlap, checkout server recompute); Newsletter admin (NewsletterSubscriber model ADDED — controller referenced it but model was missing/500; token+source columns; tokenized unsubscribe /newsletter/berhenti/{token}; NewsletterSubscriberResource read-only + CSV + SelectFilter; NewsletterTest 4).
- Tests: 107 passing.
- API v1 details: `routes/api.php` (prefix /api/v1). Public catalog read endpoints (throttle 60/min) + auth/token (throttle 5/min) + auth:me/refresh/revoke + orders (ownership-filtered), addresses, wishlist. API Resources: Product/Category/Brand/Collection/Order/Address/Profile/Wishlist. Product has approvedReviews() relation for real rating aggregates; OrderResource excludes notes/refunds. Auth guard resets needed between in-process test requests (see ApiTest::resetAuth).

## NEXT QUEUE (in order)
1. B6 REMAINDER: flash sales (server-validated pricing), banners, cart rules, newsletter admin listing + safe unsubscribe.
2. B9/B8: theme presets (settings-driven CSS var overrides), menu builder, media library, blog.
3. B15 hostile audit round 2 (mobile widths via rendered DOM checks, a11y pass) + docs finalization (README/INSTALLATION/etc).

## Batch log (session 2 delta)
| Item | Status | Evidence |
|------|--------|----------|
| B9 tail (drawer/track/success/addresses) | DONE | CartDrawerTest, TrackOrderTest, HTTP 200 smoke |
| B10 SEO + redirects | DONE | SeoTest(10) |
| B13 events/mail | DONE | OrderNotificationTest(3) |
| B12 settings/audit/CSV | DONE (core) | AuditAndSettingsTest(7) |
| B14 concurrency/adversarial | DONE (core) | ConcurrencyAdversarialTest(6) — variant oversell, price authority, duplicate submit, coupon double-spend, return over-cap, refund over-cap |
| B11 API v1 | DONE (core) | Sanctum tokens; ApiTest(9): catalog, token issue/revoke/rate-limit, orders ownership (IDOR), wishlist; docs/API.md |

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
- `php artisan test` (89 passing)
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
