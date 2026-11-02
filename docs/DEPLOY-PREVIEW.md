# Deploy Preview — PesanMushaf (tanpa domain, tanpa VPS Litera)

Dokumen ini menyiapkan hosting **preview/produksi awal** untuk Save Golden Age tanpa memakai
VPS Litera dan tanpa domain kustom.

## 1) Jawaban singkat: bisakah semuanya di Vercel?

**Tidak.** Vercel hanya bisa menjalankan frontend React/Vite. Backend Laravel 13 di repo ini
**tidak bisa** dijalankan native di Vercel:

- Runtime resmi Vercel (per 2026): Node.js, Bun, Python, Rust, Go, Ruby, Wasm, Edge, Container.
  **Tidak ada PHP.**
- Laravel butuh proses PHP persisten, `artisan migrate`, session/cache DB, dan filesystem
  `storage/`. Vercel Functions bersifat request-scoped dan filesystem-nya read-only (kecuali `/tmp`).
- Opsi memaksa PHP lewat community runtime / container runtime bisa jalan, tapi bukan jalur
  produksi yang didukung dan rawan cold start + tidak ada queue worker.

**Database:** Vercel Postgres sudah **tidak tersedia** (dihentikan Desember 2024, proyek lama
dipindah ke Neon). Vercel hanya menyediakan integrasi Marketplace. Jadi database tetap dari
provider terpisah.

## 2) Arsitektur preview yang dipakai

| Bagian | Layanan | Biaya | Domain gratis |
|---|---|---|---|
| Frontend (React/Vite/PWA) | **Vercel Hobby** | Rp0 | `<project>.vercel.app` |
| Backend (Laravel + Filament) | **Render Free** (Docker) | Rp0 | `<service>.onrender.com` |
| Database | **Neon Free** (Postgres) | Rp0 | — |

Alternatif backend bila Render Free tidak lagi mencukupi: **Railway** (Free Trial $5/30 hari,
atau Free plan $1 kredit/bulan — cek dashboard saat daftar). Image Docker yang sama dipakai.

> Catatan free tier (diverifikasi 2026-11-02): Neon Free bersifat permanen — 1 GB storage per
> project, 100 CU-hours/project, compute scale-to-zero setelah 5 menit idle. Render Free web
> service tidur setelah idle (cold start puluhan detik) — cocok untuk preview, bukan traffic
> produksi ramai.

## 3) Langkah deploy

### 3.1 Database (Neon)

1. Buat project di <https://neon.com>, pilih region terdekat (Singapore/AWS ap-southeast-1).
2. Salin **pooled connection string** (`postgresql://USER:PASS@...neon.tech/DB?sslmode=require`).
3. Nilai untuk backend:
   - `DB_CONNECTION=pgsql`
   - `DB_URL=<connection string>`
   - `DB_SSLMODE=require`

### 3.2 APP_KEY

Render tidak bisa menjalankan `artisan key:generate` interaktif. Generate lokal:

```bash
echo "base64:$(openssl rand -base64 32)"
```

Simpan hasilnya sebagai env `APP_KEY` (jangan commit).

### 3.3 Backend (Render, Docker)

1. Push repo ke GitHub (`imamkoo/save-golden-age`).
2. Render → **New** → **Blueprint** → pilih repo. Render membaca `render.yaml` di root repo.
3. Isi env yang `sync: false`:
   - `APP_KEY` — dari langkah 3.2
   - `APP_URL` — `https://<service>.onrender.com` (isi setelah URL diberikan Render)
   - `DB_URL` — connection string Neon
   - `CORS_ALLOWED_ORIGINS` — URL Vercel, mis. `https://<project>.vercel.app`
   - `MIDTRANS_*` — isi bila alur pembayaran mau diuji (boleh kosong untuk uji form saja)
4. Deploy. Entrypoint container otomatis menjalankan `php artisan migrate --force`,
   `config:cache`, `view:cache`, lalu Apache.
5. Verifikasi: `GET https://<service>.onrender.com/up` → 200.

Build lokal untuk uji image (butuh Docker):

```bash
docker build -t pesanmushaf-api ./backend
docker run --rm -p 8080:80 --env-file backend/.env pesanmushaf-api
```

### 3.4 Frontend (Vercel)

1. Vercel → **Add New** → **Project** → import repo.
2. **Root Directory:** `frontend`. Framework terdeteksi Vite (`vercel.json` sudah menangani
   SPA rewrite dan cache asset).
3. Environment Variable:
   - `VITE_API_URL=https://<service>.onrender.com/api`
4. Deploy. Vercel memberi `https://<project>.vercel.app`.
5. Balik ke Render, set `CORS_ALLOWED_ORIGINS=https://<project>.vercel.app`, lalu redeploy
   backend agar CORS berlaku.

### 3.5 Midtrans (bila dipakai)

- Notification URL: `https://<service>.onrender.com/api/midtrans/notification`
- Sandbox: `MIDTRANS_IS_PRODUCTION=false` + server/client key sandbox.
- `APP_URL` harus URL backend publik, bukan localhost.

## 4) Checklist smoke test setelah deploy

- [ ] `GET /up` → 200
- [ ] `GET /api/districts` → daftar kecamatan
- [ ] `GET /api/price-categories` → reguler & vip
- [ ] Form booking: pilih **Sekolah** → jenjang SD/SMP/SMA + direktori sekolah muncul
- [ ] Form booking: pilih **Masjid** → field berubah jadi "Nama Masjid", NIK + alamat wajib
- [ ] Submit pendaftaran masjid → batch baru bernama `Mushaf Reguler MASJID <Kecamatan> <n>`
- [ ] Submit pendaftaran sekolah di kecamatan sama → batch berbeda
- [ ] Cek status pendaftaran via kode
- [ ] Login admin Filament (`/admin`) dengan akun seeder

## 5) Batasan & tindak lanjut

- Belum ada worker queue — saat ini tidak ada job yang di-queue (`QUEUE_CONNECTION=sync`).
- File upload bukti tulis belum diimplementasikan; `FILESYSTEM_DISK=public` memakai disk
  ephemeral Render. Bila fitur upload jadi, pindah ke object storage (S3/R2/Supabase Storage).
- Free tier bisa berubah kapan saja — verifikasi kuota di dashboard masing-masing provider
  sebelum mengumumkan ke pengguna.
- Jangan deploy apa pun ke VPS Litera.
