## 📝 Ringkasan Perubahan

<!-- 
Ringkasan singkat tentang perubahan di PR ini.
Fitur/Fix apa yang diselesaikan?
-->

- **Fitur/Fix:** 
- **Modul/Komponen yang disentuh (backend / frontend):** 

---

## 🔍 Checklist

Sebelum meminta review, pastikan poin berikut terpenuhi:

### Code Quality
- [ ] Backend: `./vendor/bin/pest` atau `php artisan test` pass
- [ ] Backend: `composer lint:check` (Pint) pass
- [ ] Frontend: `pnpm run lint` & `pnpm run build` pass
- [ ] Tidak ada hardcoded secrets/API keys/credentials

### Concurrency & Data Safety (Mushaf Booking)
- [ ] Operasi booking/claim halaman menggunakan lock / transaksi database atomik
- [ ] Webhook / signature payment gateway terverifikasi dengan benar

### Flow & Target Branch
- [ ] PR ini mentargetkan branch `develop` — bukan `main` langsung

---

## 🚨 Risk & Breaking Changes

- [ ] **Tidak ada risk** — perubahan UI/fix minor saja
- [ ] **Ada risk** (skema DB, payment gateway, batch allocation, concurrency):
  - **Risiko:** 
  - **Mitigasi:** 
