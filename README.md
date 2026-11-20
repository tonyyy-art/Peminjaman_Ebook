| NAMA | NIM |
|------|-----|
| AHMAD FATHONI | 202410370110444 |
| Muhammad Fayyad Hudzaqi | 202410370110450 |
| Ilham Antonika Wijaya | 202410370110452 |
| ABU BAKAR HADUN | 202410370110477 |
| Abid Abdullah Ghani | 202410370110485 |

# Panduan Setup — Sistem Peminjaman E-Book

Dokumentasi lengkap cara memasang & menjalankan aplikasi **Sistem Peminjaman E-Book**
(FrontEnd HTML/CSS/JS + BackEnd PHP 8 OOP + database MySQL) di laptop baru.
> Aplikasi ini adalah prototipe untuk tugas UTS Pemrograman Web.
> Seluruh data (user, e-book, peminjaman) disimpan di database MySQL `peminjaman_ebook`.

---

## 1. Yang Dibutuhkan

| Kebutuhan | Keterangan |
|-----------|------------|
| Sistem Operasi | Windows 10/11 (panduan ini). Linux/macOS caranya mirip. |
| Web Server + PHP | PHP **8.1 atau lebih baru** (kode memakai fitur `never`, `str_starts_with`, dll). |
| Database | MySQL / MariaDB. |
| Browser | Chrome, Edge, atau Firefox. |

Cara paling mudah: pasang **XAMPP** — satu paket berisi Apache + PHP 8 + MySQL + phpMyAdmin.

> Catatan versi: XAMPP terbaru sudah memakai PHP 8.2, jadi aman. Jika PHP di bawah 8.1, aplikasi akan error.

---

## 2. Install XAMPP (cukup sekali)

1. Unduh XAMPP dari **https://www.apachefriends.org** (pilih versi dengan PHP 8.1/8.2).
2. Jalankan installer, klik **Next** sampai selesai (lokasi default `C:\xampp`).
3. Buka **XAMPP Control Panel**.
4. Klik **Start** pada baris **Apache** dan **MySQL**.
   - Jika muncul peringatan Windows Firewall, pilih **Allow**.
   - Pastikan kedua modul berwarna hijau / bertuliskan *Running*.

---

## 3. Pasang Proyek

