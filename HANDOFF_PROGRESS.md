# Handoff Progress — Hut500

Dokumen ini merangkum percakapan dan pekerjaan yang sudah dilakukan, supaya bisa dilanjutkan di device/akun Cursor lain tanpa kehilangan konteks.

**Terakhir diperbarui:** 12 Mei 2026  
**Branch lokal:** `main` (belum tentu semua perubahan sudah di-commit)

---

## Ringkasan singkat

Proyek **Hut500**: Laravel backend + React/Vite frontend, pendaftaran mushaf, Midtrans sandbox, admin Filament, Green API untuk WhatsApp demo.

Yang sudah jalan di sesi ini:

1. **WhatsApp (Green API)** — notifikasi registrasi berhasil + pembayaran berhasil (dengan anti-duplikasi).
2. **UX alamat (UMUM)** — review + checkbox konfirmasi sebelum submit; reminder di halaman selesai.
3. **Pelacakan kode** — lookup pakai kode penuh, kode belakang (suffix), atau nomor WhatsApp.
4. **Modal hasil pelacakan** — multi-hasil pakai kartu geser (Swiper), layout diperbaiki agar tidak ada kolom kosong besar.

---

## Status fitur

| Area | Status | Catatan |
|------|--------|---------|
| Registrasi + batch | Ada | Logic di `RegistrationService` |
| Midtrans sandbox | Ada | Kredensial di `.env` lokal user |
| WA registrasi berhasil | **Selesai + tes live** | Trigger setelah commit registrasi |
| WA pembayaran sukses | **Selesai (kode)** | Trigger webhook + sync-status; belum wajib tes live |
| WA pembayaran pending | Belum | Sengaja ditunda (freeze demo) |
| UX cek alamat UMUM | **Selesai** | `BookingPage`, `BookingFinishedPage` |
| Lookup suffix + phone | **Selesai** | Backend + frontend |
| Modal pelacakan (Swiper) | **Selesai** | `BookingDetailsPage` + CSS |
| Admin Filament polish | Sebelumnya | UMUM/VIP, registrations table, dll. |
| PWA frontend | Sebelumnya | `vite-plugin-pwa` |
| Commit / push | **Belum** | Banyak file masih modified/untracked |

---

## WhatsApp (Green API)

### Env (`backend/.env`)

```env
WHATSAPP_NOTIFICATIONS_ENABLED=true
WHATSAPP_REGISTRATION_SUCCESS_ENABLED=true
WHATSAPP_PAYMENT_SUCCESS_ENABLED=true
# Kosongkan = kirim ke nomor pendaftar. Isi 628... untuk demo aman ke HP sendiri.
WHATSAPP_TEST_TO=

GREEN_API_URL=https://xxxx.api.greenapi.com
GREEN_API_ID_INSTANCE=...
GREEN_API_TOKEN_INSTANCE=...
GREEN_API_TIMEOUT=20
GREEN_API_SSL_VERIFY=false   # dev macOS: hindari cURL error 77 CA bundle
```

**Penting:** Jangan commit `.env`. Duplikat blok Midtrans pernah ditambahkan oleh kesalahan — sudah dibersihkan; Midtrans asli tetap di bagian atas file (sekitar baris 31–35).

### File utama

| Peran | Path |
|-------|------|
| Config | `backend/config/whatsapp.php` |
| Client HTTP | `backend/app/Services/WhatsApp/GreenApiClient.php` |
| Pesan registrasi | `RegistrationSuccessMessageBuilder.php`, `RegistrationSuccessWhatsAppNotifier.php` |
| Pesan lunas | `PaymentSuccessMessageBuilder.php`, `PaymentSuccessWhatsAppNotifier.php` |
| Trigger registrasi | `backend/app/Services/RegistrationService.php` (`DB::afterCommit`) |
| Trigger lunas | `MidtransController::notification`, `MidtransRegistrationSyncService` |
| Dedupe lunas | Kolom `whatsapp_payment_notified_at` di model `Registration` |
| Command manual | `app:send-registration-whatsapp`, `app:send-payment-success-whatsapp` |

### Command tes

```bash
cd backend
php artisan app:send-registration-whatsapp "KODE_PENDAFTARAN"
php artisan app:send-payment-success-whatsapp "KODE_PENDAFTARAN"
# Paksa ke nomor lain:
php artisan app:send-payment-success-whatsapp "KODE" --to=628xxxxxxxxxx
```

### Test otomatis

```bash
php artisan test tests/Feature/WhatsAppRegistrationNotificationTest.php \
  tests/Feature/WhatsAppPaymentSuccessNotificationTest.php \
  tests/Feature/MidtransApiTest.php
```

### Batasan demo Green API

- Paket gratis: ~**3 chat unik/bulan** → untuk demo isi `WHATSAPP_TEST_TO` ke nomor sendiri.
- Instance harus tetap terhubung (scan QR di console Green API).

---

## Pelacakan kode (lookup)

### Perilaku backend

Endpoint: `GET /api/registrations/status?lookup=...` dan `GET /api/registrations/{code}/status`

Urutan pencocokan di `RegistrationController::lookupRegistrationStatus`:

1. **Kode penuh** (exact, tanpa spasi, uppercase)
2. **Nomor WhatsApp** (normalisasi `62…` via `IndonesianPhone`)
3. **Suffix** — segmen **terakhir** setelah `-` terakhir (mis. `XBV1` dari `...-DPR-XBV1`)

Jika suffix bentrok → mengembalikan **beberapa** baris (sama seperti lookup nomor).

