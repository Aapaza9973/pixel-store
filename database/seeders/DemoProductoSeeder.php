<?php

namespace Database\Seeders;

use App\Models\AtributoTecnico;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoAtributo;
use App\Models\StockUbicacion;
use App\Models\Ubicacion;
use App\Models\ValorAtributo;
use Illuminate\Database\Seeder;

class DemoProductoSeeder extends Seeder
{
    /**
     * 15 productos realistas del rubro con sus atributos EAV
     * y el stock repartido entre las ubicaciones registradas.
     */
    public function run(): void
    {
        // Permite correr este seeder solo, garantizando sus dependencias.
        if (Categoria::query()->count() === 0) {
            $this->call([
                CategoriaSeeder::class,
                MarcaSeeder::class,
                AtributoTecnicoSeeder::class,
                ValorAtributoSeeder::class,
                UbicacionSeeder::class,
            ]);
        }

        // Con la estructura física, la tienda y el depósito primarios son las primeras de su tipo.
        $tiendaId = Ubicacion::query()->where('tipo', 'tienda')->orderBy('id')->value('id');
        $depositoId = Ubicacion::query()->where('tipo', 'deposito')->orderBy('id')->value('id');

        foreach ($this->productos() as $datos) {
            $categoria = Categoria::query()->where('nombre', $datos['categoria'])->first();

            if (! $categoria) {
                continue;
            }

            $marca = $datos['marca']
                ? Marca::query()->where('nombre', $datos['marca'])->first()
                : null;

            $producto = Producto::updateOrCreate(
                ['nombre' => $datos['nombre']],
                [
                    'categoria_id' => $categoria->id,
                    'marca_id' => $marca?->id,
                    'descripcion' => $datos['descripcion'],
                    'precio_unitario' => $datos['precio'],
                    'costo' => $datos['costo'],
                    'stock' => $datos['stock'],
                    'umbral_alerta' => $datos['umbral'],
                    'maneja_numero_serie' => $datos['serie'] ?? false,
                    'sku' => $datos['sku'],
                    'codigo_barras' => $datos['barras'],
                    'visible_catalogo' => true,
                ]
            );

            foreach ($datos['atributos'] as $nombre => $valor) {
                $this->guardarAtributo($producto, $nombre, $valor);
            }

            $this->distribuirStock($producto, $datos['stock'], $tiendaId, $depositoId);
        }

        $this->command?->info('✅ '.Producto::count().' productos demo con atributos y stock por ubicación.');
    }

    /**
     * Crea o actualiza el valor EAV del producto según el tipo_dato del atributo.
     */
    private function guardarAtributo(Producto $producto, string $nombreAtributo, mixed $valor): void
    {
        $atributo = AtributoTecnico::query()
            ->where('categoria_id', $producto->categoria_id)
            ->where('nombre', $nombreAtributo)
            ->first();

        if (! $atributo) {
            return;
        }

        $datos = [
            'producto_id' => $producto->id,
            'atributo_id' => $atributo->id,
            'valor_string' => null,
            'valor_integer' => null,
            'valor_decimal' => null,
            'valor_boolean' => null,
            'valor_enum_id' => null,
        ];

        match ($atributo->tipo_dato) {
            'string' => $datos['valor_string'] = (string) $valor,
            'integer' => $datos['valor_integer'] = (int) $valor,
            'decimal' => $datos['valor_decimal'] = (float) $valor,
            'boolean' => $datos['valor_boolean'] = (bool) $valor,
            'enum' => $datos['valor_enum_id'] = $this->valorEnumId($atributo, (string) $valor),
            default => null,
        };

        ProductoAtributo::updateOrCreate(
            ['producto_id' => $producto->id, 'atributo_id' => $atributo->id],
            $datos
        );
    }

    /**
     * Resuelve el id de un valor de enum a partir de su etiqueta.
     */
    private function valorEnumId(AtributoTecnico $atributo, string $valor): ?int
    {
        return ValorAtributo::query()
            ->where('atributo_id', $atributo->id)
            ->where('valor', $valor)
            ->value('id');
    }

    /**
     * Reparte el stock total: 30% en tienda y el resto en depósito.
     */
    private function distribuirStock(Producto $producto, int $stock, ?int $tiendaId, ?int $depositoId): void
    {
        $cantidadTienda = (int) floor($stock * 0.3);
        $cantidadDeposito = $stock - $cantidadTienda;

        if ($tiendaId) {
            StockUbicacion::updateOrCreate(
                ['producto_id' => $producto->id, 'ubicacion_id' => $tiendaId],
                ['cantidad' => $cantidadTienda]
            );
        }

        if ($depositoId) {
            StockUbicacion::updateOrCreate(
                ['producto_id' => $producto->id, 'ubicacion_id' => $depositoId],
                ['cantidad' => $cantidadDeposito]
            );
        }
    }

