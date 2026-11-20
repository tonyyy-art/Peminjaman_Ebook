<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;
use Throwable;
use App\Contracts\EbookRepositoryInterface;
use App\Contracts\PeminjamanRepositoryInterface;
use App\Models\Peminjaman;

/**
 * Layanan transaksi peminjaman.
 * Menggunakan transaksi database (atomic) saat menyimpan peminjaman
 * sekaligus mengurangi stok e-book.
 */
class PeminjamanService
{
    public function __construct(
        private PDO $db,
        private EbookRepositoryInterface $ebooks,
        private PeminjamanRepositoryInterface $peminjaman
    ) {
    }

    public function pinjam(int $userId, int $ebookId, int $durasiHari = 7): Peminjaman
    {
        $ebook = $this->ebooks->findById($ebookId);

        if ($ebook === null) {
            throw new RuntimeException('E-book tidak ditemukan.');
        }

        if (!$ebook->isAvailable()) {
            throw new RuntimeException('Stok e-book sedang habis.');
        }

        if ($this->peminjaman->isBorrowed($userId, $ebookId)) {
            throw new RuntimeException('Anda masih meminjam e-book ini.');
        }

        $p = new Peminjaman();
        $p->setUserId($userId);
        $p->setEbookId($ebookId);
        $p->setTanggalPinjam(date('Y-m-d'));
        $p->setTanggalJatuhTempo(date('Y-m-d', strtotime("+{$durasiHari} days")));
        $p->setStatus('dipinjam');

        $this->db->beginTransaction();

        try {
            $saved = $this->peminjaman->save($p);
            $this->ebooks->decrementStock($ebookId);
            $this->db->commit();

            return $saved;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw new RuntimeException('Transaksi peminjaman gagal: ' . $e->getMessage());
        }
    }

    /** @return Peminjaman[] */
    public function riwayat(int $userId): array
    {
        return $this->peminjaman->findByUser($userId);
    }

    /**
     * Pengembalian hanya boleh oleh pemilik peminjaman, atau admin
     * (mis. membantu mengembalikan atas nama peminjam).
     */
    public function kembalikan(int $peminjamanId, int $actorId, bool $actorIsAdmin = false): Peminjaman
    {
        $p = $this->peminjaman->findById($peminjamanId);

        if ($p === null) {
            throw new RuntimeException('Data peminjaman tidak ditemukan.');
        }

        if (!$actorIsAdmin && $p->getUserId() !== $actorId) {
            throw new RuntimeException('Anda tidak berhak mengembalikan peminjaman ini.');
        }

        if (!$p->isActive()) {
            throw new RuntimeException('Peminjaman ini sudah dikembalikan.');
        }

        $this->db->beginTransaction();

        try {
            $this->peminjaman->updateStatus($peminjamanId, 'dikembalikan', date('Y-m-d'));
            $this->ebooks->incrementStock($p->getEbookId());
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw new RuntimeException('Transaksi pengembalian gagal: ' . $e->getMessage());
        }

        $p->setStatus('dikembalikan');
        $p->setTanggalKembali(date('Y-m-d'));

        return $p;
    }
}
