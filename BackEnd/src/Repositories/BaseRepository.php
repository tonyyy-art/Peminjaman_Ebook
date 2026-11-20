<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Kelas abstrak induk repository (Inheritance).
 * Menyimpan koneksi PDO bersama untuk seluruh repository turunan.
 */
abstract class BaseRepository
{
    public function __construct(protected PDO $db)
    {
    }
}
