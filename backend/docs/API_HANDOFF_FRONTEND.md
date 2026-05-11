# API Handoff - Hut500

## 1) Base Info

- Base URL (local): `http://127.0.0.1:8000`
- Prefix API: `/api`
- Auth header wajib: `X-API-KEY: <api_key>`
- Accept: `application/json`
- CORS origin diatur lewat env: `CORS_ALLOWED_ORIGINS`
- Postman Collection: `postman/Hut500-API.postman_collection.json`

## 2) Endpoint Utama

- `GET /api/districts`
- `GET /api/districts/{district}`
- `GET /api/universities`
- `GET /api/universities/{university}`
- `GET /api/batches?district_id={id}&education_level={SD|SMP|SMA|UMUM}&only_available=1`
- `GET /api/batches/{batch}`
- `POST /api/register`
- `GET /api/registrations/{registrationCode}/status`

## 3) Request Register

Endpoint: `POST /api/register`

Request body:

```json
{
  "district_id": 1,
  "education_level": "SMP",
  "edition": "reguler",
  "name": "Ahmad Fauzi",
  "phone_number": "081234567890",
  "school_name": "SMPN 5 Jakarta",
  "email": "ahmad@example.com"
}
```

Field wajib:

- `district_id`
- `education_level` (`SD|SMP|SMA|UMUM`)
- `edition` (`reguler|vip`)
- `name`
- `phone_number`
- `school_name`

Field opsional:

- `email`

## 4) Response Pattern

Sukses umum:

```json
{
  "success": true,
  "message": "....",
  "data": {}
}
```

Error umum:

```json
{
  "success": false,
  "message": "...."
}
```

Validasi gagal (`422`):

```json
{
  "success": false,
  "message": "Data pendaftaran tidak valid.",
  "errors": {
    "field_name": ["..."]
  }
}
```

## 5) HTTP Status Code

- `200` OK (request GET sukses)
- `201` Created (`POST /api/register` sukses)
- `401` Unauthorized (API key tidak ada / tidak valid)
- `404` Not Found (registration code tidak ditemukan)
- `422` Unprocessable Entity (validasi gagal)
- `500` Internal Server Error

## 6) Alur Integrasi Frontend (Disarankan)

1. Load master data: `districts`, `universities`.
2. Saat user pilih kecamatan + jenjang, load `batches`.
3. Submit `register`.
4. Simpan `registration_code` dari response.
5. Cek status via `GET /api/registrations/{registrationCode}/status`.

## 7) Checklist Frontend sebelum Merge

- `X-API-KEY` selalu terkirim di semua request API.
- Error `401`, `422`, `404`, dan `500` sudah ditangani di UI.
- Validasi form client-side selaras dengan validasi backend.
- Mapping field response ke komponen UI sudah sesuai.
- Uji minimal: 1 happy path + 3 error path.
