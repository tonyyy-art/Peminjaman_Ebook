<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\PeminjamanRepositoryInterface;
use App\Models\Peminjaman;

class PeminjamanRepository extends BaseRepository implements PeminjamanRepositoryInterface
{
    /** @return Peminjaman[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM peminjaman ORDER BY created_at DESC');
        $rows = $stmt->fetchAll();

        return array_map([Peminjaman::class, 'fromArray'], $rows);
    }

    public function findById(int $id): ?Peminjaman
    {
        $stmt = $this->db->prepare('SELECT * FROM peminjaman WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Peminjaman::fromArray($row) : null;
    }

    /** @return Peminjaman[] */
    public function findByUser(int $userId): array
    {
        $sql = 'SELECT p.*, e.judul AS ebook_judul, e.penulis AS ebook_penulis, e.cover AS ebook_cover
                FROM peminjaman p
                JOIN ebooks e ON e.id = p.ebook_id
                WHERE p.user_id = :user_id
                ORDER BY p.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll();

        return array_map([Peminjaman::class, 'fromArray'], $rows);
    }

    /** @return Peminjaman[] */
    public function findAllWithDetails(): array
    {
        $sql = 'SELECT p.*, e.judul AS ebook_judul, e.penulis AS ebook_penulis, e.cover AS ebook_cover,
                       u.nama AS user_nama, u.email AS user_email
                FROM peminjaman p
                JOIN ebooks e ON e.id = p.ebook_id
                JOIN users u ON u.id = p.user_id
                ORDER BY p.created_at DESC, p.id DESC';
        $rows = $this->db->query($sql)->fetchAll();

        return array_map([Peminjaman::class, 'fromArray'], $rows);
    }

    public function hasActiveForEbook(int $ebookId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS total FROM peminjaman WHERE ebook_id = :ebook_id AND status = :status'
        );
        $stmt->execute(['ebook_id' => $ebookId, 'status' => 'dipinjam']);
        $row = $stmt->fetch();

        return (int) $row['total'] > 0;
    }

    public function save(Peminjaman $peminjaman): Peminjaman
    {
        $stmt = $this->db->prepare(
            'INSERT INTO peminjaman (user_id, ebook_id, tanggal_pinjam, tanggal_jatuh_tempo, status)
             VALUES (:user_id, :ebook_id, :tanggal_pinjam, :tanggal_jatuh_tempo, :status)'
        );
        $stmt->execute([
            'user_id'             => $peminjaman->getUserId(),
            'ebook_id'            => $peminjaman->getEbookId(),
            'tanggal_pinjam'      => $peminjaman->getTanggalPinjam(),
            'tanggal_jatuh_tempo' => $peminjaman->getTanggalJatuhTempo(),
            'status'              => $peminjaman->getStatus(),
        ]);
        $peminjaman->setId((int) $this->db->lastInsertId());

        return $peminjaman;
    }

    public function updateStatus(int $id, string $status, ?string $tanggalKembali = null): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE peminjaman SET status = :status, tanggal_kembali = :tanggal_kembali WHERE id = :id'
        );
        $stmt->execute([
            'status'          => $status,
            'tanggal_kembali' => $tanggalKembali,
            'id'              => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function isBorrowed(int $userId, int $ebookId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS total FROM peminjaman
             WHERE user_id = :user_id AND ebook_id = :ebook_id AND status = :status'
        );
        $stmt->execute([
            'user_id'  => $userId,
            'ebook_id' => $ebookId,
            'status'   => 'dipinjam',
        ]);
        $row = $stmt->fetch();

        return (int) $row['total'] > 0;
    }
}
