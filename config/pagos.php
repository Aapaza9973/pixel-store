<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Pagos en línea
|--------------------------------------------------------------------------
|
| `modo` = simulado  → el botón de pago aprueba el pedido sin cobrar de verdad.
| `modo` = produccion → requiere las claves del proveedor (Stripe / PayPal),
|                       que todavía no están integradas; el pago devuelve 404.
|
*/

return [
    'modo' => env('PAGOS_MODO', 'simulado'),

    'proveedor' => env('PAGOS_PROVEEDOR'),
];
