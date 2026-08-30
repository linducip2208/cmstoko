# API v1

Base URL: `/api/v1` · Format: JSON · Auth: Laravel Sanctum personal access tokens.

## Error format

Validation errors (422) and standard errors follow Laravel's default JSON shape:

```json
{"message": "...", "errors": {"field": ["..."]}}
```

## Rate limits

| Endpoint group | Limit |
|----------------|-------|
| Public catalog | 60/min per IP |
| `POST /auth/token` | 5/min per IP |

## Public (no auth)

### `GET /api/v1/products`
Paginated list. Query params: `q` (search), `category` (slug), `brand` (slug), `min`/`max` (IDR price), `stock=in`, `sort` (`recommended|latest|price_asc|price_desc|best|discount`), `per_page` (max 50).

### `GET /api/v1/products/{slug}`
Full product incl. `variants` (id, sku, price, stock, label, options) for configurable products, `images`, and approved-review aggregates only (`rating`, `reviews_count` — never fabricated).

### `GET /api/v1/categories`
Root categories with active children (id, name, slug).

### `GET /api/v1/brands`
Active brands (id, name, slug, description, logo).

### `GET /api/v1/collections`
Active featured collections (id, name, slug, description).

## Auth

### `POST /api/v1/auth/token`
```json
{"email": "you@example.com", "password": "secret", "device_name": "mobile-app"}
```
→ `201 {"token": "...", "token_type": "Bearer", "user": {...}}`

### Authenticated (`Authorization: Bearer <token>`)
- `GET /api/v1/auth/me` — profile.
- `POST /api/v1/auth/refresh` — revoke current token, issue new one.
- `DELETE /api/v1/auth/token` — revoke current token.

## Customer (auth)

- `GET /api/v1/orders` — own orders, paginated. Filter: `status` (valid order statuses).
- `GET /api/v1/orders/{order_number}` — own order detail (items, shipments + tracking, status history, summary, shipping). Ownership is enforced server-side; other customers' orders return 404. Internal admin notes and refund details are excluded.
- `GET /api/v1/addresses` — saved addresses.
- `GET /api/v1/wishlist` — wishlist products.
- `POST /api/v1/wishlist` `{"product_id": 1}` — toggles; returns `{"wishlisted": true|false}`.

## Privacy guarantees

- Products: no cost fields exist; only public price data is exposed.
- Orders: `order_notes`, refund records, and admin metadata are never serialized.
- Ratings: computed from approved reviews only.
- Tokens: bearer tokens are hashed server-side; refresh/revocation supported.