    /**
     * Datos de los productos demo.
     *
     * @return array<int, array<string, mixed>>
     */
    private function productos(): array
    {
        return [
            // ── Procesadores ──────────────────────────────────────────────
            [
                'categoria' => 'Procesadores', 'marca' => 'AMD',
                'nombre' => 'AMD Ryzen 5 5600X',
                'descripcion' => 'Procesador de 6 núcleos y 12 hilos, ideal para gaming y productividad.',
                'precio' => 1450.00, 'costo' => 1180.00, 'stock' => 24, 'umbral' => 5,
                'serie' => false, 'sku' => 'CPU-AMD-5600X', 'barras' => '840000100001',
                'atributos' => ['socket' => 'AM4', 'núcleos' => 6, 'hilos' => 12, 'frecuencia' => 3.7, 'tdp' => 65],
            ],
            [
                'categoria' => 'Procesadores', 'marca' => 'AMD',
                'nombre' => 'AMD Ryzen 7 5800X',
                'descripcion' => 'Procesador de 8 núcleos y 16 hilos para estaciones de trabajo exigentes.',
                'precio' => 2200.00, 'costo' => 1850.00, 'stock' => 12, 'umbral' => 5,
                'serie' => false, 'sku' => 'CPU-AMD-5800X', 'barras' => '840000100002',
                'atributos' => ['socket' => 'AM4', 'núcleos' => 8, 'hilos' => 16, 'frecuencia' => 3.8, 'tdp' => 105],
            ],
            [
                'categoria' => 'Procesadores', 'marca' => 'Intel',
                'nombre' => 'Intel Core i5-12400F',
                'descripcion' => 'Procesador de 12.ª generación sin gráficos integrados, excelente relación precio/rendimiento.',
                'precio' => 1350.00, 'costo' => 1050.00, 'stock' => 30, 'umbral' => 5,
                'serie' => false, 'sku' => 'CPU-INT-12400F', 'barras' => '840000100003',
                'atributos' => ['socket' => 'LGA1700', 'núcleos' => 6, 'hilos' => 12, 'frecuencia' => 2.5, 'tdp' => 65],
            ],
            [
                'categoria' => 'Procesadores', 'marca' => 'Intel',
                'nombre' => 'Intel Core i7-12700K',
                'descripcion' => 'Procesador desbloqueado de alto rendimiento para gaming y creación de contenido.',
                'precio' => 2900.00, 'costo' => 2500.00, 'stock' => 3, 'umbral' => 5,
                'serie' => false, 'sku' => 'CPU-INT-12700K', 'barras' => '840000100004',
                'atributos' => ['socket' => 'LGA1700', 'núcleos' => 12, 'hilos' => 20, 'frecuencia' => 3.6, 'tdp' => 125],
            ],

            // ── Memorias RAM ──────────────────────────────────────────────
            [
                'categoria' => 'Memorias RAM', 'marca' => 'Kingston',
                'nombre' => 'Kingston Fury Beast 8GB DDR4 3200MHz',
                'descripcion' => 'Módulo de memoria gaming con disipador de perfil bajo.',
                'precio' => 320.00, 'costo' => 240.00, 'stock' => 40, 'umbral' => 8,
                'serie' => false, 'sku' => 'RAM-KIN-FB8D4', 'barras' => '840000100005',
                'atributos' => ['tipo_ram' => 'DDR4', 'capacidad' => 8, 'frecuencia' => 3200],
            ],
            [
                'categoria' => 'Memorias RAM', 'marca' => 'Corsair',
                'nombre' => 'Corsair Vengeance 16GB DDR5 5200MHz',
                'descripcion' => 'Módulo DDR5 de alta frecuencia para plataformas de última generación.',
                'precio' => 750.00, 'costo' => 610.00, 'stock' => 4, 'umbral' => 5,
                'serie' => false, 'sku' => 'RAM-COR-V16D5', 'barras' => '840000100006',
                'atributos' => ['tipo_ram' => 'DDR5', 'capacidad' => 16, 'frecuencia' => 5200],
            ],
            [
                'categoria' => 'Memorias RAM', 'marca' => 'Samsung',
                'nombre' => 'Samsung 8GB DDR4 2666MHz',
                'descripcion' => 'Memoria estándar para equipos de oficina y actualizaciones.',
                'precio' => 300.00, 'costo' => 220.00, 'stock' => 35, 'umbral' => 8,
                'serie' => false, 'sku' => 'RAM-SAM-8D4', 'barras' => '840000100007',
                'atributos' => ['tipo_ram' => 'DDR4', 'capacidad' => 8, 'frecuencia' => 2666],
            ],

            // ── Tarjetas madre ────────────────────────────────────────────
            [
                'categoria' => 'Tarjetas madre', 'marca' => 'ASUS',
                'nombre' => 'ASUS Prime B550M-A',
                'descripcion' => 'Placa madre micro-ATX con soporte PCIe 4.0 y Ryzen serie 5000.',
                'precio' => 950.00, 'costo' => 760.00, 'stock' => 18, 'umbral' => 4,
                'serie' => false, 'sku' => 'MB-ASU-B550M', 'barras' => '840000100008',
                'atributos' => ['socket' => 'AM4', 'chipset' => 'B550', 'tipo_ram' => 'DDR4'],
            ],
            [
                'categoria' => 'Tarjetas madre', 'marca' => 'MSI',
                'nombre' => 'MSI PRO B660M-A',
                'descripcion' => 'Placa madre micro-ATX para procesadores Intel de 12.ª generación.',
                'precio' => 1100.00, 'costo' => 880.00, 'stock' => 10, 'umbral' => 4,
                'serie' => false, 'sku' => 'MB-MSI-B660M', 'barras' => '840000100009',
                'atributos' => ['socket' => 'LGA1700', 'chipset' => 'B660', 'tipo_ram' => 'DDR5'],
            ],

            // ── Tarjetas gráficas ─────────────────────────────────────────
            [
                'categoria' => 'Tarjetas gráficas', 'marca' => 'NVIDIA',
                'nombre' => 'NVIDIA GeForce RTX 3060 12GB',
                'descripcion' => 'Tarjeta gráfica con ray tracing y DLSS para gaming en 1080p y 1440p.',
                'precio' => 3200.00, 'costo' => 2750.00, 'stock' => 7, 'umbral' => 3,
                'serie' => true, 'sku' => 'GPU-NVI-RTX3060', 'barras' => '840000100010',
                'atributos' => ['chipset' => 'GeForce RTX 3060', 'vram' => 12, 'longitud' => 242.0],
            ],
            [
                'categoria' => 'Tarjetas gráficas', 'marca' => 'AMD',
                'nombre' => 'AMD Radeon RX 6600 8GB',
                'descripcion' => 'Tarjeta gráfica eficiente para gaming competitivo en 1080p.',
                'precio' => 2800.00, 'costo' => 2380.00, 'stock' => 6, 'umbral' => 3,
                'serie' => true, 'sku' => 'GPU-AMD-RX6600', 'barras' => '840000100011',
                'atributos' => ['chipset' => 'Radeon RX 6600', 'vram' => 8, 'longitud' => 190.0],
            ],

            // ── Fuentes de poder ──────────────────────────────────────────
            [
                'categoria' => 'Fuentes de poder', 'marca' => 'EVGA',
                'nombre' => 'EVGA 600W 80+ Bronze',
                'descripcion' => 'Fuente de poder confiable para armados de gama de entrada y media.',
                'precio' => 550.00, 'costo' => 420.00, 'stock' => 22, 'umbral' => 5,
                'serie' => false, 'sku' => 'PSU-EVG-600B', 'barras' => '840000100012',
                'atributos' => ['vatios' => 600, 'certificacion' => '80+ Bronze'],
            ],
            [
                'categoria' => 'Fuentes de poder', 'marca' => 'Corsair',
                'nombre' => 'Corsair RM750 750W 80+ Gold',
                'descripcion' => 'Fuente modular completamente certificada 80+ Gold.',
                'precio' => 1150.00, 'costo' => 920.00, 'stock' => 9, 'umbral' => 4,
                'serie' => false, 'sku' => 'PSU-COR-RM750', 'barras' => '840000100013',
                'atributos' => ['vatios' => 750, 'certificacion' => '80+ Gold'],
            ],

            // ── Laptops ───────────────────────────────────────────────────
            [
                'categoria' => 'Laptops', 'marca' => 'HP',
                'nombre' => 'HP Pavilion 15',
                'descripcion' => 'Laptop de 15.6" para estudio y trabajo con procesador Intel de 12.ª generación.',
                'precio' => 6500.00, 'costo' => 5600.00, 'stock' => 5, 'umbral' => 3,
                'serie' => true, 'sku' => 'LAP-HP-PAV15', 'barras' => '840000100014',
                'atributos' => [
                    'tamaño_pantalla' => 15.6, 'procesador' => 'Intel Core i5-1235U',
                    'ram' => 8, 'almacenamiento' => 512, 'sistema_operativo' => 'Windows 11',
                ],
            ],
            [
                'categoria' => 'Laptops', 'marca' => 'Lenovo',
                'nombre' => 'Lenovo IdeaPad 3',
                'descripcion' => 'Laptop económica de 15.6" con procesador AMD Ryzen serie 5000.',
                'precio' => 5200.00, 'costo' => 4400.00, 'stock' => 8, 'umbral' => 3,
                'serie' => true, 'sku' => 'LAP-LEN-IP3', 'barras' => '840000100015',
                'atributos' => [
                    'tamaño_pantalla' => 15.6, 'procesador' => 'AMD Ryzen 5 5500U',
                    'ram' => 8, 'almacenamiento' => 256, 'sistema_operativo' => 'Windows 10',
                ],
            ],
        ];
    }
}
