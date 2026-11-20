<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Entitas E-book (katalog media yang dipinjam).
 */
class Ebook extends Model
{
    private ?int $id = null;
    private string $judul = '';
    private string $penulis = '';
    private string $kategori = '';
    private string $deskripsi = '';
    private int $tahun = 0;
    private string $cover = '';
    private int $stok = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getJudul(): string
    {
        return $this->judul;
    }

    public function setJudul(string $judul): void
    {
        $this->judul = trim($judul);
    }

    public function getPenulis(): string
    {
        return $this->penulis;
    }

    public function setPenulis(string $penulis): void
    {
        $this->penulis = trim($penulis);
    }

    public function getKategori(): string
    {
        return $this->kategori;
    }

    public function setKategori(string $kategori): void
    {
        $this->kategori = trim($kategori);
    }

    public function getDeskripsi(): string
    {
        return $this->deskripsi;
    }

    public function setDeskripsi(string $deskripsi): void
    {
        $this->deskripsi = trim($deskripsi);
    }

    public function getTahun(): int
    {
        return $this->tahun;
    }

    public function setTahun(int $tahun): void
    {
        $this->tahun = $tahun;
    }

    public function getCover(): string
    {
        return $this->cover;
    }

    public function setCover(string $cover): void
    {
        $this->cover = trim($cover);
    }

    public function getStok(): int
    {
        return $this->stok;
    }

    public function setStok(int $stok): void
    {
        $this->stok = $stok;
    }

    public function isAvailable(): bool
    {
        return $this->stok > 0;
    }

    public function validate(): bool
    {
        $this->errors = [];

        if ($this->judul === '') {
            $this->addError('judul', 'Judul wajib diisi.');
        }

        if ($this->penulis === '') {
            $this->addError('penulis', 'Penulis wajib diisi.');
        }

        if ($this->stok < 0) {
            $this->addError('stok', 'Stok tidak boleh negatif.');
        }

        return $this->errors === [];
    }

    public function toArray(): array
    {
        return [
            'id'        => $this->id,
            'judul'     => $this->judul,
            'penulis'   => $this->penulis,
            'kategori'  => $this->kategori,
            'deskripsi' => $this->deskripsi,
            'tahun'     => $this->tahun,
            'cover'     => $this->cover,
            'stok'      => $this->stok,
        ];
    }

    public static function fromArray(array $data): static
    {
        $ebook = new static();
        $ebook->id        = isset($data['id']) ? (int) $data['id'] : null;
        $ebook->judul     = (string) ($data['judul'] ?? '');
        $ebook->penulis   = (string) ($data['penulis'] ?? '');
        $ebook->kategori  = (string) ($data['kategori'] ?? '');
        $ebook->deskripsi = (string) ($data['deskripsi'] ?? '');
        $ebook->tahun     = (int) ($data['tahun'] ?? 0);
        $ebook->cover     = (string) ($data['cover'] ?? '');
        $ebook->stok      = (int) ($data['stok'] ?? 0);

        return $ebook;
    }
}
