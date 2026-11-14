# Serah Terima Deploy JEJAKA500 ke cPanel (untuk admin hosting)

> Dokumen ini untuk orang yang akan meng-upload aplikasi ke cPanel. Ringkas dan
> cukup untuk kerja sekali jalan. Detail + troubleshooting lengkap: `CPANEL-DEPLOY.md`.

## 1. Yang diserahkan

| File | Ukuran | Keterangan |
|---|---|---|
| `jejaka500-backend-<versi>.zip` | ±23 MB | Laravel lengkap: `vendor/`, `public/build`. **Sudah build, tidak perlu Composer/Node di server.** |
| `jejaka500-frontend-<versi>.zip` | ±2,2 MB | SPA statis: `index.html`, `assets/`, `.htaccess`. |
| `CPANEL-DEPLOY.md` | — | Runbook lengkap (jalur Terminal & jalur cron). |
| `deploy/cpanel/env.example` | — | Template `.env` — **sudah ikut di dalam ZIP backend** (`jejaka500/deploy/cpanel/env.example`). |

ZIP ada di **GitHub → Releases → tag v1.0.0** (repo `imamkoo/jejaka500`),
atau dikirim langsung oleh pemilik proyek.

## 2. Spesifikasi server yang wajib terpenuhi

| Item | Syarat | Cek di cPanel |
|---|---|---|
| Versi PHP | **8.3 atau 8.4** | MultiPHP Manager |
| Ekstensi PHP | `mbstring`, `intl`, `json`, `openssl`, `pdo_mysql`, `fileinfo`, `zip` (opsional: `gd`, `exif`) | Select PHP Version → Extensions |
| Limit PHP | `memory_limit ≥ 256M`, `upload_max_filesize ≥ 8M`, `post_max_size ≥ 10M` | MultiPHP INI Editor |
| `open_basedir` | Harus mencakup **home directory**, bukan hanya docroot (Laravel membaca `../vendor`, `../storage`, `.env`) | MultiPHP INI Editor — kalau dibatasi, tiket ke support CloudHost |
| Terminal/SSH | Opsional tapi sangat disarankan — tanpa itu migrasi via cron (lihat CPANEL-DEPLOY.md Langkah 6) | cPanel → Terminal |

## 3. Struktur folder tujuan

```
/home/<user>/
├── jejaka500/            ← ekstrak ZIP backend DI SINI
│   ├── .env              ← dibuat sendiri (lihat §4)
│   ├── public/           ← docroot untuk api.jejaka500.id
│   ├── vendor/
│   └── storage/app/public ← foto upload (JANGAN dihapus saat update)
└── jejaka500-web/        ← ekstrak isi ZIP frontend DI SINI (docroot jejaka500.id)
    ├── index.html
    ├── assets/
    └── .htaccess
```

- Document root `api.jejaka500.id` → `/home/<user>/jejaka500/public`
- Document root `jejaka500.id` → `/home/<user>/jejaka500-web`
- Setelah itu jalankan AutoSSL untuk kedua domain.

## 4. Mengisi `.env`

Salin `jejaka500/deploy/cpanel/env.example` ke `/home/<user>/jejaka500/.env` lalu isi:

**Dibuat oleh admin hosting (di cPanel):**
```dotenv
APP_KEY=base64:...        # jalankan: php artisan key:generate (atau openssl rand -base64 32)
DB_DATABASE=<user>_jejaka500
DB_USERNAME=<user>_jejaka
DB_PASSWORD=...           # dibuat di cPanel → MySQL Databases
```

**Diberikan pemilik proyek (lewat password manager, bukan chat):**
```dotenv
ADMIN_EMAIL=
ADMIN_PASSWORD=           # minimal 12 karakter
MIDTRANS_MERCHANT_ID=
MIDTRANS_CLIENT_KEY=
MIDTRANS_SERVER_KEY=
MIDTRANS_IS_PRODUCTION=false
GREEN_API_URL=
GREEN_API_ID_INSTANCE=
GREEN_API_TOKEN_INSTANCE=
```

