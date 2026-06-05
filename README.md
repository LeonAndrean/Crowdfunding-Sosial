# Berbagi Donasi Social

Platform penggalangan dana sosial berbasis web yang memungkinkan masyarakat untuk membuat, mengelola, dan berdonasi pada campaign sosial secara transparan. Dibangun menggunakan PHP native, MySQL, dan CSS murni tanpa framework tambahan.

---

## Daftar Isi

- [Fitur](#fitur)
- [Teknologi](#teknologi)
- [Struktur Direktori](#struktur-direktori)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Cara Instalasi](#cara-instalasi)
- [Konfigurasi Database](#konfigurasi-database)
- [Akun Bawaan untuk Pengujian](#akun-bawaan-untuk-pengujian)
- [Alur Penggunaan](#alur-penggunaan)

---

## Fitur

**Untuk Donatur**

- Melihat daftar campaign aktif dengan pencarian berdasarkan judul, kategori, lokasi, dan pengelola
- Melakukan donasi dengan unggah bukti pembayaran
- Melihat riwayat donasi beserta status verifikasi
- Melihat ringkasan statistik donasi pribadi
- Mengelola profil akun termasuk foto profil

**Untuk Pengelola Campaign (Manager)**

- Membuat, mengedit, dan menghapus campaign
- Melihat dashboard campaign beserta progres dana terkumpul
- Memverifikasi atau menolak donasi yang masuk

**Umum**

- Notifikasi donasi terbaru secara live di halaman utama
- Sistem autentikasi dengan dua peran: Donatur dan Pengelola
- Pengaturan akun: ubah nama, nomor telepon, alamat, password, dan foto profil

---

## Teknologi

- PHP 8.x (native, tanpa framework)
- MySQL / MariaDB via phpMyAdmin
- HTML5 dan CSS3 (tanpa framework CSS)
- JavaScript vanilla
- Google Fonts (Plus Jakarta Sans, Lora)
- XAMPP sebagai server lokal

---

## Struktur Direktori

```
projekmini2/
├── assets/
│   └── css/              # File stylesheet per halaman
├── uploads/              # Foto kampanye, bukti donasi, dan avatar pengguna
├── config.php            # Konfigurasi koneksi database
├── index.php             # Halaman utama (daftar campaign)
├── login.php             # Halaman login
├── logout.php            # Proses logout
├── register.php          # Halaman registrasi
├── detail.php            # Detail campaign
├── donate.php            # Form donasi
├── donation_history.php  # Riwayat donasi (donor)
├── summary.php           # Ringkasan statistik donasi
├── manager_dashboard.php # Dashboard pengelola campaign
├── campaign_add.php      # Tambah campaign baru
├── campaign_edit.php     # Edit campaign
├── campaign_delete.php   # Hapus campaign
├── verify_donations.php  # Verifikasi donasi masuk
├── pengaturan_akun.php   # Pengaturan profil pengguna
├── projekmini2_db.sql    # File dump database utama
└── add_avatar_column.sql # Patch SQL untuk kolom avatar
```

---

## Persyaratan Sistem

- XAMPP (atau server lokal setara) dengan Apache dan MySQL aktif
- PHP versi 8.0 atau lebih baru
- Browser modern (Chrome, Firefox, Edge)

---

## Cara Instalasi

**1. Clone atau unduh repository ini**

```bash
git clone https://github.com/username/projekmini2.git
```

Atau unduh sebagai ZIP lalu ekstrak.

**2. Pindahkan ke direktori htdocs**

Salin seluruh folder project ke dalam `C:\xampp\htdocs\` (Windows) atau `/opt/lampp/htdocs/` (Linux).

Contoh hasil akhir: `C:\xampp\htdocs\projekmini2\`

**3. Jalankan XAMPP**

Aktifkan modul Apache dan MySQL melalui XAMPP Control Panel.

**4. Import database**

- Buka `http://localhost/phpmyadmin` di browser
- Buat database baru dengan nama `projekmini2_db`
- Pilih tab Import, lalu pilih file `projekmini2_db.sql` dari folder project
- Klik Go untuk mengeksekusi

**5. Sesuaikan konfigurasi (jika diperlukan)**

Buka file `config.php` dan sesuaikan kredensial database jika berbeda dari pengaturan default XAMPP:

```php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "projekmini2_db";
```

**6. Buka aplikasi**

Akses melalui browser:

```
http://localhost/projekmini2/
```

---

## Konfigurasi Database

Database terdiri dari tiga tabel utama:

| Tabel       | Keterangan                                                      |
|-------------|-----------------------------------------------------------------|
| `users`     | Data pengguna dengan peran `donor` atau `manager`               |
| `campaigns` | Data campaign termasuk target, dana terkumpul, dan deadline     |
| `donations` | Data transaksi donasi dengan status `pending`, `verified`, atau `rejected` |

---

## Akun Bawaan untuk Pengujian

Berikut akun yang sudah tersedia di dalam file SQL untuk keperluan pengujian:

| Nama              | Email                              | Password | Peran    |
|-------------------|------------------------------------|----------|----------|
| admin2            | admin2@gmail.com                   | (hash)   | Manager  |
| LEONARDO ANDREAN  | leonardo.andrean@ti.ukdw.ac.id     | (hash)   | Manager  |
| Arseen 45         | sadrakhwibowo@gmail.com            | (hash)   | Donor    |
| Maxz Kebon        | leonandreanleon@gmail.com          | (hash)   | Donor    |

Password pada akun di atas telah di-hash dengan bcrypt. Untuk pengujian, disarankan mendaftar akun baru melalui halaman `register.php` agar password diketahui langsung.

---

## Alur Penggunaan

**Sebagai Donatur:**
1. Daftar akun baru di halaman Register, pilih peran Donatur
2. Login, lalu jelajahi campaign aktif di halaman utama
3. Klik Lihat Detail Campaign untuk melihat informasi lengkap
4. Klik tombol donasi, isi nominal dan unggah bukti pembayaran
5. Pantau status donasi di halaman Riwayat Donasi
6. Lihat statistik keseluruhan di halaman Ringkasan Donasi

**Sebagai Pengelola Campaign:**
1. Daftar akun baru di halaman Register, pilih peran Pengelola Kampanye
2. Login, lalu akses Dashboard dari navbar
3. Tambahkan campaign baru dengan mengisi judul, kategori, target, deadline, dan gambar
4. Tinjau donasi masuk melalui menu Verifikasi Donasi dan ubah statusnya menjadi Verified atau Rejected
5. Edit atau hapus campaign sesuai kebutuhan

---

## Catatan Pengembangan

Project ini dibuat sebagai tugas Projek Mini 2 mata kuliah Praktikum Pemrograman Web di Universitas Kristen Duta Wacana (UKDW). Seluruh kode ditulis tanpa framework PHP maupun CSS agar lebih fokus pada pemahaman dasar pengembangan web.
