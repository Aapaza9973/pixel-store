<?php

namespace App\Contracts;

use App\DTOs\ProductoFiltros;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/** Lectura pública del catálogo. NUNCA modifica stock (eso es de InventoryService). */
interface ProductoRepositoryInterface
{
    public function paginarFiltrados(ProductoFiltros $filtros, int $porPagina = 12): LengthAwarePaginator;

    public function destacados(int $limite = 8): Collection;

    public function buscarPorIds(array $ids): Collection;

    /** Busca por slug o, si es numérico, por id. */
    public function buscarPorReferencia(string $ref): ?object;

    public function relacionados(int $productoId, int $categoriaId, int $limite = 4): Collection;

    /** @return array<int,string> URLs de la galería */
    public function galeria(int $productoId): array;

    /** @return array<int,array{nombre:string,valor:string}> */
    public function especificaciones(int $productoId): array;

    public function categorias(): Collection;

    public function marcas(): Collection;
}
