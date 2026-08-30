# Deployment

## Ringkasan

Laravel 12 standar + Vite build. Frontend statis dari `public/build`; upload di `storage/app/public` (symlink `public/storage`).

## Checklist rilis

```sh
# 1. maintenance
php artisan down

# 2. kode + dependensi
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 3. migrasi (additive — data aman)
php artisan migrate --force

# 4. cache produksi
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 5. storage
php artisan storage:link 2>/dev/null || true

# 6. up
php artisan up
```

## Queue & email

Notifikasi pesanan mengantre (`ShouldQueue`). Set `QUEUE_CONNECTION=database`, jalankan worker + supervisor:

```sh
php artisan queue:work --tries=3 --backoff=10
```

Mail: set `MAIL_MAILER=smtp` + kredensial. Test kirim via reset password.

## Cron

Hanya `schedule:run` standar Laravel (belum ada job terjadwal wajib):

```
* * * * * php /path/artisan schedule:run
```

## Keamanan produksi

- `APP_DEBUG=false`, `APP_ENV=production`
- HTTPS wajib (HSTS di server)
- `php artisan cache:clear` setelah deploy besar (payload cache berformat array/JSON — aman di-serialize lintas deploy)
- Backup harian DB + `storage/app/public`
- Webhook Midtrans hanya via HTTPS; server key hanya di env

## Cache yang perlu dipahami

| Key | Isi | Invalidasi |
|-----|-----|------------|
| `shop.settings` | key-value settings | otomatis saat Settings::set |
| `shop.theme.vars` | token tema aktif | otomatis saat tema disimpan |
| `flash_sales.price_map` | harga flash aktif | otomatis saat sale disimpan; TTL 30 dtk |
| `menus.header` / `menus.footer` | item menu resolved | otomatis saat menu disimpan |
| `nav.root_categories` | kategori utama | observer Category; TTL 5 mnt |
| `seo.sitemap` | XML sitemap | TTL 1 jam |
| `seo.redirects` | tabel redirect | otomatis saat redirect disimpan |
