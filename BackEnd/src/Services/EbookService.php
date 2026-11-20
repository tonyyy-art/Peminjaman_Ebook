<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\EbookRepositoryInterface;
use App\Models\Ebook;

/**
 * Layanan katalog e-book (pencarian, detail, kategori).
 */
class EbookService
{
    public function __construct(private EbookRepositoryInterface $ebooks)
    {
    }

    /** @return Ebook[] */
    public function catalog(string $keyword = '', ?string $kategori = null): array
    {
        return $this->ebooks->search($keyword, $kategori);
    }

    public function detail(int $id): ?Ebook
    {
        return $this->ebooks->findById($id);
    }

    /** @return string[] */
    public function categories(): array
    {
        return $this->ebooks->categories();
    }
}
