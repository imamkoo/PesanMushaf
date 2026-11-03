# Snapshot katalog masjid DMI DKI

`dmi-mosques-dki.csv.gz` adalah snapshot satu kali dari halaman publik
<https://dmi.or.id/data-masjid/> (provinsi DMI `6` / DKI Jakarta), diambil
pada 3 November 2026.

- 9.344 baris, kolom: `dmi_code`, `name`, `address`, `village`,
  `district_name`, `city`, `province`, `latitude`, `longitude`.
- Nomor telepon **tidak** disertakan untuk mengurangi data pribadi.
- Kecamatan diisi dari data DMI; jika kosong, dicocokkan dari alamat dengan daftar kecamatan aplikasi (meningkatkan cakupan dari ~34% menjadi ~59%; hanya kecamatan di kota yang sama yang dipakai agar tidak salah kota).
- `dmi_code` dipakai sebagai kunci idempotensi.
- Migrasi `2026_11_03_000200_import_dmi_mosques_dki.php` memuat snapshot ini
  hanya saat `APP_ENV=production`, sekali saat migrasi pertama.
- Pembaruan berikutnya memakai `php artisan mosque:import --province=6`.
- Data dapat berubah di sumber aslinya; verifikasi admin tetap menjadi
  sumber kebenaran untuk katalog yang dipakai peserta.
