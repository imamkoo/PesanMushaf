# Logistics QA Stress Test

Panduan ini dipakai untuk memverifikasi bahwa:

- status `Batch Penuh` konsisten di dashboard, list batch, dan API;
- stress test dapat membentuk minimal dua batch penuh secara deterministik;
- data uji tetap aman karena command dibatasi untuk environment `local` / `testing` secara default.

## Prasyarat

Pastikan data master berikut sudah tersedia:

- `districts`
- `school_suggestions`
- `universities`
- `price_categories` aktif untuk `reguler` dan `vip`

Command akan berhenti bila katalog sekolah atau universitas masih kosong.

## Command utama

Jalankan dari folder backend:

```bash
php artisan app:master-stress-test 1206
```

`1206` adalah angka minimum untuk membentuk **2 batch penuh** dalam satu run:

- `603` pendaftaran pertama dipaksa ke surge `VIP global`
- `603` pendaftaran berikutnya dipaksa ke surge `Reguler target`

Kalau ingin run yang lebih mendekati kondisi ramai sekaligus tetap memberi contoh dua batch penuh, pakai:

```bash
php artisan app:master-stress-test 1500
```

atau

```bash
php artisan app:master-stress-test 2000
```

## Keamanan data

Secara default command hanya boleh dijalankan di `local` atau `testing`.

Kalau dijalankan di environment lain, command akan gagal kecuali diberi opsi:

```bash
php artisan app:master-stress-test 1206 --allow-live-data
```

Opsi itu hanya untuk keadaan darurat dan tidak disarankan untuk data operasional.

Selain itu, payload stress selalu mengirim:

```text
exclude_from_school_suggestions = true
```

supaya data dummy tidak mengotori autocomplete publik bila sumber katalog berubah di masa depan.

## Output yang harus dicek di terminal

Setelah command selesai, perhatikan bagian ini:

- `Batch Penuh (live)`
- `Flag is_full stale`
- audit batch VIP global
- audit batch Reguler di kecamatan target

Kriteria lolos minimum:

- `Batch Penuh (live)` minimal `2`
- `Flag is_full stale` bernilai `0`
- ada minimal satu batch VIP yang statusnya `[PENUH]`
- ada minimal satu batch Reguler target yang statusnya `[PENUH]`

## Verifikasi manual di Filament

1. Buka dashboard admin.
2. Cocokkan angka `Batch Penuh` dengan hasil terminal.
3. Buka menu `Batches`.
4. Aktifkan filter `Status Kuota = Sudah penuh`.
5. Verifikasi ada minimal 2 baris batch penuh.
6. Pastikan kolom `Terisi / Kapasitas` menunjukkan `603 / 603`.
7. Pastikan ikon `Penuh` terkunci pada baris yang sama.
8. Buka detail batch dan cek:
   - `Progress Terisi` = `603 / 603`
   - `Status Kapasitas` = `SUDAH PENUH (Siap Cetak)`

## Skenario manual yang direkomendasikan

### A. Minimal 2 batch penuh

```bash
php artisan app:master-stress-test 1206
```

Target hasil:

- 1 batch VIP penuh
- 1 batch Reguler target penuh

### B. Simulasi lebih dekat ke realita logistik

```bash
php artisan app:master-stress-test 2000
```

Target hasil:

- tetap ada minimal 2 batch penuh
- distribusi district / education / edition mulai terlihat untuk audit logistik
- ada campuran batch yang penuh dan masih terbuka

## Checklist QA akhir

- Dashboard `Batch Penuh` sama dengan jumlah batch penuh live.
- Filter `Sudah penuh` di list batch menampilkan baris yang benar.
- Tidak ada batch yang terlihat `603 / 603` tapi ikon `Penuh` terbuka.
- Tidak ada batch yang ikon `Penuh` terkunci tapi isi aktual masih di bawah kapasitas.
- Audit terminal menunjukkan `Flag is_full stale = 0`.
- Data stress hanya dijalankan di database lokal / testing.
