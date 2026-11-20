<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Ebook;

interface EbookRepositoryInterface extends RepositoryInterface
{
    public function findById(int $id): ?Ebook;

    /** @return Ebook[] */
    public function search(string $keyword = '', ?string $kategori = null): array;

    public function save(Ebook $ebook): Ebook;

    public function delete(int $id): bool;

    public function decrementStock(int $id): bool;

    public function incrementStock(int $id): bool;

    /** @return string[] */
    public function categories(): array;
}