**Tetap seperti di template:**
```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.jejaka500.id
CORS_ALLOWED_ORIGINS=https://jejaka500.id,https://www.jejaka500.id
TRUST_CF_CONNECTING_IP=false
DB_CONNECTION=mysql
```

Setelah `.env` diisi: permission file `600`.

## 5. Migrasi & optimasi (wajib, sekali jalan)

Di **cPanel → Terminal**:

```bash
cd ~/jejaka500
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
```

Tanpa Terminal: pakai **Cron Jobs** sekali-jalan (`*/5 * * * *`) dengan
perintah yang sama, log ke `~/migrate.log`, lalu **hapus cron-nya** setelah
selesai.

## 6. Larangan keras

1. **Jangan** letakkan `.env` di dalam `public/` — harus di `~/jejaka500/.env`.
2. **Jangan** hapus folder `jejaka500` atau `storage/app/public` saat update versi — cukup extract ZIP **di atas** folder lama.
3. **Jangan** mengubah `prisma/`, `laravel.log`, atau file apa pun yang bukan bagian dari ZIP.
4. **Jangan** mengaktifkan ulang `TRUST_CF_CONNECTING_IP=true` kecuali domain memakai Cloudflare.

## 7. Verifikasi setelah deploy

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://api.jejaka500.id/up          # 200
curl -s -o /dev/null -w "%{http_code}\n" https://api.jejaka500.id/admin/login # 200
curl -s -o /dev/null -w "%{http_code}\n" https://jejaka500.id/                # 200
curl -s -o /dev/null -w "%{http_code}\n" https://jejaka500.id/booking         # 200 (SPA route)
curl -s https://api.jejaka500.id/api/price-categories | head -c 150           # data JSON
```

Login admin di `https://api.jejaka500.id/admin` dengan `ADMIN_EMAIL`/`ADMIN_PASSWORD`.

## 8. Update versi berikutnya

### 8a. Cara otomatis (setelah SSH aktif) — direkomendasikan

Repo sudah punya workflow **`Deploy (cPanel via SSH)`** yang berjalan otomatis
setiap CI di `main` sukses: build backend + frontend → `rsync` ke server →
`php artisan migrate --force` + cache. Push ke `main` = website ter-update.

**Yang admin hosting perlu berikan ke pemilik proyek:**

| Data | Contoh |
|---|---|
| Host SSH | `103.63.24.171` atau `api.jejaka500.id` |
| Port SSH | `22` (kadang `2222`) |
| User cPanel | `imamkoo` |
| Path dokumen backend | `/home/imamkoo/jejaka500` |
| Path dokumen frontend | `/home/imamkoo/jejaka500-web` |
| Path PHP CLI | `/opt/cpanel/ea-php84/root/usr/bin/php` |
| Public key deploy | (dikirim pemilik proyek, admin menempelkannya di `~/.ssh/authorized_keys`) |
| Output `ssh-keyscan` | `ssh-keyscan -p 22 HOST` (untuk memverifikasi identitas server) |

Pemilik proyek lalu mengisi **Settings → Secrets and variables → Actions**:
variabel `CPANEL_HOST`, `CPANEL_USER`, `CPANEL_BACKEND_PATH`,
`CPANEL_FRONTEND_PATH`, `CPANEL_PHP_BIN`, dan secret `CPANEL_SSH_KEY`.

Sebelum dipakai, jalankan sekali secara **dry-run** (Actions → Deploy (cPanel via
SSH) → Run workflow → centang `dry_run`) untuk melihat file mana yang berubah
tanpa menyentuh server.

Setelah cut-over, workflow `deploy.yml` (Render/Vercel) bisa dimatikan/dihapus.

### 8b. Cara manual (selama SSH belum siap)

Pemilik proyek memberi ZIP baru dari Release terbaru; admin hosting mengulang:
upload + extract **di atas** folder lama → `php artisan migrate --force` →
`php artisan config:cache`. Tidak perlu mengulang `db:seed`/`storage:link`.

---

*Kontak pemilik proyek: Imam — via password manager untuk semua secret.*
