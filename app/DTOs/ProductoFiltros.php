<?php

namespace App\DTOs;

/** Objeto de valor inmutable con los filtros del catálogo. */
final class ProductoFiltros
{
    public const ORDENES = ['recientes', 'precio_asc', 'precio_desc', 'nombre'];

    public function __construct(
        public readonly ?string $busqueda = null,
        public readonly ?int $categoriaId = null,
        public readonly ?int $marcaId = null,
        public readonly ?float $precioMin = null,
        public readonly ?float $precioMax = null,
        public readonly bool $soloDisponibles = false,
        public readonly string $orden = 'recientes',
    ) {}

    public static function desdeArray(array $d): self
    {
        $orden = in_array($d['orden'] ?? '', self::ORDENES, true) ? $d['orden'] : 'recientes';

        return new self(
            busqueda: filled($d['q'] ?? null) ? trim($d['q']) : null,
            categoriaId: filled($d['categoria'] ?? null) ? (int) $d['categoria'] : null,
            marcaId: filled($d['marca'] ?? null) ? (int) $d['marca'] : null,
            precioMin: filled($d['precio_min'] ?? null) ? (float) $d['precio_min'] : null,
            precioMax: filled($d['precio_max'] ?? null) ? (float) $d['precio_max'] : null,
            soloDisponibles: filter_var($d['disponible'] ?? false, FILTER_VALIDATE_BOOLEAN),
            orden: $orden,
        );
    }
}
