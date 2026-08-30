# 01 — MASTER PLAN

Mission: evolve cmstoko into a production-grade, CMS-driven Laravel commerce platform.
Principles: additive migrations (existing data survives), shared domain services (headless-ready),
CMS-configurable storefront, no fake claims, integer IDR money, Livewire only where interactivity pays,
Blade SSR for SEO pages, RBAC everywhere, tests per module.

## Batch order (each ends with tests + progress update)
- B1 P0 SECURITY: RBAC (roles/permissions/gates/policies), panel access fix, upload validation, rate limits, order page ownership, webhook idempotency, checkout races (locking + atomic coupon).
- B2 CATALOG CORE: brands, nested categories, attributes/sets/options, product variants (configurable), product types, collections, pricing service, product model refactor.
- B3 INVENTORY: warehouses, levels, movements, reservations, transactions+locks, restore-on-cancel, low stock.
- B4 ORDER DOMAIN: state machine, status history, notes, invoices, shipments, refunds, returns (RMA), payment transactions; Midtrans gateway contract + idempotency; manual transfer driver.
- B5 CUSTOMERS: storefront auth, profile, addresses, groups, portal (orders/detail/tracking/wishlist/reviews/returns), wishlist engine, reviews engine (moderation, verified purchase, aggregate).
- B6 MARKETING: coupons v2 (per-customer limits), flash sales, banners, newsletter, customer-group pricing hooks.
- B7 CMS: pages, blog (cats/tags), FAQ, testimonials, menus (nested), media library, settings system (typed, cached), footer/header CMS-driven, redirect manager.
- B8 APPEARANCE: theme system + presets, branding, homepage builder (section registry + Filament builder UI), section components.
- B9 STOREFRONT REBUILD: design system tokens, header/mega menu/predictive search/mobile drawer, footer, home sections, category/brand/collection pages + filters, PDP (variants/gallery/reviews/related), cart (page+drawer), checkout rebuild, order success, track order, auth pages. Fix all mojibake.
- B10 SEO: meta manager per entity, canonical/OG/Twitter, sitemap.xml, robots, schema.org (Organization, WebSite, Product, Article, BreadcrumbList), redirects enforcement.
- B11 API: /api/v1 (products/categories/brands/collections, auth, account, wishlist, orders), Sanctum, resources, rate limits.
- B12 ADMIN: navigation re-org, dashboard metrics, reports+CSV, settings UI, users/roles management, audit log.
- B13 EVENTS/NOTIFICATIONS: OrderPlaced etc., mail templates, queue.
- B14 TESTS SWEEP: unit domain (pricing/inventory/order state/refund), feature (RBAC, catalog, variants, cart, checkout, payment, CMS, SEO, API), concurrency suite.
- B15 VISUAL QA + hostile audit + docs finalization.

## Target architecture
- Domain: `app/Services/*` + `app/Actions/*` (checkout, pricing, inventory, orders, payments, shipping).
- Contracts: `PaymentGateway`, `ShippingProvider`, `SearchEngine` (driver-ready).
- Settings: `settings` table + `app/Settings/StoreSettings` (cached, invalidating).
- Sections: `app/Shop/Sections/*Section` registry rendering Blade components.
- Theme: `config/theme-presets.php` + settings store; CSS custom properties emitted in layout.
- Frontend: design tokens in `resources/css/app.css` (@theme), components under `resources/views/components/*`.
