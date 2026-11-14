# Deploy JEJAKA500 ke cPanel (CloudHost / LiteSpeed)

Runbook langkah-demi-langkah memindahkan JEJAKA500 dari Vercel + Render ke satu
akun cPanel, dengan domain `jejaka500.id`.

| Komponen | Tujuan |
|---|---|
| `jejaka500.id` | SPA statis (hasil `vite build`) |
| `api.jejaka500.id` | Laravel 13 (API + panel admin Filament) |
| MySQL cPanel | Pengganti PostgreSQL Render |
| AutoSSL | TLS untuk kedua host |

Build dilakukan di GitHub Actions (`.github/workflows/release.yml`) karena tidak
ada PHP/Composer di mesin lokal dan hosting tidak punya Node. Hasilnya dua ZIP
siap-upload:

- `jejaka500-backend-<tag>.zip` — Laravel + `vendor/` + `public/build`
- `jejaka500-frontend-<tag>.zip` — isi `dist/` + `.htaccess`

> **Jangan menyalin `.env` ke repo.** Semua kredensial diisi langsung di server.

---

## 0. Prasyarat yang harus dicek di cPanel

Masuk cPanel → cek 5 hal ini **sebelum** mulai. Kalau salah satu gagal, deploy
tidak akan jalan.

| Cek | Di mana | Syarat |
|---|---|---|
| Versi PHP | **MultiPHP Manager** | `8.3` atau `8.4` (Laravel 13 butuh ≥ 8.3) |
| Ekstensi PHP | **Select PHP Version → Extensions** | `mbstring`, `intl`, `json`, `openssl`, `pdo_mysql`, `fileinfo`, `gd`/`exif` (opsional), `zip` |
| Terminal | **Terminal** (cari di kotak pencarian cPanel) | Ada = paling mudah. Tidak ada → pakai jalur Cron di Langkah 6 |
| Batas upload PHP | **MultiPHP INI Editor** | `upload_max_filesize ≥ 8M`, `post_max_size ≥ 10M`, `memory_limit ≥ 256M` |
| `open_basedir` | **MultiPHP INI Editor** (baris `open_basedir`) | Harus mengizinkan home directory, **bukan** hanya `public_html`. Kalau dibatasi ke docroot, Laravel tidak bisa baca `../vendor` dan `../storage` |

Kalau `open_basedir` dibatasi atau Terminal tidak ada, kirim tiket ke support
CloudHost:

> Mohon izinkan `open_basedir` mencakup seluruh home directory akun
> (`/home/USERNAME`) untuk domain jejaka500.id, dan aktifkan Terminal/SSH agar
> saya bisa menjalankan `php artisan`. Aplikasi Laravel perlu mengakses
> `vendor/`, `storage/`, dan `.env` di luar `public/`.

---

## 1. Beli domain + siapkan host

1. **cPanel → Domains → Create A New Domain** (atau beli lewat menu domain
   CloudHost) untuk `jejaka500.id`. Document root: `/home/USERNAME/jejaka500-web`.
2. **cPanel → Domains → Create A New Domain** untuk `api.jejaka500.id`,
   document root: `/home/USERNAME/jejaka500/public`.
   (Kalau hanya tersedia "Subdomains", pilih domain `jejaka500.id`, isi
   subdomain `api`, lalu ubah document root ke path di atas.)
3. **cPanel → SSL/TLS Status → Run AutoSSL** untuk kedua domain. Tunggu status
   menjadi *Active* sebelum lanjut.

Syarat registrar `.id`: KTP/e-KTP pemilik. Untuk `.co.id` baru perlu badan usaha
+ NIB.

---

## 2. Buat database MySQL

**cPanel → MySQL Databases**

1. Buat database: `USERNAME_jejaka500`.
2. Buat user: `USERNAME_jejaka` + password kuat (simpan di password manager).
3. **Add User To Database** → pilih database → centang **ALL PRIVILEGES**.

Catat: `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`. Host tetap `localhost`.

---

