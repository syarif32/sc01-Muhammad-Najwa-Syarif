# Sistem Reservasi Ruang Meeting

Backend RESTful API dan antarmuka web berbasis Laravel Blade untuk pengelolaan ruang meeting dan reservasi. Proyek ini berfokus pada pemodelan data, konsistensi data, aturan bisnis, pencegahan *double-booking*, *authorization*, serta penanganan *concurrency*.

## Daftar Isi

1. [Status Pengerjaan](#1-status-pengerjaan)
2. [Fitur Berdasarkan Tingkatan](#2-fitur-berdasarkan-tingkatan)
3. [Prasyarat dan Cara Menjalankan](#3-prasyarat-dan-cara-menjalankan)
4. [Antarmuka Web](#4-antarmuka-web)
5. [Keputusan Desain](#5-keputusan-desain)
6. [Aturan dan Matriks Pengujian Overlap](#6-aturan-dan-matriks-pengujian-overlap)
7. [Penanganan Race Condition dan Concurrency](#7-penanganan-race-condition-dan-concurrency)
8. [Dokumentasi API](#8-dokumentasi-api)
9. [Batasan yang Disadari](#9-batasan-yang-disadari)
10. [Bagian yang Belum Selesai dan Roadmap](#10-bagian-yang-belum-selesai-dan-roadmap)

---

## 1. Status Pengerjaan

Studi kasus yang dikerjakan: **Sistem Reservasi Ruang Meeting**

Implementasi menggunakan:

| Komponen | Teknologi |
| :--- | :--- |
| **Backend** | Laravel |
| **Database** | MySQL / SQLite (Testing) |
| **ORM** | Eloquent |
| **Web UI** | Laravel Blade + TailwindCSS |
| **API** | RESTful API |
| **Testing** | PHPUnit / Pest |
| **Version Control** | Git |

Fokus utama implementasi adalah memastikan aturan bisnis *double-booking* tetap konsisten, termasuk ketika terdapat dua permintaan booking yang datang secara bersamaan.

---

## 2. Fitur Berdasarkan Tingkatan

### 2.1 Mandatory

| Fitur | Status | Keterangan |
| :--- | :---: | :--- |
| CRUD ruang meeting | ✅ Selesai | Nama, kapasitas, dan lokasi |
| Membuat booking | ✅ Selesai | Booking berdasarkan ruang dan rentang waktu |
| Pencegahan double-booking | ✅ Selesai | Overlap tidak diperbolehkan |
| Daftar booking per ruang | ✅ Selesai | Dapat difilter berdasarkan tanggal |
| Pembatalan booking | ✅ Selesai | Hanya pemilik booking yang dapat membatalkan |
| Identitas user sederhana | ✅ Selesai | Menggunakan `user_id` sebagai identitas |
| Validasi input | ✅ Selesai | Validasi ruang dan rentang waktu |

> Mandatory diselesaikan terlebih dahulu sebelum pengembangan fitur tambahan.

### 2.2 Nice to Have

| Fitur | Status | Keterangan |
| :--- | :---: | :--- |
| Pengujian seluruh bentuk overlap | ✅ Selesai | Mencakup *identical*, *nested*, *partial overlap*, dan *boundary* |
| Pengujian overlap tanpa HTTP | ✅ Selesai | Logika overlap dapat diuji pada level model |
| Penyimpanan waktu dalam UTC | ✅ Selesai | Konversi timezone dilakukan pada edge aplikasi |
| Error response konsisten | ✅ Selesai | `409 Conflict` untuk bentrok jadwal dan `422` untuk validation error |
| Pemisahan lapisan | ✅ Selesai | Logika overlap dapat diuji tanpa melalui HTTP |

### 2.3 Enhancement

| Fitur | Status | Keterangan |
| :--- | :---: | :--- |
| Race-condition safe booking | ✅ Selesai | Transaction + pessimistic locking |
| Concurrent booking test | ✅ Selesai | Memastikan hanya satu booking berhasil |
| Availability search | ✅ Selesai | Pencarian ruang berdasarkan waktu dan kapasitas |
| Booking audit trail | ✅ Selesai | Perubahan tertentu dicatat pada `booking_logs` |
| Recurring booking | ❌ Belum selesai | Masuk roadmap |
| Configurable operating hours | ❌ Belum selesai | Masuk roadmap |
| Configurable booking buffer | ❌ Belum selesai | Masuk roadmap |

> Fitur Enhancement dikerjakan setelah fitur Mandatory dan Nice to Have dianggap stabil.

---

## 3. Prasyarat dan Cara Menjalankan

### Prasyarat

- PHP 8.1 / 8.2 / 8.3
- Composer
- MySQL 8.0+
- Node.js dan NPM (jika diperlukan untuk asset frontend)

### Instalasi

1. Clone repository:

   ```bash
   git clone <repo-url>
   cd meeting-room-api
   ```

2. Install dependency:

   ```bash
   composer install
   ```

3. Salin konfigurasi environment:

   ```bash
   cp .env.example .env
   ```

4. Generate application key:

   ```bash
   php artisan key:generate
   ```

5. Atur konfigurasi database pada `.env`. Contoh:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=meeting_room
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. Jalankan migration:

   ```bash
   php artisan migrate
   ```

7. Jalankan aplikasi:

   ```bash
   php artisan serve
   ```

Aplikasi dapat diakses melalui: `http://localhost:8000`

### Menjalankan Automated Test

Seluruh test dapat dijalankan menggunakan:

```bash
php artisan test
```

Test mencakup business rule utama, validasi, authorization, timezone, overlap, dan concurrency.

---

## 4. Antarmuka Web

Antarmuka web dibuat menggunakan Laravel Blade dengan tampilan sederhana yang berfokus pada fungsi utama aplikasi. Dashboard utama menyediakan beberapa bagian:

- **Room Management** — Membuat ruang meeting, melihat daftar ruang, mengubah data ruang, dan menghapus ruang (Nama, Kapasitas, Lokasi).
- **Room Availability Search** — Pengguna dapat mencari ruang yang tersedia berdasarkan waktu mulai, waktu selesai, dan kapasitas minimum. Sistem akan mengecualikan ruangan yang memiliki booking yang overlap dengan rentang waktu tersebut.
- **Booking Management** — Pengguna dapat membuat booking, melihat daftar booking berdasarkan ruangan, memfilter booking berdasarkan tanggal, dan membatalkan booking miliknya (memerlukan `user_id` sebagai mekanisme identifikasi sederhana).

---

## 5. Keputusan Desain

### 5.1 Pessimistic Locking untuk Concurrency

**Keputusan:** Sistem menggunakan `DB::transaction()` bersama dengan `lockForUpdate()` pada resource ruang yang sedang dibooking.

**Alasan:** Pengecekan overlap saja tidak cukup untuk menghadapi race condition. Contohnya ketika Request A dan Request B sama-sama mengecek jadwal kosong secara bersamaan, tanpa locking kedua request berpotensi berhasil melakukan insert. Dengan melakukan locking pada row ruang di dalam transaction, request untuk ruang yang sama diproses secara terkontrol.

**Trade-off:** Relatif sederhana diterapkan pada Laravel, tidak membutuhkan database khusus, dan dapat diuji secara otomatis. Konsekuensinya, request booking untuk ruang yang sama dapat mengalami *waiting* ketika terdapat transaction lain yang sedang memegang lock.

### 5.2 Penyimpanan Waktu dalam UTC

Data `start_time` dan `end_time` disimpan dalam UTC.

- **Input:** Asia/Jakarta ➔ UTC ➔ Database
- **Output:** Database (UTC) ➔ Asia/Jakarta ➔ API / Blade

### 5.3 Mengapa Tidak Menggunakan Unique Index untuk Overlap

Unique index seperti `(room_id, start_time, end_time)` tidak cukup untuk mencegah seluruh kasus overlap (misal Booking A: 10:00–12:00 dan Booking B: 11:00–13:00 memiliki kombinasi kolom berbeda namun tetap overlap). Karena itu, pencegahan overlap dilakukan melalui business logic dan mekanisme concurrency pada transaction.

### 5.4 Availability Search

Pencarian ruang tersedia menggunakan pendekatan:

1. Cari ruangan yang mengalami conflict.
2. Exclude ruangan tersebut.
3. Tampilkan ruangan yang tersisa (`whereNotIn`).

---

## 6. Aturan dan Matriks Pengujian Overlap

Sistem menggunakan interval `[start_time, end_time)`. Dua interval dianggap overlap apabila:

```
S1 < E2   AND   E1 > S2
```

Dengan pendekatan ini, booking yang selesai tepat ketika booking berikutnya dimulai tetap diperbolehkan.

### Matriks Pengujian

| No | Skenario | Booking Existing | Booking Baru | Hasil |
| :---: | :--- | :--- | :--- | :---: |
| 1 | Identik | 10:00 – 11:00 | 10:00 – 11:00 | ❌ Conflict |
| 2 | Nested | 09:00 – 12:00 | 10:00 – 11:00 | ❌ Conflict |
| 3 | Overlap awal | 10:00 – 11:00 | 09:30 – 10:30 | ❌ Conflict |
| 4 | Overlap akhir | 10:00 – 11:00 | 10:30 – 11:30 | ❌ Conflict |
| 5 | Tepat bersentuhan | 10:00 – 11:00 | 11:00 – 12:00 | ✅ Allowed |
| 6 | Sebelum | 10:00 – 11:00 | 09:00 – 10:00 | ✅ Allowed |
| 7 | Sesudah | 10:00 – 11:00 | 11:00 – 12:00 | ✅ Allowed |

**Validasi tambahan:**

- `start_time == end_time` → ❌ Invalid
- `end_time < start_time` → ❌ Invalid

Conflict booking dikembalikan sebagai `409 Conflict`, sedangkan kesalahan validasi input menggunakan `422 Unprocessable Entity`.

---

## 7. Penanganan Race Condition dan Concurrency

Race condition ditangani menggunakan transaction database dan pessimistic locking.

Secara konseptual:

**Request A** masuk ➔ `BEGIN TRANSACTION` ➔ `LOCK Room` ➔ `CHECK OVERLAP` ➔ `CREATE BOOKING` ➔ `COMMIT` ➔ `RELEASE LOCK`.

Jika **Request B** datang bersamaan untuk ruang yang sama:

`BEGIN TRANSACTION` ➔ `WAIT FOR ROOM LOCK` ➔ `CHECK OVERLAP` (konflik ditemukan) ➔ `ROLLBACK` ➔ `409 Conflict`.

Mekanisme ini dibuktikan melalui automated test pada `tests/Feature/BookingConcurrencyTest.php`.

---

## 8. Dokumentasi API

### Search Available Rooms

`GET /api/rooms/search`

**Parameter:** `start_time`, `end_time`, `min_capacity`

**Response:**

```json
{
    "status": "success",
    "data": [
        {
            "id": 2,
            "name": "Ruang Alpha",
            "capacity": 10,
            "location": "Lantai 1"
        }
    ]
}
```

### Create Booking

`POST /api/bookings`

**Request:**

```json
{
    "room_id": 2,
    "start_time": "2026-10-15 10:00:00",
    "end_time": "2026-10-15 11:00:00",
    "user_id": 1
}
```

- **Success:** `201 Created`
- **Conflict:** `409 Conflict`

### Cancel Booking

`PATCH /api/bookings/{id}/cancel`

**Request:**

```json
{
    "user_id": 1
}
```

- **Forbidden (bukan pemilik):** `403 Forbidden`

---

## 9. Batasan yang Disadari

**Simple User Identification**
Assessment hanya membutuhkan mekanisme identitas sederhana untuk membedakan pemilik booking. Karena itu, implementasi menggunakan `user_id` secara langsung dan belum menggunakan sistem autentikasi penuh seperti Laravel Sanctum, JWT, OAuth2, atau SSO agar scope assessment tetap fokus pada business rule reservasi.

**Database Constraint untuk Arbitrary Time Range**
MySQL tidak menyediakan *exclusion constraint* untuk arbitrary time range seperti yang tersedia pada PostgreSQL. Oleh karena itu, solusi yang digunakan adalah kombinasi Application Business Rule + Database Transaction + Pessimistic Locking.

---

## 10. Bagian yang Belum Selesai dan Roadmap

Beberapa fitur tidak dilanjutkan karena keterbatasan waktu pengerjaan dan tidak termasuk Mandatory:

- **Recurring Booking** — Fitur booking berulang harian/mingguan belum diintegrasikan ke versi final.
- **Operating Hours dan Buffer** — Konfigurasi jam operasional (misal: 08:00–17:00) dan buffer antarbooking belum diterapkan.
- **My Bookings** — Halaman khusus untuk menampilkan seluruh booking milik user belum diimplementasikan.
- **Full Authentication** — Belum terdapat Login, Register, Logout, Password hashing, atau Bearer token.
- **External Calendar Integration** — Integrasi dengan Google Calendar, Microsoft Outlook, atau iCalendar belum diterapkan.
- **Notification & Reminder** — Sistem belum memiliki email confirmation, reminder, webhook, atau queue worker.
- **Role-Based Access Control** — Pemisahan role Administrator dan User belum diterapkan.