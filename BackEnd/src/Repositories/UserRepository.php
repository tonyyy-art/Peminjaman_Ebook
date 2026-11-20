<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\Models\User;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /** @return User[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM users ORDER BY nama');
        $rows = $stmt->fetchAll();

        return array_map([User::class, 'fromArray'], $rows);
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? User::fromArray($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower(trim($email))]);
        $row = $stmt->fetch();

        return $row ? User::fromArray($row) : null;
    }

    public function save(User $user): User
    {
        if ($user->getId() !== null) {
            $stmt = $this->db->prepare(
                'UPDATE users SET nama = :nama, email = :email, role = :role WHERE id = :id'
            );
            $stmt->execute([
                'nama'  => $user->getNama(),
                'email' => $user->getEmail(),
                'role'  => $user->getRole(),
                'id'    => $user->getId(),
            ]);

            return $user;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO users (nama, email, password, role) VALUES (:nama, :email, :password, :role)'
        );
        $stmt->execute([
            'nama'     => $user->getNama(),
            'email'    => $user->getEmail(),
            'password' => $user->getPasswordHash(),
            'role'     => $user->getRole(),
        ]);
        $user->setId((int) $this->db->lastInsertId());

        return $user;
    }
}
