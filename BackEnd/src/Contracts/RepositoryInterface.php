<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Kontrak dasar (Polymorphism) untuk seluruh repository.
 * Semua repository wajib mengimplementasikan kontrak ini.
 */
interface RepositoryInterface
{
    /** @return object[] */
    public function all(): array;

    public function findById(int $id): ?object;
}
