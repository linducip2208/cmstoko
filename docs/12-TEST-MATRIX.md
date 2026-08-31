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
| API | feature | PASS (9) — ApiTest (catalog, auth, IDOR, rate limit) |
| Flash sales | feature | PASS (5) — FlashSaleTest (expiry, overlap, checkout authority) |
| Menu builder | feature | PASS (5) — MenuTest (fallback, dead targets, external block, nesting) |
| Blog | feature | PASS (6) — BlogTest (status gates, filters, XSS sanitize, schema) |
| Media library | feature | PASS (8) — MediaTest (MIME, disguised exe, SVG sanitize, in-use guard) |
| Theme presets | feature | PASS (5) — ThemeTest |
| Newsletter | feature | PASS (4) — NewsletterTest |
| Parallel true-concurrency (multi-process) | process-level | PASS — tests/Concurrency/run.php (stock race, coupon race, transition CAS) |
| Static analysis | tooling | PASS — Larastan level 5 + baseline (no new debt) |
| Style | tooling | PASS — Pint (CI-enforced) |
| CI/CD | pipeline | READY — .github/workflows/ci.yml (mysql service, pint, phpstan, tests, npm build, composer validate/audit) |

Total: 171 passing (php artisan test) + 3 multi-process concurrency tests (php tests/Concurrency/run.php).
