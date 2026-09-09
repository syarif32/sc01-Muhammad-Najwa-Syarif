Sistem Reservasi Ruang Meeting

Backend RESTful API dan antarmuka web berbasis Laravel Blade untuk pengelolaan ruang meeting dan reservasi.

Proyek ini berfokus pada pemodelan data, konsistensi data, aturan bisnis, pencegahan double-booking, authorization, serta penanganan concurrency.

Daftar Isi
1. Status Pengerjaan
2. Fitur Berdasarkan Tingkatan
3. Prasyarat dan Cara Menjalankan
4. Antarmuka Web
5. Keputusan Desain
6. Aturan dan Matriks Pengujian Overlap
7. Penanganan Race Condition dan Concurrency
8. Dokumentasi API
9. Batasan yang Disadari
10. Bagian yang Belum Selesai dan Roadmap
1. Status Pengerjaan

Studi kasus yang dikerjakan:

Sistem Reservasi Ruang Meeting

Implementasi menggunakan:

Komponen	Teknologi
Backend	Laravel
Database	MySQL
ORM	Eloquent
Web UI	Laravel Blade + TailwindCSS
API	RESTful API
Testing	PHPUnit
Version Control	Git

Fokus utama implementasi adalah memastikan aturan bisnis double-booking tetap konsisten, termasuk ketika terdapat dua permintaan booking yang datang secara bersamaan.

2. Fitur Berdasarkan Tingkatan
2.1 Mandatory
Fitur	Status	Keterangan
CRUD ruang meeting	✅ Selesai	Nama, kapasitas, dan lokasi
Membuat booking	✅ Selesai	Booking berdasarkan ruang dan rentang waktu
Pencegahan double-booking	✅ Selesai	Overlap tidak diperbolehkan
Daftar booking per ruang	✅ Selesai	Dapat difilter berdasarkan tanggal
Pembatalan booking	✅ Selesai	Hanya pemilik booking yang dapat membatalkan
Identitas user sederhana	✅ Selesai	Menggunakan user_id sebagai identitas
Validasi input	✅ Selesai	Validasi ruang dan rentang waktu

Mandatory diselesaikan terlebih dahulu sebelum pengembangan fitur tambahan.

2.2 Nice to Have
Fitur	Status	Keterangan
Pengujian seluruh bentuk overlap	✅ Selesai	Mencakup identical, nested, partial overlap, dan boundary
Pengujian overlap tanpa HTTP	✅ Selesai	Logika overlap dapat diuji pada level model
Penyimpanan waktu dalam UTC	✅ Selesai	Konversi timezone dilakukan pada edge aplikasi
Error response konsisten	✅ Selesai	409 Conflict untuk bentrok jadwal dan 422 untuk validation error
Pemisahan lapisan	✅ Selesai	Logika overlap dapat diuji tanpa melalui HTTP
2.3 Enhancement
Fitur	Status	Keterangan
Race-condition safe booking	✅ Selesai	Transaction + pessimistic locking
Concurrent booking test	✅ Selesai	Memastikan hanya satu booking berhasil
Availability search	✅ Selesai	Pencarian ruang berdasarkan waktu dan kapasitas
Booking audit trail	✅ Selesai	Perubahan tertentu dicatat pada booking_logs
Recurring booking	❌ Belum selesai	Masuk roadmap
Configurable operating hours	❌ Belum selesai	Masuk roadmap
Configurable booking buffer	❌ Belum selesai	Masuk roadmap

Fitur Enhancement dikerjakan setelah fitur Mandatory dan Nice to Have dianggap stabil.

3. Prasyarat dan Cara Menjalankan
Prasyarat
PHP 8.1 / 8.2 / 8.3
Composer
MySQL 8.0+
Node.js dan NPM jika diperlukan untuk asset frontend
Instalasi

Clone repository:

git clone <repo-url>
cd meeting-room-api

Install dependency:

composer install

Salin konfigurasi environment:

cp .env.example .env

Generate application key:

php artisan key:generate

Atur konfigurasi database pada .env.

Contoh:

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=meeting_room
DB_USERNAME=root
DB_PASSWORD=

Jalankan migration:

php artisan migrate

Jalankan aplikasi:

php artisan serve

Aplikasi dapat diakses melalui:

http://localhost:8000
Menjalankan Automated Test

Seluruh test dapat dijalankan menggunakan:

php artisan test

Test mencakup business rule utama, validasi, authorization, timezone, overlap, dan concurrency.

4. Antarmuka Web

Antarmuka web dibuat menggunakan Laravel Blade dengan tampilan sederhana yang berfokus pada fungsi utama aplikasi.

Dashboard utama menyediakan beberapa bagian:

