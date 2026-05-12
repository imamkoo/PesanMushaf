# QA Admin UMUM vs Non-UMUM

Checklist ini dipakai setelah perubahan pemisahan tampilan admin untuk memastikan data UMUM tidak lagi tenggelam di view campuran.

## Tujuan

- Memastikan list `Registrations` punya jalur baca yang jelas untuk `UMUM` dan `Non-UMUM`.
- Memastikan `NIK` dan `Alamat` peserta `UMUM` tampil jelas pada layar yang relevan.
- Memastikan `Batches`, relasi peserta, dan dashboard tidak lagi menampilkan istilah teknis seperti `Global / Null`.

## Persiapan Data

Siapkan minimal data berikut:

- 1 registrasi `UMUM` dengan `NIK`, `Alamat`, dan `school_name`/instansi terisi.
- 1 registrasi `UMUM` lama dengan `NIK` atau `Alamat` kosong untuk mengecek placeholder warning.
- 1 registrasi `Non-UMUM` (`SD`, `SMP`, atau `SMA`).
- 1 batch `UMUM`.
- 1 batch `Non-UMUM`.
- 1 batch `VIP Global` (`education_level = null`) untuk memastikan label ini tidak terbaca sebagai `UMUM`.

## Checklist Registrations

1. Buka `Registrations` di admin.
2. Pastikan tab `Semua`, `UMUM`, dan `Non-UMUM` muncul.
3. Pastikan label `Edisi` tidak lagi muncul; admin harus melihat istilah `Kategori`.
4. Pastikan `Kode Booking` tampil di bagian depan tabel dan mudah terlihat tanpa scroll jauh ke samping.
5. Pastikan kolom default yang terlihat berfokus pada info operasional seperti `Kode Booking`, `Status`, `Batch`, `Halaman`, `Kategori`, dan `Jenjang`.
6. Buka tab `UMUM`.
7. Pastikan peserta `Non-UMUM` tidak muncul di tab `UMUM`.
8. Jika kolom sekunder seperti `WhatsApp`, `Sekolah / Kampus / Instansi`, `NIK`, atau `Alamat` disembunyikan default, pastikan masih bisa diakses dari menu toggle kolom.
9. Buka salah satu registrasi `UMUM`.
10. Pastikan section paling atas adalah ringkasan admin/operasional, bukan detail peserta.
11. Pastikan ada section khusus `Dokumen & Identitas UMUM`.
12. Jika data lama belum lengkap, pastikan tampil placeholder seperti `Belum diisi pada data lama`, bukan kosong tanpa konteks.

## Checklist Batches

1. Buka `Batches` di admin.
2. Pastikan tab `Batch UMUM`, `Batch Non-UMUM`, dan `VIP Global` muncul.
3. Pastikan label `Kategori` membedakan `UMUM`, `Non-UMUM`, dan `VIP Global`.
4. Buka detail salah satu batch `VIP Global`.
5. Pastikan batch ini tidak diberi label `UMUM`.
6. Buka relation manager peserta pada sebuah batch.
7. Pastikan tab `Semua`, `UMUM`, `Non-UMUM`, dan `Data Lama` tersedia.
8. Di relation manager, verifikasi `NIK` dan `Alamat` peserta `UMUM` terlihat jelas.

## Checklist Dashboard

1. Buka dashboard admin.
2. Pastikan ringkasan statistik menampilkan kartu terpisah untuk:
   - `Pendaftar UMUM`
   - `Pendaftar Non-UMUM`
   - `Batch UMUM`
   - `Batch Non-UMUM`
3. Pastikan chart kapasitas hanya punya bucket:
   - `SD`
   - `SMP`
   - `SMA`
   - `UMUM`
4. Pastikan batch `VIP Global` tidak lagi muncul sebagai bucket tersendiri di chart jenjang.
5. Pada widget kecamatan, pastikan kolom `UMUM` dan `Non-UMUM` terpisah.
6. Jika kolom `Data Lama` ditampilkan di widget kecamatan, pastikan hanya berisi entri lama yang memang belum terkategori.

## Verifikasi Cepat Otomatis

Jalankan test berikut dari folder `backend`:

```bash
php artisan test tests/Feature/Filament/RegistrationsStatsOverviewTest.php
php artisan test tests/Feature/Filament/AdminUmumSegmentationTest.php
```

Jika keduanya hijau dan checklist manual di atas lolos, pemisahan presentasi admin UMUM dianggap aman untuk diteruskan ke QA berikutnya.
