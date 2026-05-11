# Deepnote Backend Setup

Gunakan panduan ini untuk menjalankan backend Laravel repo ini di Deepnote trial dengan PostgreSQL dan Midtrans sandbox.

## 1. Siapkan machine Deepnote

Di terminal Deepnote, pastikan command berikut tersedia:

```bash
php -v
composer --version
git --version
psql --version
```

Repo ini membutuhkan `PHP 8.3+`. Setelah repo terbuka di Deepnote, jalankan:

```bash
cd backend
chmod +x scripts/deepnote-preflight.sh scripts/deepnote-bootstrap.sh scripts/deepnote-serve.sh scripts/deepnote-smoke-check.sh scripts/deepnote-midtrans-sandbox-check.sh
./scripts/deepnote-preflight.sh
```

Kalau `deepnote-preflight.sh` gagal, lengkapi dulu dependency machine dan koneksi PostgreSQL sebelum lanjut.

## 2. Isi file `.env`

Salin template Deepnote:

```bash
cp .env.deepnote.example .env
```

Isi minimal nilai berikut:

```env
APP_URL=https://your-project-id.deepnoteproject.com

DB_CONNECTION=pgsql
DB_HOST=<postgres-host>
DB_PORT=5432
DB_DATABASE=<postgres-db>
DB_USERNAME=<postgres-user>
DB_PASSWORD=<postgres-password>

CORS_ALLOWED_ORIGINS=https://your-frontend-domain.com,https://your-preview.pages.dev
CORS_ALLOWED_ORIGINS_PATTERNS=

MIDTRANS_MERCHANT_ID=<sandbox-merchant-id>
MIDTRANS_CLIENT_KEY=<sandbox-client-key>
MIDTRANS_SERVER_KEY=<sandbox-server-key>
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SSL_VERIFY=true
```

Poin penting:

- `APP_URL` harus domain bawaan Deepnote yang benar.
- `MIDTRANS_IS_PRODUCTION=false` wajib agar tetap sandbox.
- Kalau frontend memakai preview domain yang berubah-ubah, isi `CORS_ALLOWED_ORIGINS_PATTERNS` dengan regex yang sesuai.

## 3. Bootstrap aplikasi

Setelah `.env` terisi:

```bash
./scripts/deepnote-bootstrap.sh
```

Script ini akan menjalankan:

- `composer install --no-dev --optimize-autoloader`
- `php artisan key:generate --force`
- `php artisan migrate --force`
- `php artisan storage:link`
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`

## 4. Jalankan port 8080

Deepnote incoming connections memakai port `8080`, jadi jalankan:

```bash
./scripts/deepnote-serve.sh
```

Lalu aktifkan incoming connections di Deepnote dan salin domain `*.deepnoteproject.com` yang diberikan.

## 5. Smoke check endpoint

Untuk menguji endpoint lokal di mesin Deepnote:

```bash
./scripts/deepnote-smoke-check.sh
```

Atau setelah domain Deepnote aktif:

```bash
./scripts/deepnote-smoke-check.sh "https://your-project-id.deepnoteproject.com"
```

Script ini memeriksa:

- `/up`
- `/api/districts`
- `/api/batches`
- `/api/price-categories`
- `/admin`

## 6. Midtrans tetap sandbox

Arahkan notification URL Midtrans sandbox ke:

```text
https://your-project-id.deepnoteproject.com/api/midtrans/notification
```

Endpoint Midtrans yang dipakai aplikasi:

- `POST /api/midtrans/snap-token`
- `POST /api/midtrans/sync-status`
- `POST /api/midtrans/notification`

Checklist verifikasi:

1. Snap token berhasil dibuat dari frontend.
2. Pembayaran test Midtrans memakai sandbox methods, bukan uang asli.
3. Notification masuk ke endpoint Deepnote.
4. `sync-status` mengubah status pendaftaran sesuai transaksi sandbox.

Untuk validasi cepat dari `.env`, jalankan:

```bash
./scripts/deepnote-midtrans-sandbox-check.sh
```

## 7. Catatan trial

- Setup ini cocok untuk trial/testing publik ringan.
- Backend tetap memakai domain bawaan Deepnote.
- Setelah alur valid, backend sebaiknya dipindah ke VPS normal untuk production final.
