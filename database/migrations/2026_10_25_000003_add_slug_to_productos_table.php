<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Slug para la ficha pública /producto/{slug}.
 * Sprint 2 — Día 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('productos', 'slug')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->string('slug', 255)->nullable()->unique();
            });
        }

        $this->backfill();
    }

    public function down(): void
    {
        if (! Schema::hasColumn('productos', 'slug')) {
            return;
        }

        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }

    /**
     * Genera slugs legibles resolviendo colisiones con sufijo numérico.
     */
    private function backfill(): void
    {
        $usados = [];

        DB::table('productos')
            ->select(['id', 'nombre', 'slug'])
            ->orderBy('id')
            ->chunk(200, function ($productos) use (&$usados) {
                foreach ($productos as $producto) {
                    $slug = $producto->slug ?: Str::slug($producto->nombre);

                    if ($slug === '') {
                        $slug = 'producto-'.$producto->id;
                    }

                    $base = $slug;
                    $i = 1;
                    while (isset($usados[$slug])) {
                        $slug = "{$base}-{$i}";
                        $i++;
                    }

                    $usados[$slug] = true;

                    if ($producto->slug !== $slug) {
                        DB::table('productos')->where('id', $producto->id)->update(['slug' => $slug]);
                    }
                }
            });
    }
};