1. **Ekstrak** file `peminjaman-ebook.zip`.
2. Pindahkan folder hasil ekstrak (`peminjaman-ebook`) ke dalam `C:\xampp\htdocs\`.
3. Pastikan hasilnya menjadi:

   ```
   C:\xampp\htdocs\peminjaman-ebook\
   ```

> Catatan: tampilan memakai Google Fonts, jadi butuh internet. Tanpa internet
> tampilan tetap normal dengan font sistem.

> Penting: jangan memisahkan folder `FrontEnd` dan `BackEnd`.
> Keduanya harus tetap di dalam satu folder `peminjaman-ebook`.

---

## 4. Membuat Database (Import Schema)

Database dan seluruh data dummy dibuat otomatis dari file `schema.sql`.

### Cara A — phpMyAdmin (paling mudah, disarankan)

1. Buka browser: **http://localhost/phpmyadmin**
2. Pada halaman utama (tanpa memilih database), klik tab **Import**.
3. Klik **Choose File**, pilih:
   `C:\xampp\htdocs\peminjaman-ebook\BackEnd\database\schema.sql`
4. Scroll ke bawah, klik **Import**.
5. Tunggu sampai muncul pesan *"import has been successfully finished"*.

Database `peminjaman_ebook`, ketiga tabel (`users`, `ebooks`, `peminjaman`),
dan data contoh akan otomatis terbuat.

### Cara B — Baris Perintah (XAMPP Shell / cmd)

```cmd
C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\peminjaman-ebook\BackEnd\database\schema.sql
```

> Jika MySQL diberi password, tambahkan `-p` lalu masukkan password saat diminta.

---

## 5. (Opsional) Sesuaikan Koneksi Database

Default konfigurasi **sudah cocok** untuk XAMPP (user `root`, tanpa password).
Jika MySQL Anda memakai password, buka file:

```
BackEnd\config\database.php
```

lalu sesuaikan:

```php
$user = 'root';
$pass = '';        // isi password MySQL Anda di sini jika ada
```

Variabel `$host`, `$port`, dan `$name` juga bisa diubah bila perlu
(default `127.0.0.1`, `3306`, `peminjaman_ebook`).

---

## 6. Menjalankan Aplikasi

1. Pastikan **Apache** dan **MySQL** di XAMPP berstatus *Running*.
2. Buka browser ke alamat berikut:

   ```
   http://localhost/peminjaman-ebook/FrontEnd/login.html
   ```

3. Login memakai akun demo:

| Role   | Email              | Password   |
|--------|--------------------|------------|
| Member | `member@umm.ac.id` | `member123`|
| Admin  | `admin@umm.ac.id`  | `admin123` |

4. Alur pemakaian **Member**:
   **Login → Dashboard (katalog) → klik Detail → tombol Pinjam →
   status peminjaman berhasil → kembali ke Dashboard → Logout.**

5. Alur pemakaian **Admin** (otomatis diarahkan ke Panel Admin setelah login):
   - **Kelola e-book**: tambah, ubah, dan hapus e-book (stok, kategori, dll.).
     E-book yang sedang dipinjam tidak bisa dihapus.
   - **Semua peminjaman**: pantau seluruh peminjaman semua user, filter
     Aktif / Terlambat / Selesai, dan tandai pengembalian atas nama peminjam.
   - Admin tidak meminjam e-book. Member yang membuka `admin.html` dialihkan
     ke dashboard, dan endpoint admin di API menolak non-admin dengan HTTP 403.

---

## 7. Alternatif: Pakai Laragon

Kalau di laptop sudah ada **Laragon**:

1. Buka Laragon → **Start All** (Apache + MySQL).
2. Ekstrak folder `peminjaman-ebook` ke `C:\laragon\www\`.
3. Import database (lewat terminal Laragon / HeidiSQL / phpMyAdmin) mengikuti **Langkah 4**.
4. Buka:

   ```
   http://localhost/peminjaman-ebook/FrontEnd/login.html
   ```

---

## 8. Alternatif: Tanpa Apache (PHP Built-in Server)

Jika sudah ada PHP 8.1+ dan MySQL, tapi tidak ingin memasang Apache:

```cmd
cd C:\xampp\htdocs\peminjaman-ebook
php -S localhost:8000
```

Lalu buka **http://localhost:8000/FrontEnd/login.html**

> MySQL tetap harus berjalan, karena aplikasi membaca data dari database.

---

## 9. Pemecahan Masalah (Troubleshooting)

| Gejala / Pesan Error | Penyebab & Solusi |
|----------------------|-------------------|
| `SQLSTATE[HY000] [2002] No connection could be made / Connection refused` | MySQL belum jalan. Start **MySQL** di XAMPP Control Panel. |
| `could not find driver` | Ekstensi `pdo_mysql` tidak aktif. (XAMPP sudah aktif default.) Bila bukan XAMPP, aktifkan `extension=pdo_mysql` di `php.ini` lalu restart. |
| `Unknown database 'peminjaman_ebook'` | Database belum di-import. Ulangi **Langkah 4**. |
| `Access denied for user 'root'@'localhost'` | Password MySQL salah/kosong. Sesuaikan `$pass` di `BackEnd\config\database.php` (**Langkah 5**). |
| Halaman **404 / Object not found** | Folder tidak berada di `htdocs` dengan nama tepat `peminjaman-ebook`, atau Apache belum di-start. |
| Apache **gagal start** (port 80 dipakai) | Tutup aplikasi yang memakai port 80 (Skype/IIS), atau ubah port Apache (mis. 8080) di XAMPP, lalu akses `http://localhost:8080/peminjaman-ebook/...`. |
| MySQL **gagal start** (port 3306 dipakai) | Ada MySQL lain yang berjalan. Hentikan, atau ubah port. |
| Error PHP: `syntax error` / `unexpected token "never"` / `"static"` | Versi PHP terlalu lama (< 8.1). Update XAMPP/PHP ke 8.1+. |
| Login selalu *"Email atau password salah"* | Data seed belum masuk. Pastikan `schema.sql` berhasil di-import; cek tabel `users` di phpMyAdmin. |
| Tombol/login tidak merespons, error jaringan di console | Aplikasi dibuka lewat `file://` (double-click). Harus dibuka lewat `http://localhost/...`. Pastikan `FrontEnd` & `BackEnd` tetap satu folder. |

---

## 10. Struktur Folder Proyek

```
peminjaman-ebook\
├── FrontEnd\
│   ├── login.html            Halaman login
│   ├── dashboard.html        Katalog e-book + riwayat
│   ├── peminjaman.html       Detail e-book + form pinjam
│   ├── admin.html            Panel admin (kelola e-book & semua peminjaman)
│   ├── css\
│   │   └── style.css         Seluruh tampilan (responsif)
│   └── js\
│       ├── app.js            Utilitas bersama (API, tema, toast, dialog, sampul)
│       ├── auth.js           Logika login & validasi
│       ├── admin.js          Panel admin
│       ├── dashboard.js      Katalog, pencarian, filter, riwayat
│       └── peminjaman.js     Detail & transaksi pinjam
└── BackEnd\
    ├── bootstrap.php         Autoloader (namespace App\ → src/)
    ├── config\
    │   └── database.php      Konfigurasi koneksi PDO/MySQL
    ├── public\
    │   └── api.php           REST API (satu pintu, routing via ?action=)
    ├── database\
    │   └── schema.sql        Skema tabel + data contoh (import ini)
    └── src\
        ├── Contracts\        Interface (Repository, dst.)
        ├── Models\           Model (Model, User, Ebook, Peminjaman)
        ├── Repositories\     Akses database (PDO)
        └── Services\         Logika bisnis (Auth, Ebook, Peminjaman, Admin)
```

---

## 11. Catatan

- Ini adalah **prototipe untuk UTS**, bukan untuk produksi. Kredensial demo dan
  password root kosong sebaiknya tidak dipakai di lingkungan nyata.
- Ingin mengulang dari data bersih? Import ulang `schema.sql` — perintah di dalamnya
  otomatis membuat ulang tabel dan mengisi data contoh.
- Ringkasan akun demo: **member@umm.ac.id / member123** dan **admin@umm.ac.id / admin123**.
