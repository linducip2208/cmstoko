# 12 — TEST MATRIX (live)
| Area | Type | Status |
|------|------|--------|
| Baseline smoke | feature | PASS (7) |
| RBAC/panel | feature | PASS (8) — RbacTest |
| Checkout concurrency/security | feature | PASS (6) — CheckoutSecurityTest |
| Coupon limits (atomic) | feature | PASS — CheckoutSecurityTest + ConcurrencyAdversarialTest |
| Webhook idempotency | feature | PASS (8) — MidtransWebhookTest |
| Variants | feature | PASS — CatalogTest |
| Inventory | unit+feature | PASS (5) — InventoryTest |
| Order state machine | feature | PASS — FulfillmentTest |
| Returns/refunds math | feature | PASS — FulfillmentTest + ConcurrencyAdversarialTest (over-cap) |
| Track-order ownership | feature | PASS (5) — TrackOrderTest (guest email verify, stranger blocked) |
| Cart drawer interactions | feature | PASS (3) — CartDrawerTest |
| Notifications (order lifecycle) | feature | PASS (3) — OrderNotificationTest |
| Audit log + settings + CSV | feature | PASS (7) — AuditAndSettingsTest |
| Adversarial: variant oversell, price authority, duplicate submit, coupon double-spend | feature | PASS (6) — ConcurrencyAdversarialTest |
| SEO meta/sitemap/robots/redirects | feature | PASS (10) — SeoTest |
| API | feature | TODO B11 |
| Parallel true-concurrency (multi-process) | feature | TODO (sequential interleaving covered) |
| Themes/menus/blog/media | feature | TODO after features land |

Total: 89 passing (php artisan test).