## 3. Build artefak

Di mesin lokal (repo `jejaka500`), pada commit yang mau dirilis:

```bash
git checkout main && git pull
git tag v1.0.0
git push origin v1.0.0
```

GitHub Actions akan menjalankan **Release (artefak cPanel)**. Setelah hijau:

- **GitHub → Releases → v1.0.0** → unduh `jejaka500-backend-v1.0.0.zip` dan
  `jejaka500-frontend-v1.0.0.zip`.
- Atau tab **Actions → run terakhir → Artifacts**.

Kalau perlu build tanpa tag: **Actions → Release (artefak cPanel) → Run
workflow**, isi `api_base_url` (`https://api.jejaka500.id/api`).

> Workflow ini memverifikasi artefak tidak mengandung `.env` dan build SPA tidak
> lagi menunjuk `127.0.0.1:8000`.

---

## 4. Upload backend

1. **cPanel → File Manager** → buka `/home/USERNAME/`.
2. **Upload** `jejaka500-backend-v1.0.0.zip`.
3. Klik kanan ZIP → **Extract**. Hasilnya folder `/home/USERNAME/jejaka500`
   (berisi `app/`, `public/`, `vendor/`, dst).
4. Hapus ZIP-nya setelah ekstrak.
5. Pastikan permission:
   - folder → `755`
   - file → `644`
   - `storage/` dan `bootstrap/cache/` → `775`

Kalau ekstrak lewat File Manager memutus di tengah (file `vendor/` banyak),
gunakan FTP (FileZilla) atau **Terminal**:

```bash
cd ~ && unzip jejaka500-backend-v1.0.0.zip
```

---

## 5. Isi `.env`

1. **File Manager → Settings → centang "Show Hidden Files"**.
2. Buka `jejaka500/deploy/cpanel/env.example`, salin isinya.
3. Buat file baru `/home/USERNAME/jejaka500/.env`, tempel, lalu isi:

```dotenv
APP_KEY=base64:ISI_HASIL_OPENSSL
APP_URL=https://api.jejaka500.id
DB_DATABASE=USERNAME_jejaka500
DB_USERNAME=USERNAME_jejaka
DB_PASSWORD=password_yang_dibuat_tadi
CORS_ALLOWED_ORIGINS=https://jejaka500.id,https://www.jejaka500.id
ADMIN_EMAIL=email-anda@contoh.com
ADMIN_PASSWORD=minimal-12-karakter
```

Generate `APP_KEY` di mesin lokal (tanpa PHP):

```bash
echo "base64:$(openssl rand -base64 32)"
```

4. Klik kanan `.env` → **Permissions** → `600`.

> `TRUST_CF_CONNECTING_IP=false` biarkan apa adanya selama belum pakai
> Cloudflare (lihat Langkah 9).

---

## 6. Jalankan migrasi & optimasi

### Jalur A — ada Terminal (disarankan)

**cPanel → Terminal**, lalu:

