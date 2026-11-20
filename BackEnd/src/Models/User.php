<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Entitas User (Member/Admin).
 *
 * Menerapkan Encapsulation: seluruh properti berstatus private,
 * akses hanya melalui getter/setter.
 */
class User extends Model
{
    private ?int $id = null;
    private string $nama = '';
    private string $email = '';
    private string $passwordHash = '';
    private string $role = 'member';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getNama(): string
    {
        return $this->nama;
    }

    public function setNama(string $nama): void
    {
        $this->nama = trim($nama);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = strtolower(trim($email));
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $hash): void
    {
        $this->passwordHash = $hash;
    }

    public function setPassword(string $plain): void
    {
        $this->passwordHash = password_hash($plain, PASSWORD_DEFAULT);
    }

    public function verifyPassword(string $plain): bool
    {
        return password_verify($plain, $this->passwordHash);
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function validate(): bool
    {
        $this->errors = [];

        if ($this->nama === '') {
            $this->addError('nama', 'Nama wajib diisi.');
        }

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('email', 'Format email tidak valid.');
        }

        return $this->errors === [];
    }

    public function toArray(): array
    {
        return [
            'id'    => $this->id,
            'nama'  => $this->nama,
            'email' => $this->email,
            'role'  => $this->role,
        ];
    }

    public static function fromArray(array $data): static
    {
        $user = new static();
        $user->id           = isset($data['id']) ? (int) $data['id'] : null;
        $user->nama         = (string) ($data['nama'] ?? '');
        $user->email        = (string) ($data['email'] ?? '');
        $user->passwordHash = (string) ($data['password'] ?? '');
        $user->role         = (string) ($data['role'] ?? 'member');

        return $user;
    }
}
