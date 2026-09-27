<?php

namespace App\Console\Commands;

use App\Services\InventoryService;
use Illuminate\Console\Command;

class CheckStockAlerts extends Command
{
    /**
     * @var string
     */
    protected $signature = 'inventory:check-alerts';

    /**
     * @var string
     */
    protected $description = 'Genera las alertas de stock faltantes para productos en nivel crítico';

    public function __construct(private readonly InventoryService $inventory)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $generadas = $this->inventory->generarAlertasPendientes();

        $this->info("✅ Alertas generadas: {$generadas}.");

        return self::SUCCESS;
    }
}
