<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Libera el stock reservado por pedidos web que nadie confirmó.
 *
 * El checkout descuenta stock al crear el pedido Pendiente. Si el comprador
 * abandona, esas unidades quedarían inmovilizadas para siempre; este comando
 * las devuelve pasadas N horas usando el mismo flujo de cancelación del panel.
 */
class ExpirarPedidosWeb extends Command
{
    protected $signature = 'pedidos:expirar
                            {--horas=24 : Antigüedad mínima del pedido, en horas}
                            {--dry-run : Muestra qué pedidos se cancelarían sin tocar nada}';

    protected $description = 'Cancela pedidos web Pendientes vencidos y reingresa su stock.';

    public function handle(VentaService $ventas): int
    {
        $horas = max(1, (int) $this->option('horas'));
        $limite = now()->subHours($horas);

        $vencidos = Venta::query()
            ->where('origen', 'web')
            ->where('estado', 'Pendiente')
            ->where('created_at', '<=', $limite)
            ->orderBy('id')
            ->get();

        if ($vencidos->isEmpty()) {
            $this->info("Sin pedidos web pendientes de más de {$horas} h.");

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            foreach ($vencidos as $venta) {
                $this->line("#{$venta->id} · {$venta->created_at?->format('d/m/Y H:i')} · Bs ".\App\Support\Dinero::numero($venta->total));
            }

            $this->info("Se cancelarían {$vencidos->count()} pedido(s).");

            return self::SUCCESS;
        }

        $cancelados = 0;
        $unidades = 0;

        foreach ($vencidos as $venta) {
            try {
                $cancelado = $ventas->cancelarPedidoWeb($venta);
                $cancelados++;
                $unidades += (int) $cancelado->detalles->sum('cantidad');
            } catch (Throwable $e) {
                report($e);
                $this->warn("No se pudo cancelar el pedido #{$venta->id}: {$e->getMessage()}");
            }
        }

        $this->info("✅ {$cancelados} pedido(s) cancelados · {$unidades} unidad(es) devueltas al stock.");

        return self::SUCCESS;
    }
}
