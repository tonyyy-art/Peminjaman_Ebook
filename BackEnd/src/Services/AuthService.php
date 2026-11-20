<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\UserRepositoryInterface;
use App\Models\User;

/**
 * Layanan autentikasi (login/logout + sesi).
 * Bergantung pada abstraksi UserRepositoryInterface (Polymorphism).
 */
class AuthService
{
    public function __construct(private UserRepositoryInterface $users)
    {
    }

    public function attempt(string $email, string $password): ?User
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || !$user->verifyPassword($password)) {
            return null;
        }

        return $user;
    }

    public function login(User $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['nama']    = $user->getNama();
        $_SESSION['role']    = $user->getRole();
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}
