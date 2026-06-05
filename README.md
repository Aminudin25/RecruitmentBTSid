# Product Management API

REST API untuk manajemen produk — dibangun dengan **Laravel 11**, **PostgreSQL**, **Redis**, dan **JWT Authentication**.

---

## Tech Stack

| Layer         | Technology                     |
|---------------|-------------------------------|
| Framework     | PHP 8.2 + Laravel 11          |
| Database      | PostgreSQL 16                 |
| Cache         | Redis 7                       |
| Auth          | JWT (`tymon/jwt-auth`)        |
| Docs          | Swagger UI (`l5-swagger`)     |
| Container     | Docker + Docker Compose       |
| Web Server    | Nginx 1.25                    |

---

## Cara Menjalankan (Docker)

### Prasyarat
- [Docker](https://docs.docker.com/get-docker/) & [Docker Compose](https://docs.docker.com/compose/install/)

### 1. Clone & setup

```bash
git clone <YOUR_REPO_URL> product-api
cd product-api

cp .env.example .env
```

### 2. Jalankan dengan Docker Compose

```bash
docker compose up --build -d
```

Proses build pertama ±2–3 menit. Setelah selesai:
- **API**     → http://localhost:8000
- **Swagger** → http://localhost:8000/api/documentation

### 3. Verifikasi

```bash
# Cek semua container running
docker compose ps

# Lihat logs
docker compose logs -f app
```

> Entrypoint otomatis menjalankan: `key:generate`, `jwt:secret`, `migrate`, `db:seed`, `l5-swagger:generate`

### Default Credentials (dari seeder)

| Field    | Value         |
|----------|--------------|
| username | `admin`       |
| password | `password123` |

---

## API Endpoints

### Authentication — `/api/auth`

| Method | Endpoint              | Auth | Rate Limit     | Deskripsi             |
|--------|-----------------------|------|----------------|-----------------------|
| POST   | `/api/auth/register`  | ✗    | 3x / 60 detik  | Daftar akun baru      |
| POST   | `/api/auth/login`     | ✗    | 3x / 60 detik  | Login → dapat token   |
| POST   | `/api/auth/refresh`   | ✓    | —              | Refresh access token  |
| POST   | `/api/auth/logout`    | ✓    | —              | Logout (invalidate)   |
| GET    | `/api/auth/me`        | ✓    | —              | Info user aktif       |

### Products — `/api/products`

| Method | Endpoint               | Auth | Rate Limit    | Deskripsi              |
|--------|------------------------|------|---------------|------------------------|
| GET    | `/api/products`        | ✗    | —             | Semua produk + filter  |
| GET    | `/api/products/:id`    | ✗    | —             | Detail produk          |
| POST   | `/api/products`        | ✓    | 1x / 5 detik  | Tambah produk baru     |
| PUT    | `/api/products/:id`    | ✓    | 1x / 5 detik  | Update produk          |
| DELETE | `/api/products/:id`    | ✓    | 1x / 5 detik  | Hapus produk           |

### Query Parameters — GET `/api/products`

| Parameter  | Contoh            | Deskripsi                   |
|------------|-------------------|-----------------------------|
| `search`   | `?search=shirt`   | Cari berdasarkan nama produk|
| `category` | `?category=Clothes` | Filter by kategori        |
| `limit`    | `?limit=10`       | Jumlah item per halaman     |
| `page`     | `?page=2`         | Halaman ke-N                |

---

## Contoh Request

### Register
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"username":"jhon_doe","password":"supersecret","password_confirmation":"supersecret"}'
```

### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password123"}'
```
Response:
```json
{
  "success": true,
  "data": {
    "authentication_token": "eyJ0eXAiOiJKV1QiLCJhbGci...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGci...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

### Buat Produk (perlu token)
```bash
curl -X POST http://localhost:8000/api/products \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Awesome T-Shirt",
    "price": 99.99,
    "description": "High-quality cotton t-shirt",
    "category": "Clothes",
    "images": ["https://placeimg.com/640/480/any"]
  }'
```

---

## Response Format

### Sukses
```json
{
  "success": true,
  "message": "...",
  "data": { ... }
}
```

### Error Validasi (400)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "title": ["The title field is required."]
  }
}
```

### Not Found (404)
```json
{
  "success": false,
  "message": "Product with ID 999 not found"
}
```

### Rate Limited (429)
```json
{
  "success": false,
  "message": "Too many requests. Please try again later.",
  "retry_after": "5 seconds"
}
```

---

## Fitur

- ✅ CRUD Produk lengkap
- ✅ JWT Authentication (access token + refresh token)
- ✅ Search & filter by category
- ✅ Pagination
- ✅ Redis cache (GET endpoints, TTL 5 menit, auto-invalidate saat data berubah)
- ✅ Rate limiting (1x/5s untuk mutasi, 3x/60s untuk auth)
- ✅ CORS (allow all origins)
- ✅ Swagger UI documentation
- ✅ Docker Compose (app + nginx + postgres + redis)
- ✅ Postman Collection

---

## API Documentation

Setelah container berjalan, buka: **http://localhost:8000/api/documentation**

Atau import `postman_collection.json` ke Postman — token otomatis tersimpan setelah login.

---

## Stop / Reset

```bash
# Stop containers
docker compose down

# Stop + hapus volume (reset database)
docker compose down -v
```