Room Management
Membuat ruang meeting
Melihat daftar ruang
Mengubah data ruang
Menghapus ruang

Data ruang terdiri dari:

Nama
Kapasitas
Lokasi
Room Availability Search

Pengguna dapat mencari ruang yang tersedia berdasarkan:

Waktu mulai
Waktu selesai
Kapasitas minimum

Sistem akan mengecualikan ruangan yang memiliki booking yang overlap dengan rentang waktu tersebut.

Booking Management

Pengguna dapat:

Membuat booking
Melihat daftar booking berdasarkan ruangan
Memfilter booking berdasarkan tanggal
Membatalkan booking miliknya

Pembatalan memerlukan user_id sebagai mekanisme identifikasi sederhana sesuai kebutuhan assessment.

5. Keputusan Desain
5.1 Pessimistic Locking untuk Concurrency
Keputusan

Sistem menggunakan:

DB::transaction()

bersama dengan:

lockForUpdate()

pada resource ruang yang sedang dibooking.

Alasan

Pengecekan overlap saja tidak cukup untuk menghadapi race condition.

Contohnya:

Request A → cek booking → tidak ada conflict
Request B → cek booking → tidak ada conflict
Request A → insert booking
Request B → insert booking

Tanpa mekanisme locking, kedua request berpotensi berhasil.

Dengan melakukan locking pada row ruang di dalam transaction, request untuk ruang yang sama diproses secara terkontrol.

Trade-off

Keuntungan:

Relatif sederhana untuk diterapkan pada Laravel.
Tidak membutuhkan database khusus.
Dapat diuji secara otomatis.
Cocok dengan kebutuhan assessment.

Konsekuensinya, request booking untuk ruang yang sama dapat mengalami waiting ketika terdapat transaction lain yang sedang memegang lock.

5.2 Penyimpanan Waktu dalam UTC

Data start_time dan end_time disimpan dalam UTC.

Konversi dilakukan:

Input
 ↓
Asia/Jakarta
 ↓
UTC
 ↓
Database

Ketika data ditampilkan:

Database UTC
 ↓
Asia/Jakarta
 ↓
API / Blade

Pendekatan ini membuat penyimpanan waktu memiliki satu referensi yang konsisten.

5.3 Mengapa Tidak Menggunakan Unique Index untuk Overlap

Unique index seperti:

(room_id, start_time, end_time)

tidak cukup untuk mencegah seluruh kasus overlap.

Contohnya:

Booking A: 10:00 - 12:00
Booking B: 11:00 - 13:00

Kedua booking memiliki kombinasi start_time dan end_time yang berbeda, tetapi tetap mengalami overlap.

Karena itu, pencegahan overlap dilakukan melalui business logic dan mekanisme concurrency pada transaction.

5.4 Availability Search

Pencarian ruang tersedia menggunakan pendekatan:

Cari ruangan yang mengalami conflict
        ↓
Exclude ruangan tersebut
        ↓
Tampilkan ruangan yang tersisa

Pendekatan ini dipilih agar tidak perlu melakukan looping seluruh ruangan dan menjalankan pengecekan satu per satu di sisi PHP.

6. Aturan dan Matriks Pengujian Overlap

Sistem menggunakan interval:

[start_time, end_time)

Dua interval dianggap overlap apabila:

S1 < E2 AND E1 > S2

Dengan pendekatan ini, booking yang selesai tepat ketika booking berikutnya dimulai tetap diperbolehkan.

Contoh:

Booking A: 10:00 ─── 11:00
Booking B:              11:00 ─── 12:00

Tidak dianggap overlap.

Matriks Pengujian
No	Skenario	Booking Existing	Booking Baru	Hasil
1	Identik	10:00–11:00	10:00–11:00	❌ Conflict
2	Nested	09:00–12:00	10:00–11:00	❌ Conflict
3	Overlap awal	10:00–11:00	09:30–10:30	❌ Conflict
4	Overlap akhir	10:00–11:00	10:30–11:30	❌ Conflict
5	Tepat bersentuhan	10:00–11:00	11:00–12:00	✅ Allowed
6	Sebelum	10:00–11:00	09:00–10:00	✅ Allowed
7	Sesudah	10:00–11:00	11:00–12:00	✅ Allowed

Validasi tambahan:

start_time == end_time → ❌ Invalid
end_time < start_time → ❌ Invalid

Conflict booking dikembalikan sebagai:

409 Conflict

Sedangkan kesalahan validasi input menggunakan:

422 Unprocessable Entity
7. Penanganan Race Condition dan Concurrency

Race condition ditangani menggunakan transaction database dan pessimistic locking.

Secara konseptual:

Request A
   ↓
