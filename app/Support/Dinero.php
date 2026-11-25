<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Formato único de dinero en el sistema: `Bs 1.234,56`.
 * Punto para miles, coma para decimales, siempre dos decimales.
 */
final class Dinero
{
    public static function numero(float|int|string|null $monto): string
    {
        return number_format((float) ($monto ?? 0), 2, ',', '.');
    }

    public static function formatear(float|int|string|null $monto): string
    {
        return 'Bs '.self::numero($monto);
    }
}
