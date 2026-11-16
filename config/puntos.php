<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Puntos de fidelidad
|--------------------------------------------------------------------------
|
| Regla: 1 punto por cada `bs_por_punto` bolivianos cobrados. La devolución
| procesada revierte los puntos proporcionales al monto devuelto usando la
| misma regla.
|
*/

return [
    'bs_por_punto' => env('PUNTOS_BS_POR_PUNTO', 10),
];
