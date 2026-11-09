<?php

namespace App\Repositories;

use App\Contracts\ProductoRepositoryInterface;
use App\DTOs\ProductoFiltros;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Consultas de solo lectura sobre tu esquema real:
 * productos (visible_catalogo, ocultar_si_sin_stock, precio_unitario, stock),
 * categorias, marcas, producto_imagenes, producto_atributos.
 */
final class DbProductoRepository implements ProductoRepositoryInterface
{
    public function paginarFiltrados(ProductoFiltros $f, int $porPagina = 12): LengthAwarePaginator
    {
        return $this->aplicarFiltros($this->base(), $f)
            ->paginate($porPagina)
            ->withQueryString()
            ->through(fn ($row) => $this->decorar($row));
    }

    public function destacados(int $limite = 8): Collection
    {
        return $this->base()->where('p.stock', '>', 0)->orderByDesc('p.id')->limit($limite)->get()
            ->map(fn ($r) => $this->decorar($r));
    }

    public function buscarPorIds(array $ids): Collection
    {
        return $this->base()->whereIn('p.id', $ids)->get()->map(fn ($r) => $this->decorar($r));
    }

    public function buscarPorReferencia(string $ref): ?object
    {
        $q = $this->base()->where(function ($w) use ($ref) {
            $w->where('p.slug', $ref);
            if (ctype_digit($ref)) {
                $w->orWhere('p.id', (int) $ref);
            }
        });

        $row = $q->first();

        return $row ? $this->decorar($row) : null;
    }

    public function relacionados(int $productoId, int $categoriaId, int $limite = 4): Collection
    {
        return $this->base()->where('p.categoria_id', $categoriaId)->where('p.id', '<>', $productoId)
            ->where('p.stock', '>', 0)->inRandomOrder()->limit($limite)->get()
            ->map(fn ($r) => $this->decorar($r));
    }

    public function galeria(int $productoId): array
    {
        return DB::table('producto_imagenes')->where('producto_id', $productoId)
            ->orderByDesc('es_principal')->orderBy('orden')->pluck('path')
            ->map(fn ($p) => $this->urlImagen($p))->all();
    }

    public function especificaciones(int $productoId): array
    {
        return DB::table('producto_atributos as pa')
            ->join('atributos_tecnicos as a', 'a.id', '=', 'pa.atributo_id')
            ->leftJoin('valores_atributo as v', 'v.id', '=', 'pa.valor_enum_id')
            ->where('pa.producto_id', $productoId)
            ->orderBy('a.orden')
            ->get(['a.nombre', 'a.unidad', 'pa.valor_string', 'pa.valor_integer', 'pa.valor_decimal', 'pa.valor_boolean', 'v.valor as valor_enum'])
            ->map(function ($r) {
                $valor = $r->valor_string
                    ?? $r->valor_enum
                    ?? ($r->valor_integer !== null ? (string) $r->valor_integer : null)
                    ?? ($r->valor_decimal !== null ? rtrim(rtrim((string) $r->valor_decimal, '0'), '.') : null)
                    ?? ($r->valor_boolean !== null ? ($r->valor_boolean ? 'Sí' : 'No') : null);

                return $valor === null ? null : ['nombre' => $r->nombre, 'valor' => trim($valor.' '.($r->unidad ?? ''))];
            })
            ->filter()->values()->all();
    }

    public function categorias(): Collection
    {
        return DB::table('categorias')->orderBy('nombre')->get(['id', 'nombre']);
    }

    public function marcas(): Collection
    {
        return DB::table('marcas')->orderBy('nombre')->get(['id', 'nombre']);
    }

    /** Solo lo que el cliente debe ver: visible_catalogo y respetando ocultar_si_sin_stock. */
    private function base(): Builder
    {
        return DB::table('productos as p')
            ->join('categorias as c', 'c.id', '=', 'p.categoria_id')
            ->leftJoin('marcas as m', 'm.id', '=', 'p.marca_id')
            ->where('p.visible_catalogo', true)
            ->where(fn ($q) => $q->where('p.ocultar_si_sin_stock', false)->orWhere('p.stock', '>', 0))
            ->select('p.id', 'p.nombre', 'p.slug', 'p.descripcion', 'p.precio_unitario', 'p.stock',
                'p.categoria_id', 'p.marca_id', 'c.nombre as categoria', 'm.nombre as marca')
            ->selectRaw('coalesce(p.imagen_principal, (select i.path from producto_imagenes i where i.producto_id = p.id order by i.es_principal desc, i.orden asc limit 1)) as imagen');
    }

    private function aplicarFiltros(Builder $q, ProductoFiltros $f): Builder
    {
        $q->when($f->busqueda, fn ($q, $v) => $q->where('p.nombre', 'ilike', '%'.$v.'%'))
            ->when($f->categoriaId, fn ($q, $v) => $q->where('p.categoria_id', $v))
            ->when($f->marcaId, fn ($q, $v) => $q->where('p.marca_id', $v))
            ->when($f->precioMin !== null, fn ($q) => $q->where('p.precio_unitario', '>=', $f->precioMin))
            ->when($f->precioMax !== null, fn ($q) => $q->where('p.precio_unitario', '<=', $f->precioMax))
            ->when($f->soloDisponibles, fn ($q) => $q->where('p.stock', '>', 0));

        return match ($f->orden) {
            'precio_asc' => $q->orderBy('p.precio_unitario'),
            'precio_desc' => $q->orderByDesc('p.precio_unitario'),
            'nombre' => $q->orderBy('p.nombre'),
            default => $q->orderByDesc('p.id'),
        };
    }

    /** Agrega propiedades listas para las vistas: ref (slug o id) e imagen_url. */
    private function decorar(object $row): object
    {
        $row->ref = $row->slug ?: (string) $row->id;
        $row->imagen_url = $row->imagen ? $this->urlImagen($row->imagen) : null;
        $row->precio_unitario = (float) $row->precio_unitario;
        $row->stock = (int) $row->stock;

        return $row;
    }

    private function urlImagen(string $path): string
    {
        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return asset('storage/'.Str::after(ltrim($path, '/'), 'storage/'));
    }
}
