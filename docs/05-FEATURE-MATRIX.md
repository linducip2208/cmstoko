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
| Blog | TODO | — |
| Menus | TODO | header nav derives from categories; menu builder TODO |
| Media | TODO | uploads validated; no library UI |
| Settings | DONE | cached key-value w/ groups (branding/header/footer/policies/payments) |
| Homepage builder | DONE | DB sections, schedule, conditional forms, reorder, replicate |
| Theme system | TODO | tokens centralized; presets TODO |
| Storefront redesign | DONE (core) | home/shop/PDP/cart/checkout/auth/portal rebuilt; track-order & order-success restyle TODO |
| Search | PARTIAL | SQL LIKE across name/sku/brand/category; driver contract TODO |
| Filters | DONE | category tree, brand, price, stock, sort |
| SEO | PARTIAL | seo JSON columns everywhere; meta renderer/sitemap/schema/redirects TODO |
| API | TODO | services are shared/domain-ready |
| Reports | PARTIAL | sales page + widgets; CSV/other reports TODO |
| Events/Notifications | PARTIAL | password resets only; order lifecycle mails TODO |
| Tests | PARTIAL | 55 passing (RBAC/security/webhook/inventory/catalog/fulfillment/journey); concurrency/API suites TODO |
