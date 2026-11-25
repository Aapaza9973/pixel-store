<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Impide que vuelvan a entrar vistas con la paleta legacy (slate/blue/gray).
 * La lista de excepciones es la deuda pendiente de migración: solo puede
 * achicarse. Si tocás una vista de la lista, quitá su entrada.
 */
class LegacyPaletteGuardTest extends TestCase
{
    /** Vistas que todavía no se migraron a los tokens Obsidian. */
    private const PENDIENTES = [
        // Documentos imprimibles: usan grises neutros a propósito (contraste en
        // impresora térmica y A4), no tokens de pantalla.
        'admin/ventas/comprobante-a4.blade.php',
        'admin/ventas/comprobante-ticket.blade.php',

    ];

    public function test_ninguna_vista_fuera_de_la_lista_usa_la_paleta_legacy(): void
    {
        $ofensoras = [];

        foreach (File::allFiles(resource_path('views')) as $archivo) {
            $ruta = str_replace('\\', '/', $archivo->getRelativePathname());

            if (in_array($ruta, self::PENDIENTES, true)) {
                continue;
            }

            if (preg_match('/\b(?:slate|blue|gray|zinc|neutral|stone)-\d{2,3}\b/', File::get($archivo->getPathname()))) {
                $ofensoras[] = $ruta;
            }
        }

        $this->assertSame([], $ofensoras, 'Vistas con paleta legacy fuera de la lista de pendientes: '.implode(', ', $ofensoras));
    }

    public function test_la_lista_de_pendientes_solo_contiene_vistas_que_existen(): void
    {
        $inexistentes = array_values(array_filter(
            self::PENDIENTES,
            static fn (string $ruta): bool => ! File::exists(resource_path('views/'.$ruta)),
        ));

        $this->assertSame([], $inexistentes, 'La lista de pendientes menciona vistas borradas: '.implode(', ', $inexistentes));
    }
}
