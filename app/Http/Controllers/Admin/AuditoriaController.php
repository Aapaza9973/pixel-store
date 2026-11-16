<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Consulta de `logs_auditoria` (HU-4.4): quién, cuándo y qué.
 *
 * Solo lectura: la auditoría se escribe desde `AuditoriaService`, observers y
 * listeners; acá no hay alta ni borrado.
 */
class AuditoriaController extends Controller
{
    public function index(Request $request): View
    {
        $this->autorizar($request);

        $filtros = $this->filtros($request);

        $logs = $this->consulta($filtros)
            ->paginate(25)
            ->withQueryString();

        return view('admin.auditoria.index', [
            'logs' => $logs,
            'filtros' => $filtros,
            'acciones' => LogAuditoria::query()
                ->select('accion')
                ->distinct()
                ->orderBy('accion')
                ->pluck('accion'),
            'usuarios' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Exporta los mismos registros filtrados a CSV (HU-4.4.3).
     */
    public function export(Request $request): StreamedResponse
    {
        $this->autorizar($request);

        $filtros = $this->filtros($request);
        $nombre = 'auditoria-'.now()->format('Y-m-d-Hi').'.csv';

        return Response::streamDownload(function () use ($filtros): void {
            $salida = fopen('php://output', 'w');
            // BOM para que Excel abra los acentos en UTF-8.
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, ['Fecha', 'Usuario', 'Acción', 'Modelo', 'ID modelo', 'IP']);

            $this->consulta($filtros)->chunk(500, function ($logs) use ($salida): void {
                foreach ($logs as $log) {
                    fputcsv($salida, [
                        optional($log->created_at)->format('Y-m-d H:i:s'),
                        $log->user?->name ?? ($log->user_id === null ? 'Sistema' : 'Usuario #'.$log->user_id),
                        $log->accion,
                        $log->modelo,
                        $log->modelo_id,
                        $log->ip,
                    ]);
                }
            });

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  array{accion:string, usuario:string, desde:string, hasta:string}  $filtros
     * @return Builder<LogAuditoria>
     */
    private function consulta(array $filtros): Builder
    {
        return LogAuditoria::query()
            ->with('user:id,name')
            ->when($filtros['accion'] !== '', fn (Builder $query) => $query->where('accion', $filtros['accion']))
            ->when($filtros['usuario'] !== '', fn (Builder $query) => $query->where('user_id', (int) $filtros['usuario']))
            ->when($filtros['desde'] !== '', fn (Builder $query) => $query->whereDate('created_at', '>=', $filtros['desde']))
            ->when($filtros['hasta'] !== '', fn (Builder $query) => $query->whereDate('created_at', '<=', $filtros['hasta']))
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * @return array{accion:string, usuario:string, desde:string, hasta:string}
     */
    private function filtros(Request $request): array
    {
        $desde = $request->string('desde')->toString();
        $hasta = $request->string('hasta')->toString();

        return [
            'accion' => $request->string('accion')->toString(),
            'usuario' => $request->string('usuario')->toString(),
            'desde' => $this->fechaValida($desde),
            'hasta' => $this->fechaValida($hasta),
        ];
    }

    private function fechaValida(string $fecha): string
    {
        if ($fecha === '') {
            return '';
        }

        try {
            return Carbon::parse($fecha)->toDateString();
        } catch (\Throwable) {
            return '';
        }
    }

    private function autorizar(Request $request): void
    {
        abort_unless($request->user()?->can('ver auditoria'), 403);
    }
}
