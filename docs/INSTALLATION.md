# Instalasi

## Prasyarat

- PHP >= 8.3 (ekstensi: pdo_mysql, mbstring, gd/imagick, intl, zip)
- Composer 2
- Node.js >= 20 + npm
- MySQL 8 / MariaDB 10.11+

## Langkah

```sh
composer install
cp .env.example .env
php artisan key:generate
```

Atur `.env` minimal:

```env
DB_DATABASE=cmstoko
DB_USERNAME=...
DB_PASSWORD=...

APP_URL=https://tokoanda.test

# Opsional
MIDTRANS_SERVER_KEY=...       # kosong = mode transfer manual
MIDTRANS_CLIENT_KEY=...
MIDTRANS_IS_PRODUCTION=false
RAJAONGKIR_API_KEY=...        # kosong = ongkir flat
SHOP_RETURN_WINDOW_DAYS=7
```

Lanjut:

```sh
php artisan migrate --seed
npm install
npm run build
php artisan storage:link
```

Seeder membuat: peran & izin, settings awal, gudang MAIN, grup pelanggan (Retail/VIP/Guest), katalog demo (13 produk, 3 configurable), homepage, halaman CMS, dan akun demo:

- Admin: `admin@tokokita.test` / `password`
- Pelanggan: `customer@tokokita.test` / `password`

## Verifikasi

```sh
php artisan test        # semua test harus hijau
npm run build
php artisan serve       # atau via Laragon/Nginx
```

Halaman smoke: `/`, `/produk`, `/produk/{slug}`, `/keranjang`, `/lacak`, `/masuk`, `/admin` (staff only).

## Webhook Midtrans

Endpoint publik: `POST /midtrans/webhook` (verifikasi signature + nominal). Set **Payment Notification URL** di dashboard Midtrans ke `https://domainanda/midtrans/webhook`. Redirect finish: `/midtrans/finish`.
