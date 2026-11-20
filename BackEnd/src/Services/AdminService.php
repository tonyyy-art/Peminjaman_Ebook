<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use App\Contracts\EbookRepositoryInterface;
use App\Contracts\PeminjamanRepositoryInterface;
use App\Models\Ebook;
use App\Models\Peminjaman;

/**
 * Layanan khusus admin: kelola katalog e-book & pantau seluruh peminjaman.
 * Pengecekan role dilakukan di api.php (requireAdmin) sebelum service ini dipanggil.
 */
class AdminService
{
    public function __construct(
        private EbookRepositoryInterface $ebooks,
        private PeminjamanRepositoryInterface $peminjaman
    ) {
    }

    /** @return array{total_ebook: int, total_stok: int, dipinjam: int, terlambat: int} */
    public function overview(): array
    {
        $ebooks = $this->ebooks->all();
        $today  = date('Y-m-d');
        $aktif  = array_filter($this->peminjaman->findAllWithDetails(), static fn (Peminjaman $p): bool => $p->isActive());

        return [
            'total_ebook' => count($ebooks),
            'total_stok'  => array_sum(array_map(static fn (Ebook $e): int => $e->getStok(), $ebooks)),
            'dipinjam'    => count($aktif),
            'terlambat'   => count(array_filter($aktif, static fn (Peminjaman $p): bool => $p->getTanggalJatuhTempo() < $today)),
        ];
    }

    /** @return Peminjaman[] */
    public function allLoans(): array
    {
        return $this->peminjaman->findAllWithDetails();
    }

    /** @param array<string, string> $data */
    public function saveEbook(?int $id, array $data): Ebook
    {
        $ebook = new Ebook();

        if ($id !== null) {
            $ebook = $this->ebooks->findById($id) ?? throw new RuntimeException('E-book tidak ditemukan.');
        }

        $ebook->setJudul($data['judul'] ?? '');
        $ebook->setPenulis($data['penulis'] ?? '');
        $ebook->setKategori($data['kategori'] ?? '');
        $ebook->setDeskripsi($data['deskripsi'] ?? '');
        $ebook->setTahun((int) ($data['tahun'] ?? 0));
        $ebook->setStok((int) ($data['stok'] ?? 0));

        $errors = [];
        if (!$ebook->validate()) {
            foreach ($ebook->getErrors() as $messages) {
                $errors = array_merge($errors, $messages);
            }
        }
        if ($ebook->getKategori() === '') {
            $errors[] = 'Kategori wajib diisi.';
        }
        if ($ebook->getTahun() < 1900 || $ebook->getTahun() > (int) date('Y') + 1) {
            $errors[] = 'Tahun terbit tidak valid.';
        }
        if ($ebook->getStok() > 100000) {
            $errors[] = 'Stok terlalu besar.';
        }
        if ($errors !== []) {
            throw new RuntimeException(implode(' ', $errors));
        }

        return $this->ebooks->save($ebook);
    }

    public function deleteEbook(int $id): void
    {
        if ($this->ebooks->findById($id) === null) {
            throw new RuntimeException('E-book tidak ditemukan.');
        }

        if ($this->peminjaman->hasActiveForEbook($id)) {
            throw new RuntimeException('E-book masih dipinjam, selesaikan pengembaliannya dulu.');
        }

        $this->ebooks->delete($id);
    }
}
