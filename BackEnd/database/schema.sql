-- ============================================================
--  Skema Database: Sistem Peminjaman E-book
--  UTS Pemrograman Web - Semester Ganjil 2026/2027
-- ============================================================

CREATE DATABASE IF NOT EXISTS peminjaman_ebook
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE peminjaman_ebook;

-- ------------------------------------------------------------
-- Tabel users (User/Member)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS peminjaman;
DROP TABLE IF EXISTS ebooks;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('admin', 'member') NOT NULL DEFAULT 'member',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Tabel ebooks (katalog media)
-- ------------------------------------------------------------
CREATE TABLE ebooks (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    judul      VARCHAR(200) NOT NULL,
    penulis    VARCHAR(120) NOT NULL,
    kategori   VARCHAR(80)  NOT NULL,
    deskripsi  TEXT,
    tahun      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    cover      VARCHAR(255) NOT NULL DEFAULT '',
    stok       INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Tabel peminjaman (transaksi sewa/pinjam)
-- ------------------------------------------------------------
CREATE TABLE peminjaman (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED NOT NULL,
    ebook_id            INT UNSIGNED NOT NULL,
    tanggal_pinjam      DATE NOT NULL,
    tanggal_jatuh_tempo DATE NOT NULL,
    tanggal_kembali     DATE NULL DEFAULT NULL,
    status              ENUM('dipinjam', 'dikembalikan') NOT NULL DEFAULT 'dipinjam',
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_peminjaman_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    CONSTRAINT fk_peminjaman_ebook FOREIGN KEY (ebook_id) REFERENCES ebooks(id) ON DELETE CASCADE
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Seed: users
--   admin  / admin@umm.ac.id  / admin123
--   member / member@umm.ac.id / member123
-- ------------------------------------------------------------
INSERT INTO users (nama, email, password, role) VALUES
('Administrator', 'admin@umm.ac.id',  '$2y$10$GiWxyXKzDp3wa4wmws/rHuTXyZQ.Ca9FmlHoXbEj8f5ukuKBZAUvO', 'admin'),
('Budi Santoso',  'member@umm.ac.id', '$2y$10$aiFKoPOAWYknH75/u/SDe.Q8Q7P5nInlKpoLGBT1in5exDzUFNP0y', 'member');

-- ------------------------------------------------------------
-- Seed: ebooks
-- ------------------------------------------------------------
INSERT INTO ebooks (judul, penulis, kategori, deskripsi, tahun, cover, stok) VALUES
('Pemrograman Web Modern dengan PHP 8', 'Rian Hidayat', 'Pemrograman Web', 'Panduan lengkap membangun aplikasi web dengan PHP 8 berorientasi objek, mencakup namespace, interface, trait, dan praktik terbaik.', 2024, '', 5),
('Algoritma dan Struktur Data', 'Siti Nurhaliza', 'Pemrograman', 'Konsep dasar algoritma, kompleksitas, dan struktur data seperti array, linked list, stack, queue, tree, dan graph disertai contoh kode.', 2023, '', 4),
('Basis Data Relasional dengan MySQL', 'Andi Wijaya', 'Database', 'Perancangan basis data, normalisasi, SQL lanjutan, indeks, transaksi, dan optimasi query menggunakan MySQL 8.', 2024, '', 3),
('Pengantar Kecerdasan Buatan', 'Dewi Lestari', 'Kecerdasan Buatan', 'Pengenalan machine learning, deep learning, dan penerapan AI pada berbagai domain disertai studi kasus praktis.', 2023, '', 6),
('Jaringan Komputer untuk Pemula', 'Fajar Nugroho', 'Jaringan', 'Memahami model OSI, TCP/IP, subnetting, routing, dan konfigurasi jaringan dasar dengan bahasa yang mudah dipahami.', 2022, '', 4),
('Keamanan Aplikasi Web', 'Maya Anggraini', 'Keamanan', 'Teknik pengamanan aplikasi web: OWASP Top 10, SQL injection, XSS, CSRF, autentikasi, dan manajemen sesi.', 2024, '', 2),
('Belajar JavaScript dari Nol', 'Yoga Pratama', 'Pemrograman Web', 'Dasar-dasar JavaScript modern (ES6+), DOM manipulation, fetch API, dan interaksi antarmuka yang responsif.', 2023, '', 7),
('Cloud Computing dan DevOps', 'Rina Marlina', 'Infrastruktur', 'Konsep cloud computing, virtualisasi, container dengan Docker, CI/CD, serta orkestrasi layanan berbasis cloud.', 2024, '', 3);