```bash
cd ~/jejaka500

# 1. Cek PHP yang dipakai
php -v

# 2. Bersihkan cache build CI
php artisan optimize:clear

# 3. Skema database
php artisan migrate --force

# 4. Data referensi (kecamatan, saran sekolah, universitas) + akun admin
php artisan db:seed --force

# 5. Symlink storage publik (foto batch/kecamatan)
php artisan storage:link

# 6. Cache produksi
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Kalau `php` tidak dikenali, cari path-nya: `which -a php` atau pakai path
MultiPHP terpilih, mis. `/opt/cpanel/ea-php84/root/usr/bin/php`.

### Jalur B — tanpa Terminal (cron sekali-jalan)

**cPanel → Cron Jobs** → *Add New Cron Job* → Common Settings
`Once Per Five Minutes (*/5 * * * *)`, command:

```bash
cd /home/USERNAME/jejaka500 && /usr/local/bin/php artisan migrate --force >> /home/USERNAME/migrate.log 2>&1
```

Tunggu 5 menit, buka `migrate.log` di File Manager. Kalau sudah muncul
`Nothing to migrate`/`DONE`, ganti command ke:

```bash
cd /home/USERNAME/jejaka500 && /usr/local/bin/php artisan db:seed --force >> /home/USERNAME/seed.log 2>&1
```

lalu:

```bash
cd /home/USERNAME/jejaka500 && /usr/local/bin/php artisan storage:link >> /home/USERNAME/link.log 2>&1
```

**Hapus cron job setelah selesai** — jangan dibiarkan hidup.

> `storage:link` butuh symlink. Bila hosting menonaktifkannya, buat symlink
> manual lewat File Manager: klik kanan `storage/app/public` → *Symlink* →
> target `public/storage` (atau minta support).

---

## 7. Upload frontend

1. Upload `jejaka500-frontend-v1.0.0.zip` ke `/home/USERNAME/jejaka500-web/`.
2. **Extract di dalam folder itu** (isi ZIP adalah `index.html`, `assets/`,
   `.htaccess` — bukan folder pembungkus).
3. Pastikan `.htaccess` ada di `/home/USERNAME/jejaka500-web/.htaccess`
   (aktifkan *Show Hidden Files*).

---

## 8. Verifikasi

```bash
# Kesehatan backend (Laravel health route)
curl -s -o /dev/null -w "%{http_code}\n" https://api.jejaka500.id/up

# Data referensi
curl -s https://api.jejaka500.id/api/price-categories | head -c 300

# Panel admin
curl -s -o /dev/null -w "%{http_code}\n" https://api.jejaka500.id/admin/login

# SPA + header keamanan
curl -sI https://jejaka500.id/ | grep -Ei "HTTP/|x-frame|content-security|referrer"

# Route SPA (harus 200, bukan 404)
curl -s -o /dev/null -w "%{http_code}\n" https://jejaka500.id/booking

# Aset yang tidak ada harus 404, bukan HTML
curl -s -o /dev/null -w "%{http_code}\n" https://jejaka500.id/assets/tidak-ada.js
```

Lalu jalankan suite QA dari mesin lokal:

```bash
cd jejaka500
QA_API_URL=https://api.jejaka500.id QA_WEB_URL=https://jejaka500.id \
  node qa/api-smoke.mjs

