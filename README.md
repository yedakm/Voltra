# Voltra — Genset Rental ERP (SaaS)

Aplikasi SaaS sistem manajemen keuangan perusahaan sewa genset dengan depresiasi
aset metode **Garis Lurus / Straight Line**. Dibangun **Laravel 12 + Blade + Alpine.js
+ Tailwind**, database **MySQL dua schema** (`voltra` operasional & `voltra_akuntansi`).

## Prasyarat & Menjalankan

1. **MySQL** (mis. XAMPP) berjalan di `127.0.0.1:3306`. Buat dua database:
   ```sql
   CREATE DATABASE voltra;  CREATE DATABASE voltra_akuntansi;
   ```
2. Install & build:
   ```bash
   composer install
   npm install && npm run build
   php artisan migrate --seed     # 21 tabel + data awal
   php artisan serve              # http://127.0.0.1:8000
   ```
3. Depresiasi otomatis (Laravel Scheduler):
   ```bash
   php artisan schedule:work      # menjalankan voltra:depreciate tiap tgl 1
   php artisan voltra:depreciate --periode=2026-05   # jalankan manual
   ```

Kredensial demo: `andi@voltra.id` / `password` (perusahaan #1, role admin).
Tersedia 5 role: owner, admin, operator, teknisi, akuntan.

## Arsitektur Backend

| Lapisan | Lokasi |
|---|---|
| Koneksi 2 schema MySQL | `config/database.php` (`voltra`, `voltra_akuntansi`) |
| Migration 21 tabel | `database/migrations/2026_05_21_0000*` |
| 21 Eloquent model | `app/Models/` |
| Seeder data awal | `database/seeders/VoltraSeeder.php` |
| Auth (session) + RBAC | `AuthController`, middleware `role` |
| Service akuntansi | `app/Services/` — Journal, Depreciation, PeriodClosing |
| Otomatisasi depresiasi | `app/Console/Commands/RunDepreciation.php` + `routes/console.php` |
| REST API (Sanctum) | `routes/api.php` + `app/Http/Controllers/Api/` |

## REST API (token Bearer — Laravel Sanctum)

```
POST /api/login                 { email, password, id_perusahaan } → token
GET  /api/me                    profil pengguna
GET  /api/{resource}            genset, transaksi-sewa, jurnal, periode, dll (tenant-scoped)
POST /api/rental                buat sewa + invoice + auto-jurnal Pendapatan/Piutang
POST /api/payment               catat pembayaran + auto-jurnal Kas/Piutang
POST /api/asset-purchase        beli genset + auto-jurnal Pembelian Aset
POST /api/asset-disposal        lepas aset + auto-jurnal Laba/Rugi
POST /api/depreciation/run      jalankan depresiasi Garis Lurus
GET  /api/period/{id}/validate  validasi kelengkapan tutup buku
POST /api/period/{id}/close     tutup buku  (RBAC: akuntan/owner)
GET  /api/reports/{type}        laba-rugi | neraca | arus-kas
```

## Frontend

17 halaman + Login (lihat `resources/views/pages/`). View membaca data dari database
lewat `App\Support\VoltraData::all()` yang sudah di-scope per tenant. Drawer, tab,
wizard, dan toast berjalan klien-side via Alpine.js.
