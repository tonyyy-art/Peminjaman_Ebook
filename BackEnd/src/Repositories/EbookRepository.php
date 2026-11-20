<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\EbookRepositoryInterface;
use App\Models\Ebook;

class EbookRepository extends BaseRepository implements EbookRepositoryInterface
{
    /** @return Ebook[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM ebooks ORDER BY judul');
        $rows = $stmt->fetchAll();

        return array_map([Ebook::class, 'fromArray'], $rows);
    }

    public function findById(int $id): ?Ebook
    {
        $stmt = $this->db->prepare('SELECT * FROM ebooks WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Ebook::fromArray($row) : null;
    }

    /** @return Ebook[] */
    public function search(string $keyword = '', ?string $kategori = null): array
    {
        $sql = 'SELECT * FROM ebooks WHERE 1 = 1';
        $params = [];

        if ($keyword !== '') {
            $sql .= ' AND (judul LIKE :kw OR penulis LIKE :kw)';
            $params['kw'] = '%' . $keyword . '%';
        }

        if ($kategori !== null && $kategori !== '') {
            $sql .= ' AND kategori = :kategori';
            $params['kategori'] = $kategori;
        }

        $sql .= ' ORDER BY judul';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return array_map([Ebook::class, 'fromArray'], $rows);
    }

    public function save(Ebook $ebook): Ebook
    {
        if ($ebook->getId() !== null) {
            $stmt = $this->db->prepare(
                'UPDATE ebooks SET judul = :judul, penulis = :penulis, kategori = :kategori,
                        deskripsi = :deskripsi, tahun = :tahun, cover = :cover, stok = :stok
                 WHERE id = :id'
            );
            $stmt->execute([
                'judul'     => $ebook->getJudul(),
                'penulis'   => $ebook->getPenulis(),
                'kategori'  => $ebook->getKategori(),
                'deskripsi' => $ebook->getDeskripsi(),
                'tahun'     => $ebook->getTahun(),
                'cover'     => $ebook->getCover(),
                'stok'      => $ebook->getStok(),
                'id'        => $ebook->getId(),
            ]);

            return $ebook;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO ebooks (judul, penulis, kategori, deskripsi, tahun, cover, stok)
             VALUES (:judul, :penulis, :kategori, :deskripsi, :tahun, :cover, :stok)'
        );
        $stmt->execute([
            'judul'     => $ebook->getJudul(),
            'penulis'   => $ebook->getPenulis(),
            'kategori'  => $ebook->getKategori(),
            'deskripsi' => $ebook->getDeskripsi(),
            'tahun'     => $ebook->getTahun(),
            'cover'     => $ebook->getCover(),
            'stok'      => $ebook->getStok(),
        ]);
        $ebook->setId((int) $this->db->lastInsertId());

        return $ebook;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM ebooks WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function decrementStock(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE ebooks SET stok = stok - 1 WHERE id = :id AND stok > 0');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function incrementStock(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE ebooks SET stok = stok + 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /** @return string[] */
    public function categories(): array
    {
        $stmt = $this->db->query('SELECT DISTINCT kategori FROM ebooks ORDER BY kategori');
        $rows = $stmt->fetchAll();

        return array_map(static fn (array $r): string => (string) $r['kategori'], $rows);
    }
}
