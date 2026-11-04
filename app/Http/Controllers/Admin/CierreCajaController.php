<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCierreCajaRequest;
use App\Models\CierreCaja;
use App\Models\User;
use App\Models\Venta;
use App\Services\CierreCajaService;
use App\Services\ReporteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Throwable;

/**
 * Cierre de caja (HU-6.1).
 *
 * Autorización por rol: Admin y Cajero. El Vendedor también tiene los permisos
 * 'ver caja'/'crear caja' en el RoleSeeder, así que esos permisos no alcanzan
 * para gatear el módulo: se usa hasAnyRole. El permiso 'cerrar cajas' no existe
 * y no se inventó.
 *
 * Regla 6.1.9: quien no es Admin solo cierra su propio turno.
 */
class CierreCajaController extends Controller
{
    /**
     * Listado de cierres de un día con sus totales.
     */
    public function index(Request $request, CierreCajaService $caja): View
    {
        $this->autorizarCaja();

        $fecha = $this->fecha($request->string('fecha')->toString());
        // El Admin ve todos los cierres; el Cajero solo los propios.
        $propietario = auth()->user()->hasRole('Admin') ? null : auth()->id();

        $cierres = CierreCaja::query()
            ->with(['user:id,name', 'pagos'])
            ->whereDate('fecha', $fecha)
            ->when($propietario !== null, fn ($query) => $query->where('user_id', $propietario))
            ->orderBy('turno')
            ->get();

        $resumen = $caja->calcularDelDia($fecha, $propietario);
        $diferenciaTotal = round((float) $cierres->sum(fn (CierreCaja $cierre) => (float) $cierre->diferencia), 2);

        return view('admin.caja.index', compact('fecha', 'cierres', 'resumen', 'diferenciaTotal'));
    }

    /**
     * Formulario de cierre: los montos del sistema se recalculan al elegir
     * fecha o vendedor (botón "Recalcular", que reenvía por GET).
     */
    public function create(Request $request, CierreCajaService $caja): View
    {
        $usuario = $this->autorizarCaja();
        $esAdmin = $usuario->hasRole('Admin');

        $fecha = $this->fecha($request->string('fecha')->toString());
        $userId = $esAdmin && $request->filled('user_id')
            ? (int) $request->input('user_id')
            : $usuario->id;

        $turno = $request->string('turno')->toString();
        if (! in_array($turno, CierreCajaService::TURNOS, true)) {
            $turno = CierreCajaService::TURNOS[0];
        }

        $resumen = $caja->calcularDelDia($fecha, $userId);

        // Solo se piden los métodos con algo que contar; si no hubo ventas se
        // muestra Efectivo para que el cierre siga siendo posible.
        $montos = array_filter($resumen['por_metodo'], fn (float $monto): bool => $monto > 0);
        if ($montos === []) {
            $montos = ['Efectivo' => 0.0];
        }

        $turnosCerrados = CierreCaja::query()
            ->where('user_id', $userId)
            ->whereDate('fecha', $fecha)
            ->pluck('turno')
            ->all();

        $vendedores = $esAdmin
            ? User::query()
                ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Admin', 'Cajero']))
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        $vendedorSeleccionado = $vendedores->firstWhere('id', $userId)?->name;

        return view('admin.caja.create', compact(
            'fecha', 'turno', 'userId', 'esAdmin', 'resumen', 'montos',
            'turnosCerrados', 'vendedores', 'vendedorSeleccionado'
        ));
    }

    /**
     * Registra el cierre del turno.
     */
    public function store(StoreCierreCajaRequest $request, CierreCajaService $caja): RedirectResponse
    {
        $datos = $request->validated();
        $usuario = $request->user();
        $esAdmin = $usuario->hasRole('Admin');
        $userId = (int) ($datos['user_id'] ?? $usuario->id);

        // 6.1.9: un no-Admin no puede cerrar el turno de otro.
        if (! $esAdmin && $userId !== $usuario->id) {
            abort(403, 'Solo puede cerrar su propio turno.');
        }

        try {
            $cierre = $caja->cerrar(
                $userId,
                $datos['fecha'],
                $datos['turno'],
                $datos['montos'] ?? [],
                $datos['notas'] ?? null
            );
        } catch (DomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getUserMessage());
        }

        return redirect()
            ->route('admin.caja.index')
            ->with('success', 'Cierre de caja registrado. Diferencia: Bs '.number_format((float) $cierre->diferencia, 2, '.', ','));
    }

    /**
     * Reporte diario (6.1.8): totales por método, ventas y cierres del día.
     */
    public function reporte(Request $request, CierreCajaService $caja, ?string $fecha = null): View
    {
        $this->autorizarCaja();

        $dia = $this->fecha($fecha ?? $request->string('fecha')->toString());
        $resumen = $caja->calcularDelDia($dia);

        $ventas = Venta::query()
            ->with(['vendedor:id,name', 'pagos'])
            ->whereDate('fecha', $dia)
            // Mismo criterio que CierreCajaService::ESTADOS_COBRADOS.
            ->whereIn('estado', ReporteService::ESTADOS_COBRADOS)
            ->orderBy('fecha')
            ->get();

        $cierres = CierreCaja::query()
            ->with(['user:id,name', 'pagos'])
            ->whereDate('fecha', $dia)
            ->orderBy('turno')
            ->get();

        return view('admin.caja.reporte', compact('dia', 'resumen', 'ventas', 'cierres'));
    }

    /**
     * Admin y Cajero operan la caja; el resto recibe 403.
     */
    private function autorizarCaja(): User
    {
        $usuario = auth()->user();

        abort_unless(
            $usuario !== null && $usuario->hasAnyRole(['Admin', 'Cajero']),
            403,
            'Solo el personal de caja puede acceder.'
        );

        return $usuario;
    }

    /**
     * Normaliza cualquier entrada a una fecha AAAA-MM-DD (hoy si es inválida).
     */
    private function fecha(string $fecha): string
    {
        try {
            return $fecha === ''
                ? now()->toDateString()
                : Carbon::parse($fecha)->toDateString();
        } catch (Throwable) {
            return now()->toDateString();
        }
    }
}
