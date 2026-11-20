<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Kelas abstrak induk (Inheritance).
 *
 * Setiap model wajib menurunkan kelas ini dan mengimplementasikan:
 *  - validate() : aturan validasi domain
 *  - toArray()  : serialisasi untuk respons JSON
 *  - fromArray(): hidrasi dari baris database
 */
abstract class Model
{
    protected array $errors = [];

    abstract public function validate(): bool;

    /** @return array<string, mixed> */
    abstract public function toArray(): array;

    abstract public static function fromArray(array $data): static;

    /** @return array<string, string[]> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function isValid(): bool
    {
        return $this->validate();
    }
}
