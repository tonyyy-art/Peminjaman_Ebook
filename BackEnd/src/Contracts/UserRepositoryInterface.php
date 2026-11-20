<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function save(User $user): User;
}
