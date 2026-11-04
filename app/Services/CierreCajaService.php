<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\CierreCaja;
use App\Models\CierreCajaPago;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cierre de caja (HU-6.1): cálculo del esperado del día y registro del cierre.
 *
 * Una venta cuenta como cobrada si su estado es 'Pagado', 'parcialmente_devuelta'
 * o 'totalmente_devuelta': en los tres casos el dinero entró a caja. `Pendiente`
 * y `Cancelada` quedan fuera. Solo se suman los pagos en estado 'Completado'.
 */
class CierreCajaService
{
    /** Estados de venta en los que el dinero ya fue cobrado. */
    private const ESTADOS_COBRADOS = ['Pagado', 'parcialmente_devuelta', 'totalmente_devuelta'];

    /** Métodos del enum_pago_metodo, en el orden en que se muestran. */
    public const METODOS = ['Efectivo', 'Tarjeta', 'Transferencia', 'QR', 'Otro'];

    /** Turnos válidos (coinciden con el CHECK de cierres_caja.turno). */
    public const TURNOS = ['mañana', 'tarde', 'noche'];

    /**
     * Lo que el sistema espera recaudar en una fecha.
     *
     * @return array{
     *     fecha: string,
     *     por_metodo: array<string, float>,
     *     total_ventas: float,
     *     total_devoluciones: float,
     *     efectivo_esperado: float
     * }
     */
    public function calcularDelDia(Carbon|string $fecha, ?int $userId = null): array
    {
        $dia = $this->normalizarFecha($fecha);

        $sumas = DB::table('pagos')
            ->join('ventas', 'ventas.id', '=', 'pagos.venta_id')
            ->whereIn('ventas.estado', self::ESTADOS_COBRADOS)
            ->where('pagos.estado', 'Completado')
            ->whereDate('ventas.fecha', $dia)
            ->when($userId !== null, fn ($query) => $query->where('ventas.user_id', $userId))
            ->groupBy('pagos.metodo')
            ->selectRaw('pagos.metodo, sum(pagos.monto) as total')
            ->pluck('total', 'metodo');

        $porMetodo = [];
        $totalVentas = 0.0;

        // Todos los métodos aparecen, aunque sea en cero: el cierre guarda una
        // línea por método y así el desglose siempre tiene la misma forma.
        foreach (self::METODOS as $metodo) {
            $monto = round((float) ($sumas[$metodo] ?? 0), 2);
            $porMetodo[$metodo] = $monto;
            $totalVentas += $monto;
        }

        // El reembolso sale del cajón, así que se descuenta del efectivo.
        // No se filtra por vendedor: puede haberlo registrado otro usuario, y
        // el dinero ya salió de la caja del día. Las anuladas no cuentan.
        $totalDevoluciones = round((float) DB::table('devoluciones')
            ->where('estado', '!=', 'anulada')
            ->whereDate('created_at', $dia)
            ->sum('total_devuelto'), 2);

        return [
            'fecha' => $dia,
            'por_metodo' => $porMetodo,
            'total_ventas' => round($totalVentas, 2),
            'total_devoluciones' => $totalDevoluciones,
            'efectivo_esperado' => round($porMetodo['Efectivo'] - $totalDevoluciones, 2),
        ];
    }

    /**
     * Registra el cierre de un turno con los montos contados en caja.
     *
     * @param  array<string, float|int|string>  $montosReales  claves por método
     *                                                         ('efectivo', 'Tarjeta', ...)
     *
     * @throws DomainException si el turno no es válido o ya existe el cierre
     */
    public function cerrar(
        int $userId,
        Carbon|string $fecha,
        string $turno,
        array $montosReales,
        ?string $notas = null
    ): CierreCaja {
        if (! in_array($turno, self::TURNOS, true)) {
            throw new DomainException("El turno «{$turno}» no es válido.");
        }

        $dia = $this->normalizarFecha($fecha);

        return DB::transaction(function () use ($userId, $dia, $turno, $montosReales, $notas): CierreCaja {
            $datos = $this->calcularDelDia($dia, $userId);
            $esperado = $datos['efectivo_esperado'];
            $real = $this->montoReal($montosReales, 'Efectivo');

            try {
                $cierre = CierreCaja::create([
                    'user_id' => $userId,
                    'fecha' => $dia,
                    'turno' => $turno,
                    'efectivo_esperado' => $esperado,
                    'efectivo_real' => $real,
                    'diferencia' => round($real - $esperado, 2),
                    'notas' => $notas,
                ]);
            } catch (UniqueConstraintViolationException) {
                // UNIQUE (user_id, fecha, turno): el turno ya fue cerrado.
                throw new DomainException('Ya existe un cierre para este turno.');
            }

            foreach ($datos['por_metodo'] as $metodo => $montoSistema) {
                CierreCajaPago::create([
                    'cierre_caja_id' => $cierre->id,
                    'metodo' => $metodo,
                    'monto_sistema' => $montoSistema,
                    'monto_real' => $this->montoReal($montosReales, $metodo),
                ]);
            }

            return $cierre->load('pagos');
        });
    }

    private function normalizarFecha(Carbon|string $fecha): string
    {
        return $fecha instanceof Carbon ? $fecha->toDateString() : Carbon::parse($fecha)->toDateString();
    }

    /**
     * Acepta la clave como la manda el formulario ('efectivo') o con el nombre
     * exacto del método del enum ('Efectivo').
     *
     * @param  array<string, float|int|string>  $montos
     */
    private function montoReal(array $montos, string $metodo): float
    {
        $valor = $montos[$metodo] ?? $montos[strtolower($metodo)] ?? 0;

        return round((float) $valor, 2);
    }
}