QA_WEB_URL=https://jejaka500.id node qa/kampung-optional-institution.mjs
```

Ekspektasi: `/up` = 200, `/admin/login` = 200, route SPA = 200, aset hilang =
404, suite QA lulus tanpa error konsol.

---

## 9. Cloudflare (opsional, disarankan)

Tanpa Cloudflare, `TRUST_CF_CONNECTING_IP=false` sudah benar dan rate limit
memakai IP asli klien.

Kalau memasang Cloudflare (proxy ON) untuk `jejaka500.id` + `api.jejaka500.id`:

1. Ubah nameserver domain ke Cloudflare.
2. SSL/TLS mode: **Full (strict)**.
3. Set `TRUST_CF_CONNECTING_IP=true` di `.env`, lalu
   `php artisan config:cache`.
4. **Wajib**: jangan biarkan IP origin bisa diakses langsung — header
   `CF-Connecting-IP` bisa dipalsukan bila penyerang menembak IP hosting. Kalau
   tidak bisa menutup origin, biarkan `TRUST_CF_CONNECTING_IP=false`.

---

## 10. Cut-over & rollback

Urutan aman:

1. Deploy ke subdomain `api` dan domain baru **selagi** Render + Vercel masih
   hidup.
2. Verifikasi Langkah 8 sampai hijau.
3. Baru arahkan trafik publik ke cPanel dan matikan service Render/Vercel.
4. Simpan Render/Vercel **minimal 7 hari** sebagai jalan mundur.

Rollback: kembalikan DNS ke Vercel/Render (atau aktifkan lagi layanan yang
dimatikan). Database MySQL tidak hilang; data baru yang masuk selama di cPanel
tidak otomatis pindah ke Render — catat selisihnya bila rollback dilakukan
setelah ada pendaftaran.

---

## 11. Troubleshooting

| Gejala | Penyebab umum | Perbaikan |
|---|---|---|
| 500 di semua halaman | `.env` tidak ada / `APP_KEY` kosong / permission storage | Cek `storage/logs/laravel.log`, isi `APP_KEY`, `chmod -R 775 storage bootstrap/cache` |
| 500 dengan pesan `open_basedir` | Batasan PHP | Lihat Langkah 0 |
| 404 di semua route SPA | `.htaccess` frontend tidak terupload | Upload ulang `dist/.htaccess` |
| 404 `/admin` | Document root mengarah ke folder app, bukan `public/` | Set docroot ke `/home/USERNAME/jejaka500/public` |
| Gambar upload 404 | `storage:link` belum jalan | Jalankan Langkah 6 nomor 5 |
| `could not find driver` | `pdo_mysql` tidak aktif | Aktifkan di Select PHP Version |
| 419 saat simpan di admin | `SESSION_SECURE_COOKIE=true` tapi diakses via HTTP | Pakai HTTPS atau set `false` sementara |
| CORS error di browser | `CORS_ALLOWED_ORIGINS` belum memuat domain | Isi `https://jejaka500.id`, `config:cache` ulang |
| 429 terlalu cepat | Cloudflare aktif tapi flag salah | `TRUST_CF_CONNECTING_IP=true` + `config:cache` |
| Upload foto gagal | Batas PHP | Naikkan `upload_max_filesize`/`post_max_size` |
| Perubahan `.env` tidak berefek | Config ter-cache | `php artisan config:cache` |
| 404 di `POST /api/register` setelah optimasi | `route:cache` tidak cocok dengan closure route bootstrap | `php artisan route:clear`; jangan pakai `route:cache` (pakai `config:cache` + `view:cache` saja) |

Setelah mengubah `.env` di produksi **selalu** jalankan:

```bash
cd ~/jejaka500 && php artisan config:cache && php artisan route:cache
```

---

## 12. Checklist pasca-deploy

- [ ] `/up` = 200, `/api/price-categories` mengembalikan data
- [ ] `/admin/login` bisa dibuka, login admin berhasil
- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] `.env` permission `600`, tidak bisa diakses via browser
- [ ] `php artisan migrate --force` idempotent (jalankan sekali lagi → tidak ada error)
- [ ] AutoSSL aktif di kedua host, HTTP redirect ke HTTPS
- [ ] Upload foto dari admin tampil di frontend (uji `storage:link`)
- [ ] Pendaftaran uji berhasil membuat kode batch yang benar
- [ ] Cron sekali-jalan (jalur B) sudah dihapus
- [ ] Backup database diunduh (**cPanel → Backup** atau phpMyAdmin → Export)
- [ ] Render/Vercel tetap hidup 7 hari sebagai rollback

---

## Lampiran A — Data lama dari Render

Database MySQL baru dimulai kosong. Yang dibuat otomatis:

- `price_categories` — dari migrasi
- kecamatan, saran sekolah, universitas — dari `db:seed`
- akun admin — dari `ADMIN_EMAIL`/`ADMIN_PASSWORD`

Yang **tidak** pindah otomatis: `registrations`, `payments`, `batches`, foto
batch. Pada saat runbook ini ditulis isi produksi hanya data QA, jadi mulai
bersih dapat diterima. Bila suatu saat perlu memindahkan data nyata:

1. Ekspor dari PostgreSQL Render (`pg_dump`/CSV) — butuh kredensial DB eksternal.
2. Impor ke MySQL lewat phpMyAdmin (CSV/SQL), urutan tabel: `districts` →
   `mosques`/`schools` → `batches` → `registrations` → `payments`.
3. Reset sequence `id` bila impor memakai ID eksplisit.

Lakukan hanya saat aplikasi sedang tidak menerima pendaftaran.

---

## Lampiran B — Ringkasan perintah

```bash
cd ~/jejaka500
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
