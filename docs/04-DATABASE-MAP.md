# 04 — DATABASE MAP (current)

## Original tables (extended in place)
- categories (+parent_id FK self, icon, cover_image, short_description, seo json)
- products (+brand_id FK, type simple|configurable, short_description, seo json, attribute_values json, published_at)
- coupons / provinces / cities / orders / order_items (+variant_id FK, variant_label)
- users (+role_id FK, phone)

## RBAC
- roles (slug unique, is_staff, is_system) · permissions (slug unique, group) · permission_role pivot · users.role_id

## Commerce integrity
- payment_transactions (unique gateway+transaction_id, payload json, processed_at)
- order_status_history (from, to, note, user_id, created_at)
- customer_addresses (user FK, is_default)

## Catalog v2
- brands (slug, logo, cover, seo)
- attributes (type select|color|text|number, is_variant, is_required) · attribute_options (unique attribute+value, color hex)
- product_variants (sku unique nullable, barcode, price/sale/cost nullable → fallback product, stock, weight, image, position)
- product_variant_attribute_values (unique variant+attribute)
- collections (type manual|rules, rules json) · collection_products (unique pair, sort_order)

## Inventory
- warehouses (code unique, is_default)
- stock_movements (type: opening|purchase|sale|sale_cancel|return|adjustment|transfer_in|transfer_out, signed quantity, stock_before/after, reference morph, user)
- stock_reservations (product, variant nullable, quantity, order nullable, expires/released)

## Fulfillment
- order_notes · invoices (invoice_number unique, status issued|paid|cancelled)
- shipments (shipment_number unique, courier/service/tracking, status, cost) · shipment_items (order_item FK, quantity)
- refunds (refund_number unique, amount, status pending|processed|rejected) · refund_items
- return_requests (return_number unique, status requested|approved|rejected|received|refunded|cancelled, reason) · return_items

## Customers / engagement
- wishlists (unique user+product) · reviews (rating 1-5, status pending|approved|rejected, is_verified, order_item_id nullable)

## CMS / content
- cms_pages (slug unique, status draft|scheduled|published|archived, published_at, seo json)
- homepage_sections (type, config json, sort_order, is_active, starts_at/ends_at)
- settings (key unique, value json, group) → newsletter_subscribers (email unique)

## SEO (session 2)
- seo_redirects (source unique, destination, status_code 301|302, is_active, hit_count, last_hit_at)
- audit_logs (user_id/user_name, action, subject_type+id, before/after json redacted, ip, user_agent)
- flash_sales (starts/ends, is_active) → flash_sale_products (product_id, flash_price, stock_limit)
- newsletter_subscribers + token unique, source
- menus (location header|footer) → menu_items (parent_id self, target_type custom|category|brand|page, target_id, url)
- personal_access_tokens (Sanctum)
- blog_categories / blog_posts (status, published_at, seo) / blog_tags / blog_post_tag
- media (file_name randomized, mime, size, w/h, title/alt/caption, uploaded_by)

## Statuses (orders)
pending → paid → processing/ready_to_ship → (partially_shipped) → shipped → completed → (partially_refunded) → refunded; cancel allowed until shipped.
