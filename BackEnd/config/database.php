<?php

declare(strict_types=1);

/**
 * Konfigurasi koneksi database (PDO + MySQL).
 *
 * File ini mengembalikan (return) sebuah instance PDO yang siap pakai,
 * sehingga tidak mencemari namespace global dengan variabel.
 */
$host = '127.0.0.1';
$port = 3306;
$name = 'peminjaman_ebook';
$user = 'root';
$pass = '';

return new PDO(
    "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
    $user,
    $pass,
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
);
