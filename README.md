# 🎮 KeBox — Web Game Platform

> Platform web game online berbasis PHP & Oracle Database dengan tema dark purple.  
> Tersedia **Word Game** (gaya Wordle) dan **Sliding Puzzle** dengan mode 1 Player & 2 Player Online.

---

## 📋 Daftar Isi

- [Tentang Proyek](#tentang-proyek)
- [Fitur](#fitur)
- [Teknologi](#teknologi)
- [Struktur Folder](#struktur-folder)
- [Instalasi](#instalasi)
- [Akun Default](#akun-default)
- [Cara Bermain](#cara-bermain)
- [Sistem Skor](#sistem-skor)
- [Troubleshooting](#troubleshooting)

---

## 📖 Tentang Proyek

**KeBox** adalah platform web game yang dikembangkan menggunakan PHP dan Oracle Database. Platform ini menggabungkan dua jenis permainan populer yang dapat dimainkan secara solo maupun bersama teman secara online melalui sistem kode room.

---

## ✨ Fitur

### 👤 User
- Register dan Login akun
- Dashboard dengan statistik permainan
- **Word Game** — Tebak kata gaya Wordle
  - 🟢 Easy: 4 huruf, 8 percobaan
  - 🟡 Medium: 5 huruf, 6 percobaan
  - 🔴 Hard: 6 huruf, 4 percobaan + timer 60 detik
- **Sliding Puzzle** — Susun angka berurutan
  - 10 level (grid 3×3 hingga 8×8)
  - Tracking moves dan waktu
- Mode **1 Player** dan **2 Player Online** (via kode room)

### ⚡ Admin
- Dashboard statistik (total user, kata, level, skor)
- CRUD User (tambah, edit, hapus, cari)
- CRUD Kata Word Game (validasi panjang otomatis per level)
- CRUD Level Puzzle (pilih ukuran grid 2×2 sampai 10×10 + preview interaktif)

---

## 🛠️ Teknologi

| Komponen | Detail |
|----------|--------|
| Backend | PHP 7.4+ |
| Database | Oracle Database (XE / 19c) |
| Koneksi DB | Ekstensi OCI8 |
| Frontend | HTML5, CSS3, JavaScript (Vanilla) |
| Web Server | Apache (XAMPP/WAMP) atau Nginx |

---

## 📁 Struktur Folder

```
kebox/
├── index.php               ← Homepage
├── login.php               ← Halaman login
├── register.php            ← Halaman registrasi
├── logout.php              ← Proses logout
├── dashboard.php           ← Dashboard user
├── setup.sql               ← Script setup Oracle lengkap
│
├── css/
│   └── style.css           ← Global stylesheet (dark purple theme)
│
├── includes/
│   ├── db.php              ← Koneksi Oracle Database
│   └── auth.php            ← Session & autentikasi
│
├── game/
│   ├── word-select.php     ← Pilih level Word Game
│   ├── word-mode.php       ← Pilih mode 1P / 2P
│   ├── word-play.php       ← Halaman bermain Word Game
│   ├── word-online.php     ← Lobby 2-Player Word Game
│   ├── puzzle-select.php   ← Pilih level Puzzle
│   ├── puzzle-mode.php     ← Pilih mode 1P / 2P
│   ├── puzzle-play.php     ← Halaman bermain Sliding Puzzle
│   ├── puzzle-online.php   ← Lobby 2-Player Puzzle
│   ├── api-word.php        ← API: ambil kata random dari DB
│   ├── api-score.php       ← API: simpan skor ke DB
│   └── api-session.php     ← API: manajemen sesi 2-player
│
└── admin/
    ├── dashboard.php       ← Dashboard admin
    ├── users.php           ← CRUD user
    ├── words.php           ← CRUD kata Word Game
    └── levels.php          ← CRUD level Puzzle
```

---

## 🚀 Instalasi

### Prasyarat
- PHP 7.4+ dengan ekstensi OCI8
- Oracle Database (XE / 11g / 19c)
- Apache / Nginx (XAMPP/WAMP untuk lokal)

---

### Langkah 1 — Clone / Letakkan File

Letakkan folder `kebox` di dalam direktori web server:

```
# XAMPP
C:/xampp/htdocs/kebox/

# WAMP
C:/wamp64/www/kebox/

# Linux Apache
/var/www/html/kebox/
```

Akses via browser: `http://localhost/kebox/`

---

### Langkah 2 — Aktifkan Ekstensi OCI8

Buka file `php.ini` (biasanya di folder PHP atau XAMPP), cari dan aktifkan baris:

```ini
extension=oci8
; Untuk PHP 8+:
extension=oci8_19
```

Restart Apache setelah mengubah `php.ini`.

---

### Langkah 3 — Setup Database Oracle

**Bagian 1 — Jalankan sebagai SYSDBA** (buat tablespace + user):

```bash
sqlplus sys/password_anda as sysdba
```

Lalu jalankan bagian DBA dari `setup.sql` (Bagian 1 dan Bagian 7).

**Bagian 2 — Jalankan sebagai kebox_user** (buat tabel + data):

```bash
sqlplus kebox_user/kebox_pass@localhost:1521/XEPDB1
```

```sql
@/path/to/kebox/setup.sql
```

> **Catatan:** Ganti `XEPDB1` dengan service name Oracle kamu. Cek dengan perintah `lsnrctl status`.

---

### Langkah 4 — Konfigurasi Koneksi Database

Edit file `includes/db.php` sesuaikan dengan konfigurasi Oracle kamu:

```php
define('DB_HOST',    'localhost');
define('DB_PORT',    '1521');
define('DB_SERVICE', 'XEPDB1');    // Sesuaikan dengan service name Oracle kamu
define('DB_USER',    'kebox_user');
define('DB_PASS',    'kebox_pass');
```

---

### Langkah 5 — Verifikasi Database

Jalankan query berikut untuk memastikan setup berhasil:

```sql
-- Cek tabel yang terbuat (harusnya ada 5 tabel)
SELECT table_name FROM user_tables ORDER BY table_name;

-- Cek jumlah kata per level
SELECT word_level, COUNT(*) AS jumlah FROM words GROUP BY word_level;

-- Cek level puzzle
SELECT level_num, grid_size, level_label FROM puzzle_levels ORDER BY level_num;

-- Cek akun admin
SELECT username, email, user_role FROM users WHERE user_role = 'admin';
```

---

## 🔑 Akun Default

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `password` |

> **Penting:** Segera ganti password setelah pertama kali login melalui menu **Kelola User** di panel admin.

### Cara Login Admin
1. Buka `http://localhost/kebox/login.php`
2. Masukkan username: `admin`
3. Masukkan password: `password`
4. Klik Login — akan diarahkan ke dashboard admin

---

## 🎮 Cara Bermain

### Word Game
1. Login → pilih **Word Game** di dashboard
2. Pilih level: Easy / Medium / Hard
3. Pilih mode: 1 Player atau 2 Player Online
4. Tebak kata dengan mengetik huruf dan tekan Enter
5. Petunjuk warna:
   - 🟩 **Hijau** — huruf benar di posisi yang tepat
   - 🟧 **Oranye** — huruf ada tapi posisi salah
   - ⬛ **Gelap** — huruf tidak ada dalam kata

### Sliding Puzzle
1. Login → pilih **Sliding Puzzle** di dashboard
2. Pilih level 1–10
3. Pilih mode: 1 Player atau 2 Player Online
4. Klik tile di sebelah kotak kosong untuk menggesernya
5. Susun angka dari 1 sampai N dari kiri atas ke kanan bawah

### Mode 2 Player Online
1. Pilih **2 Player Online**
2. Player 1 klik **Buat Room** → bagikan kode 6 digit ke teman
3. Player 2 masukkan kode → klik **Gabung Room**
4. Game dimulai otomatis saat kedua player terhubung

---

## 🏆 Sistem Skor

### Word Game
```
Menang : max(10, 100 - (percobaan-1) × 15 + bonus_waktu)
Kalah  : 0
```
Bonus waktu hanya berlaku untuk mode Hard (sisa detik dibagi 2).

### Sliding Puzzle
```
Skor : max(10, 1000 - (moves × 2) - durasi_detik)
```
Semakin sedikit gerakan dan waktu, semakin tinggi skor.

---

## ❗ Troubleshooting

**Error: Call to undefined function oci_connect()**
```
→ Ekstensi OCI8 belum aktif
→ Buka php.ini, aktifkan extension=oci8, restart Apache
```

**Error: Database connection failed**
```
→ Pastikan Oracle Database sedang berjalan
→ Cek service name: lsnrctl status
→ Sesuaikan DB_HOST, DB_PORT, DB_SERVICE di includes/db.php
```

**Error: ORA-00904 invalid identifier**
```
→ Jangan gunakan nama kolom yang merupakan reserved word Oracle
→ Kolom sudah diperbaiki: level→word_level, role→user_role
```

**Halaman redirect ke login terus**
```
→ Pastikan session sudah aktif
→ Cek apakah includes/auth.php ter-include dengan benar
```

**Kata tidak muncul di Word Game**
```
→ Pastikan tabel words sudah terisi data
→ Cek: SELECT COUNT(*) FROM words;
→ Sistem akan pakai kata fallback jika tabel kosong
```

**Tidak bisa akses halaman admin**
```
→ Pastikan akun yang digunakan memiliki user_role = 'admin'
→ Cek: SELECT user_role FROM users WHERE username='admin';
```

---

## 👥 Kontribusi

1. Zuhri: Frontend
2. Damar: Backend
3. Labib: Pembuatan Laporan

---

## 📄 Lisensi

Proyek ini dibuat untuk keperluan akademik.
