<?php

declare(strict_types=1);

/**
 * Front Controller / REST API (satu pintu masuk seluruh permintaan AJAX).
 *
 * Routing sederhana berbasis parameter `action` (GET/POST).
 * Mengembalikan respons JSON secara konsisten.
 */

require __DIR__ . '/../bootstrap.php';

use App\Repositories\EbookRepository;
use App\Repositories\PeminjamanRepository;
use App\Repositories\UserRepository;
use App\Services\AdminService;
use App\Services\AuthService;
use App\Services\EbookService;
use App\Services\PeminjamanService;

session_start();

function jsonResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** @return array{id: int, nama: string, role: string} */
function requireAuth(): array
{
    if (empty($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'message' => 'Silakan login terlebih dahulu.'], 401);
    }

    return [
        'id'   => (int) $_SESSION['user_id'],
        'nama' => (string) ($_SESSION['nama'] ?? ''),
        'role' => (string) ($_SESSION['role'] ?? 'member'),
    ];
}

/** Hanya admin. Pengecekan dilakukan di server, bukan hanya di tampilan. */
function requireAdmin(): array
{
    $me = requireAuth();

    if ($me['role'] !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Akses khusus admin.'], 403);
    }

    return $me;
}

function input(string $key, ?string $default = null): ?string
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;

    return $value === null ? null : trim((string) $value);
}

function requirePost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Metode request tidak diizinkan.'], 405);
    }
}

$db = require __DIR__ . '/../config/database.php';

$userRepo       = new UserRepository($db);
$ebookRepo      = new EbookRepository($db);
$peminjamanRepo = new PeminjamanRepository($db);

$authService      = new AuthService($userRepo);
$ebookService     = new EbookService($ebookRepo);
$peminjamanService = new PeminjamanService($db, $ebookRepo, $peminjamanRepo);
$adminService      = new AdminService($ebookRepo, $peminjamanRepo);

$action = input('action', '');

try {
    switch ($action) {
        case 'login':
            requirePost();
            $email    = input('email', '');
            $password = (string) ($_POST['password'] ?? '');

            $user = $authService->attempt($email, $password);

            if ($user === null) {
                jsonResponse(['success' => false, 'message' => 'Email atau password salah.'], 401);
            }

            $authService->login($user);
            jsonResponse([
                'success' => true,
                'message' => 'Login berhasil.',
                'user'    => $user->toArray(),
            ]);

        case 'logout':
            requirePost();
            $authService->logout();
            jsonResponse(['success' => true, 'message' => 'Logout berhasil.']);

        case 'me':
            jsonResponse(['success' => true, 'user' => requireAuth()]);

        case 'ebooks':
            $keyword = input('q', '');
            $kategori = input('kategori');
            $data = $ebookService->catalog($keyword, $kategori);
            jsonResponse([
                'success' => true,
                'data'    => array_map(static fn ($e) => $e->toArray(), $data),
            ]);

        case 'ebook':
            $id = (int) input('id', '0');
            $ebook = $ebookService->detail($id);

            if ($ebook === null) {
                jsonResponse(['success' => false, 'message' => 'E-book tidak ditemukan.'], 404);
            }

            jsonResponse(['success' => true, 'data' => $ebook->toArray()]);

        case 'categories':
            jsonResponse(['success' => true, 'data' => $ebookService->categories()]);

        case 'pinjam':
            $me = requireAuth();
            requirePost();

            if ($me['role'] === 'admin') {
                jsonResponse(['success' => false, 'message' => 'Akun admin tidak dapat meminjam e-book.'], 403);
            }

            $ebookId = (int) input('ebook_id', '0');
            $peminjaman = $peminjamanService->pinjam((int) $_SESSION['user_id'], $ebookId);
            jsonResponse([
                'success' => true,
                'message' => 'Peminjaman berhasil disimpan.',
                'data'    => $peminjaman->toArray(),
            ]);

        case 'peminjaman':
            $me = requireAuth();
            $data = $peminjamanService->riwayat($me['id']);
            jsonResponse([
                'success' => true,
                'data'    => array_map(static fn ($p) => $p->toArray(), $data),
            ]);

        case 'kembalikan':
            $me = requireAuth();
            requirePost();
            $id = (int) input('id', '0');
            $p = $peminjamanService->kembalikan($id, $me['id'], $me['role'] === 'admin');
            jsonResponse([
                'success' => true,
                'message' => 'E-book berhasil dikembalikan.',
                'data'    => $p->toArray(),
            ]);

        // ---------- Khusus admin ----------
        case 'admin_overview':
            requireAdmin();
            jsonResponse(['success' => true, 'data' => $adminService->overview()]);

        case 'admin_loans':
            requireAdmin();
            jsonResponse([
                'success' => true,
                'data'    => array_map(static fn ($p) => $p->toArray(), $adminService->allLoans()),
            ]);

        case 'admin_ebook_save':
            requireAdmin();
            requirePost();
            $id = (int) input('id', '0');
            $ebook = $adminService->saveEbook($id > 0 ? $id : null, [
                'judul'     => (string) input('judul', ''),
                'penulis'   => (string) input('penulis', ''),
                'kategori'  => (string) input('kategori', ''),
                'deskripsi' => (string) input('deskripsi', ''),
                'tahun'     => (string) input('tahun', '0'),
                'stok'      => (string) input('stok', '0'),
            ]);
            jsonResponse([
                'success' => true,
                'message' => $id > 0 ? 'E-book berhasil diperbarui.' : 'E-book berhasil ditambahkan.',
                'data'    => $ebook->toArray(),
            ]);

        case 'admin_ebook_delete':
            requireAdmin();
            requirePost();
            $adminService->deleteEbook((int) input('id', '0'));
            jsonResponse(['success' => true, 'message' => 'E-book berhasil dihapus.']);

        default:
            jsonResponse(['success' => false, 'message' => 'Aksi tidak dikenali.'], 404);
    }
} catch (RuntimeException $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'message' => 'Terjadi kesalahan pada server.'], 500);
}
