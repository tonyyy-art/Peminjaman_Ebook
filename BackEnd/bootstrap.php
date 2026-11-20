<?php

declare(strict_types=1);

/**
 * Bootstrap: PSR-4 style autoloader sederhana (tanpa Composer).
 *
 * Memetakan namespace `App\...` ke direktori `src/...`.
 * Contoh: App\Models\User  ->  src/Models/User.php
 */
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});