BEGIN TRANSACTION
   ↓
LOCK Room
   ↓
CHECK OVERLAP
   ↓
CREATE BOOKING
   ↓
COMMIT
   ↓
RELEASE LOCK

Jika Request B datang pada saat yang sama untuk ruang yang sama:

Request B
   ↓
BEGIN TRANSACTION
   ↓
WAIT FOR ROOM LOCK
   ↓
Room lock tersedia
   ↓
CHECK OVERLAP
   ↓
Conflict ditemukan
   ↓
ROLLBACK
   ↓
409 Conflict

Dengan demikian, dua request yang mencoba melakukan booking pada slot yang sama tidak dapat menghasilkan dua booking yang valid.

Mekanisme ini dibuktikan melalui automated test pada:

tests/Feature/BookingConcurrencyTest.php
8. Dokumentasi API
Search Available Rooms
GET /api/rooms/search

Parameter:

start_time
end_time
min_capacity

Contoh response:

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
Create Booking
POST /api/bookings

Request:

{
    "room_id": 2,
    "start_time": "2026-10-15 10:00:00",
    "end_time": "2026-10-15 11:00:00",
    "user_id": 1
}

Success:

201 Created

Jika terjadi overlap:

409 Conflict
Cancel Booking
PATCH /api/bookings/{id}/cancel

Request:

{
    "user_id": 1
}

Sistem akan memvalidasi bahwa user_id yang diberikan merupakan pemilik booking.

Jika bukan pemilik:

403 Forbidden
9. Batasan yang Disadari
Simple User Identification

Assessment hanya membutuhkan mekanisme identitas sederhana untuk membedakan pemilik booking.

Karena itu, implementasi menggunakan user_id secara langsung dan belum menggunakan sistem autentikasi penuh seperti Laravel Sanctum, JWT, OAuth2, atau SSO.

Pendekatan ini dipilih untuk menjaga scope assessment tetap fokus pada business rule reservasi.

Database Constraint untuk Arbitrary Time Range

MySQL tidak menyediakan exclusion constraint untuk arbitrary time range seperti yang tersedia pada PostgreSQL.

Karena itu, solusi yang digunakan adalah:

Application Business Rule
+
Database Transaction
+
Pessimistic Locking

Pendekatan ini dipilih karena sesuai dengan stack yang digunakan dan kebutuhan assessment.

10. Bagian yang Belum Selesai dan Roadmap

Beberapa fitur tidak dilanjutkan karena keterbatasan waktu pengerjaan dan tidak termasuk Mandatory.

Recurring Booking

Status: Belum selesai.

Fitur booking berulang harian/mingguan belum diintegrasikan ke versi final.

Pengembangan selanjutnya dapat mencakup:

recurring rule
pengecualian tanggal
validasi overlap untuk setiap occurrence
Operating Hours dan Booking Buffer

Status: Belum selesai.

Konfigurasi seperti:

08:00 – 17:00

dan buffer antarbooking belum diterapkan pada versi final.

Fitur ini sengaja tidak dipaksakan agar tidak mengganggu business rule utama dan test suite yang telah selesai.

My Bookings

Status: Belum selesai.

Dashboard saat ini berfokus pada pengelolaan ruang dan jadwal per ruang.

Halaman khusus untuk menampilkan seluruh booking milik user belum diimplementasikan.

Full Authentication

Status: Belum selesai.

Belum terdapat:

Login
Register
Logout
Password hashing
Bearer token
SSO/OAuth2

Identitas user masih menggunakan user_id sederhana sesuai scope assessment.

External Calendar Integration

Status: Belum selesai.

Integrasi dengan:

Google Calendar
Microsoft Outlook
iCalendar

belum diterapkan.

Notification dan Reminder

Status: Belum selesai.

Sistem belum memiliki:

Email confirmation
Reminder meeting
Webhook
WhatsApp notification
Background queue
Role-Based Access Control

Status: Belum selesai.

Pemisahan role seperti:

User
Admin

belum diterapkan.

Pengembangan selanjutnya dapat memberikan administrator kemampuan untuk:

mengelola seluruh ruang
mengubah konfigurasi ruang
membatalkan booking pengguna lain
mengelola aturan operasional
Penutup

Implementasi diprioritaskan berdasarkan requirement assessment dengan urutan:

Mandatory
    ↓
Nice to Have
    ↓
Enhancement

Fokus utama pengerjaan adalah memastikan aturan double-booking benar, dapat diuji, dan aman terhadap race condition, kemudian melengkapi fitur pendukung serta dokumentasi.

Fitur yang belum selesai secara sadar dicatat sebagai bagian dari known limitations dan roadmap, bukan dianggap sebagai fitur yang telah selesai.