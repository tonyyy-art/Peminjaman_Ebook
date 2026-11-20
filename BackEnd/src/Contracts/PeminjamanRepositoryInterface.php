<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Peminjaman;

interface PeminjamanRepositoryInterface extends RepositoryInterface
{
    public function findById(int $id): ?Peminjaman;

    /** @return Peminjaman[] */
    public function findByUser(int $userId): array;

    /** @return Peminjaman[] semua transaksi + info peminjam & e-book (admin) */
    public function findAllWithDetails(): array;

    public function hasActiveForEbook(int $ebookId): bool;

    public function save(Peminjaman $peminjaman): Peminjaman;

    public function updateStatus(int $id, string $status, ?string $tanggalKembali = null): bool;

    public function isBorrowed(int $userId, int $ebookId): bool;
}
