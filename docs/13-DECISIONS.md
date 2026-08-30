# 13 — DECISIONS (adr-style, short)
1. Own RBAC (not spatie): permissions registry in config/permissions.php, policies via trait, super-admin Gate::before bypass. Reason: zero deps, Filament v5 policy-native.
2. Filament v5 authorization = model policies; resources MUST NOT skipAuthorization. Navigation auto-hides via canAccess.
3. Money stays int IDR everywhere. rupiah() only formats.
4. Stock ledger: products.stock/variants.stock remain cached balances; every change writes stock_movements via InventoryService (locks + conditional updates). Restock on cancel/return.
5. Order status machine via Order::TRANSITIONS + transitionTo() writing order_status_history; admin actions call it (never forceFill).
6. Webhook = only source of payment truth; payment_transactions unique key for idempotency; amount + signature verified; regressions blocked.
7. Cart session keys productId / productId:variantId; prices always resolved server-side from DB.
8. Checkout recalculates subtotal/discount/shipping server-side; client never sends totals.
9. Reviews aggregate computed from approved reviews only; verified purchase = order_item ownership; moderation before display.
10. Homepage = homepage_sections rows + type-specific config + SectionResolver; no hard-coded marketing claims (trust bar from settings).
11. Settings: single settings table + cached Settings::get (flush on write); site identity never hard-coded in Blade.
12. CMS rich content sanitized (script/on* stripped) — renderableContent(); no uncontrolled raw HTML trust.
13. Tailwind v4 @theme tokens as the design system; semantic palette (paper/ink/accent) — no scattered hex.
14. Livewire only for interactive islands (cart, checkout, buy-box, badge); SSR Blade for SEO pages.
15. Existing-data survival: all migrations additive; seeders use updateOrCreate keyed on slug/email.
