<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Entitas Peminjaman (transaksi sewa/pinjam e-book).
 */
class Peminjaman extends Model
{
    private ?int $id = null;
    private int $userId = 0;
    private int $ebookId = 0;
    private string $tanggalPinjam = '';
    private string $tanggalJatuhTempo = '';
    private ?string $tanggalKembali = null;
    private string $status = 'dipinjam';

    /** @var array<string, mixed> info e-book hasil JOIN (untuk tampilan) */
    private array $ebook = [];

    /** @var array<string, mixed> info peminjam hasil JOIN (untuk panel admin) */
    private array $user = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getEbookId(): int
    {
        return $this->ebookId;
    }

    public function setEbookId(int $ebookId): void
    {
        $this->ebookId = $ebookId;
    }

    public function getTanggalPinjam(): string
    {
        return $this->tanggalPinjam;
    }

    public function setTanggalPinjam(string $tanggal): void
    {
        $this->tanggalPinjam = $tanggal;
    }

    public function getTanggalJatuhTempo(): string
    {
        return $this->tanggalJatuhTempo;
    }

    public function setTanggalJatuhTempo(string $tanggal): void
    {
        $this->tanggalJatuhTempo = $tanggal;
    }

    public function getTanggalKembali(): ?string
    {
        return $this->tanggalKembali;
    }

    public function setTanggalKembali(?string $tanggal): void
    {
        $this->tanggalKembali = $tanggal;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /** @return array<string, mixed> */
    public function getEbook(): array
    {
        return $this->ebook;
    }

    /** @param array<string, mixed> $ebook */
    public function setEbook(array $ebook): void
    {
        $this->ebook = $ebook;
    }

    /** @param array<string, mixed> $user */
    public function setUser(array $user): void
    {
        $this->user = $user;
    }

    public function isActive(): bool
    {
        return $this->status === 'dipinjam';
    }

    public function validate(): bool
    {
        $this->errors = [];

        if ($this->userId <= 0) {
            $this->addError('userId', 'User tidak valid.');
        }

        if ($this->ebookId <= 0) {
            $this->addError('ebookId', 'E-book tidak valid.');
        }

        return $this->errors === [];
    }

    public function toArray(): array
    {
        return [
            'id'                  => $this->id,
            'user_id'             => $this->userId,
            'ebook_id'            => $this->ebookId,
            'tanggal_pinjam'      => $this->tanggalPinjam,
            'tanggal_jatuh_tempo' => $this->tanggalJatuhTempo,
            'tanggal_kembali'     => $this->tanggalKembali,
            'status'              => $this->status,
            'ebook'               => $this->ebook,
            'user'                => $this->user,
        ];
    }

    public static function fromArray(array $data): static
    {
        $p = new static();
        $p->id                = isset($data['id']) ? (int) $data['id'] : null;
        $p->userId            = (int) ($data['user_id'] ?? 0);
        $p->ebookId           = (int) ($data['ebook_id'] ?? 0);
        $p->tanggalPinjam     = (string) ($data['tanggal_pinjam'] ?? '');
        $p->tanggalJatuhTempo = (string) ($data['tanggal_jatuh_tempo'] ?? '');
        $p->tanggalKembali    = isset($data['tanggal_kembali']) && $data['tanggal_kembali'] !== null
            ? (string) $data['tanggal_kembali']
            : null;
        $p->status            = (string) ($data['status'] ?? 'dipinjam');

        // Hasil JOIN: sertakan info e-book untuk ditampilkan.
        if (isset($data['ebook_judul'])) {
            $p->ebook = [
                'judul'   => $data['ebook_judul'],
                'penulis' => $data['ebook_penulis'] ?? '',
                'cover'   => $data['ebook_cover'] ?? '',
            ];
        }

        if (isset($data['user_nama'])) {
            $p->user = [
                'nama'  => $data['user_nama'],
                'email' => $data['user_email'] ?? '',
            ];
        }

        return $p;
    }
}
