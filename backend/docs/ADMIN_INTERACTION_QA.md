# Admin Interaction QA

Panduan ini dipakai untuk memverifikasi bahwa route backend pada
`localhost:8000` tidak lagi masuk ke kondisi beku setelah dropdown, sheet,
atau state browser tertentu meninggalkan body lock.

## Fokus masalah

Gejala yang ingin dicegah:

- klik kiri tidak berfungsi,
- klik kanan tidak berfungsi,
- scroll tidak berjalan,
- refresh biasa (`Cmd+R`) terasa ikut macet pada tab yang sama.

## Area yang perlu diuji

- `/`
- `/admin`
- route backend lain yang memakai Inertia layout admin

## Skenario utama

### 1. Initial load

1. Buka `http://localhost:8000/`.
2. Pastikan halaman bisa di-scroll.
3. Pastikan klik kiri dan klik kanan normal.
4. Lakukan refresh biasa.
5. Pastikan tab tidak membeku.

### 2. Dashboard admin

1. Login ke admin lalu buka `/admin`.
2. Scroll dashboard sampai bawah.
3. Klik beberapa elemen interaktif di dashboard.
4. Pastikan interaksi tetap normal setelah polling widget berjalan.

### 3. User dropdown

1. Buka menu user/avatar.
2. Tutup kembali dropdown tanpa memilih menu.
3. Buka lagi lalu klik `Settings`.
4. Kembali ke dashboard.
5. Pastikan halaman tetap bisa klik dan scroll.

### 4. Sidebar mobile/offcanvas

1. Kecilkan viewport hingga mode mobile.
2. Buka sidebar mobile.
3. Tutup sidebar.
4. Buka lagi lalu navigasi ke route lain.
5. Pastikan body tidak terkunci setelah route berpindah.

### 5. Perbandingan browser

1. Uji di Chrome normal.
2. Uji di Chrome incognito.
3. Pastikan perilaku interaksi di kedua mode sudah konsisten.

## Cek DevTools bila freeze masih muncul

Jalankan di Console:

```js
getComputedStyle(document.body).pointerEvents
getComputedStyle(document.body).overflow
document.body.getAttribute('data-scroll-locked')
```

Hasil yang diharapkan setelah fix:

- `pointerEvents` bukan `none`
- `overflow` tidak tertinggal `hidden` ketika tidak ada dialog/sheet aktif
- `data-scroll-locked` kosong saat halaman idle
