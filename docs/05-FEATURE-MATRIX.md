# 05 — FEATURE MATRIX (live status)
Legend: DONE | PARTIAL | TODO

| Area | Status | Notes |
|------|--------|-------|
| RBAC | DONE | 11 roles, 104 permissions, policies+gates, panel gate, users/roles admin |
| Panel access control | DONE | staff-only canAccessPanel |
| Products CRUD | DONE | types simple/configurable, SEO json, search scope |
| Categories | DONE | nested, covers, counts |
| Brands | DONE | + landing data via shop filter |
| Attributes | DONE | select/color + options repeater |
| Variants | DONE | cartesian generator, per-variant price/stock/SKU, checkout+order integration |
| Collections | DONE | manual + rule-based resolver |
| Inventory engine | DONE | movements ledger, locks, adjust/restock, kartu stok admin |
| Orders domain | DONE | state machine, history, notes, partial/full shipment, invoices, refunds caps |
| Returns/RMA | DONE | request window, admin approve/receive-restock/refund |
| Payments | DONE | Midtrans (hardened webhook) + manual transfer + gateway contract/manager |
| Shipping | PARTIAL | RajaOngkir/flat via service; provider contract extraction TODO |
| Customers/Portal | DONE | dashboard, orders, addresses, profile/password, returns |
| Auth storefront | DONE | login/register/forgot/reset, rate-limited |
| Wishlist | DONE | toggle/index, verified in journey test |
| Reviews | DONE | verified-purchase, moderation, real aggregates |
| Coupons | DONE | fixed/percent, window, atomic max_uses |
| Flash sale | TODO | — |
| CMS pages | DONE | sanitized rich content, statuses |
| Blog | DONE (core) | posts/cats/tags, statuses+scheduling, sanitized rich content, Article schema, sitemap, admin |
| Menus | DONE (core) | header/footer menus, nested items, entity targets, safe URLs, cached, admin builder |
| Media | DONE (core) | hardened upload service (MIME/extension blocklist, SVG sanitize), library UI, metadata, in-use delete guard |
| Settings | DONE | cached key-value w/ groups (branding/header/footer/policies/payments) + admin UI + audit |
| Homepage builder | DONE | DB sections, schedule, conditional forms, reorder, replicate |
| Theme system | DONE (core) | 7 presets (token overrides, one component system) + admin color overrides + instant activation |
| Storefront redesign | DONE (core) | home/shop/PDP/cart/checkout/auth/portal/track/success rebuilt; cart drawer live |
| Search | PARTIAL | SQL LIKE across name/sku/brand/category; driver contract TODO |
| Filters | DONE | category tree, brand, price, stock, sort |
| SEO | DONE (core) | meta renderer, canonical/OG/Twitter, schema.org (Org/WebSite/Breadcrumb/Product+AggregateRating), sitemap.xml, robots.txt, noindex filtered pages, redirect manager + admin |
| API | DONE (core) | /api/v1: public catalog, Sanctum auth, orders/wishlist/addresses; IDOR-tested; docs/API.md |
| Reports | PARTIAL | sales page + widgets + CSV (orders, kartu stok); settings UI + audit log done; more reports TODO |
| Events/Notifications | DONE (core) | OrderPlaced/Paid/Shipped/Completed/Cancelled queued mails; WhatsApp stub TODO |
| Tests | PARTIAL | 131 passing (RBAC/security/webhook/inventory/catalog/fulfillment/journey/track/drawer/notifications/audit-settings/concurrency/SEO/API/flash/menu/blog/media/theme/newsletter); multi-process parallel suites TODO |