### Frontend

- `frontend/src/pages/BookingDetailsPage.tsx` — form copy menjelaskan 3 cara input.
- Hasil **banyak** → Swiper + kartu per pendaftaran.
- Hasil **satu** → satu kartu tanpa carousel.

### Test backend

```bash
php artisan test tests/Feature/PublicRegistrationApiTest.php
```

---

## UX alamat (jenjang UMUM)

- `frontend/src/pages/BookingPage.tsx` — blok review alamat + checkbox wajib sebelum submit.
- `frontend/src/pages/BookingFinishedPage.tsx` — reminder cek alamat setelah daftar.

Backend: NIK + alamat wajib hanya untuk `education_level === 'UMUM'` (sudah dari sebelumnya).

---

## Modal pelacakan — perbaikan tampilan

**Masalah yang diperbaiki:** grid 2 kolom + header slide tidak `col-span` penuh → area putih kosong, panah swiper “melayang”.

**Solusi:** `StatusResultCard` jadi satu `<article>` utuh; isi detail + panel pembayaran di grid dalam kartu; style swiper di `frontend/src/index.css` (class `.hut-lookup-results-swiper`).

**Cek manual:**

1. Lookup nomor dengan 2+ pendaftaran → geser kartu, pagination, panah.
2. Lookup satu kode / suffix → satu kartu rapi.
3. Mobile: swipe + tidak scroll vertikal panjang berulang.

---

## Midtrans (ringkas)

- Sandbox: `MIDTRANS_IS_PRODUCTION=false`
- Kredensial di `.env` lokal (jangan commit)
- Flow: Snap token → webhook `settlement` → optional `sync-status`
- UI publik: `BookingFinishedPage`, `BookingDetailsPage`, `paymentStatusUi.ts`, `MidtransPayButton.tsx`

---

## Admin Filament (konteks sebelumnya)

- Segmentasi UMUM / non-UMUM, label **Kategori** (bukan Edisi), **VIP Global** untuk batch `education_level` null.
- Tabel registrations: kode booking di depan, kolom sekunder toggleable.
- Widget dashboard: SD, SMP, SMA, UMUM (tanpa bucket Global/Null di chart).
- QA doc: `backend/docs/ADMIN_UMUM_QA.md`

---

## Deploy / infra (konteks)

- Frontend: Cloudflare Pages (rencana)
- Backend: VPS Ubuntu + PHP 8.3 + PostgreSQL (pindah dari Deepnote)
- Queue: disarankan Redis + Laravel queue untuk WA production (belum diimplementasi)

---

## Known issues / catatan teknis

1. **`pnpm build` kadang hang** setelah `✓ built` — proses perlu di-stop manual; artefak `dist/` tetap terbentuk.
2. **Path folder:** workspace bisa `Hut500` vs `HUT500` di macOS — pakai path konsisten saat `pnpm --dir`.
3. **Suffix lookup** bisa tabrakan jika banyak registrasi (4 char random) — by design mengembalikan banyak hasil.
4. **`.env` jangan di-commit** — hanya `.env.example` yang di-update.

---

## File baru / penting (belum di-commit)

```
backend/config/whatsapp.php
backend/app/Services/WhatsApp/*
backend/app/Console/Commands/SendRegistrationWhatsApp.php
backend/app/Console/Commands/SendPaymentSuccessWhatsApp.php
backend/tests/Feature/WhatsApp*NotificationTest.php
frontend/src/lib/paymentStatusUi.ts   (dari sesi UI sebelumnya, jika ada)
```

Modified (cuplikan): `RegistrationController`, `MidtransController`, `RegistrationService`, `BookingPage`, `BookingDetailsPage`, `BookingFinishedPage`, `index.css`, `PublicRegistrationApiTest.php`, `.env.example`

---

## Saran langkah lanjut (prioritas)

1. **Freeze demo** — jangan tambah fitur besar dulu.
2. Isi `WHATSAPP_TEST_TO` untuk presentasi aman.
3. **QA manual end-to-end:** daftar → WA registrasi → bayar sandbox → WA lunas → lacak kode (suffix + phone).
4. **Commit** dengan pesan terpisah misalnya:
   - `feat(whatsapp): registration and payment success notifications via Green API`
   - `feat(api): support booking code suffix lookup`
   - `feat(ui): swipeable lookup results and UMUM address confirmation`
5. Production: pertimbangkan queue + log pengiriman WA; evaluasi WABA vs Green API.

---

## Perintah dev cepat

```bash
# Backend
cd backend
php artisan serve
php artisan test

# Frontend
cd frontend
pnpm dev
pnpm lint
pnpm build
```

---

## Transkrip percakapan lengkap

Agent transcript (untuk detail percakapan):  
`/Users/macbookair/.cursor/projects/Users-macbookair-Desktop-CODE-Hut500/agent-transcripts/2a3a7e20-f791-439a-90b2-833cca2d3270/2a3a7e20-f791-439a-90b2-833cca2d3270.jsonl`

---

## Prompt singkat untuk melanjutkan di Cursor lain

Salin ke chat baru:

> Saya lanjut proyek Hut500. Baca `HANDOFF_PROGRESS.md` di root repo. Fokus: (1) pastikan WA pembayaran sukses sudah dites live, (2) QA modal lookup Swiper di mobile, (3) siapkan commit. Jangan ubah scope tanpa konfirmasi — mode freeze demo.

Sesuaikan poin (1)–(3) sesuai kebutuhan Anda.
