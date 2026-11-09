<?php

namespace App\Contracts;

use App\DTOs\CartLine;
use Illuminate\Support\Collection;

interface CartInterface
{
    public function add(int $productoId, int $cantidad = 1): void;

    public function update(int $productoId, int $cantidad): void;

    public function remove(int $productoId): void;

    public function clear(): void;

    /** @return Collection<int, CartLine> */
    public function lines(): Collection;

    public function count(): int;

    public function total(): float;
}
