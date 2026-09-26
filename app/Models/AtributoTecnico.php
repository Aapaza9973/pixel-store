<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtributoTecnico extends Model
{
    protected $table = 'atributos_tecnicos';

    protected $fillable = [
        'nombre',
        'tipo_dato',
        'unidad',
        'categoria_id',
        'es_filtrable',
        'es_comparable',
        'orden',
    ];

    protected $casts = [
        'es_filtrable' => 'boolean',
        'es_comparable' => 'boolean',
    ];

    public function valoresPredefinidos()
    {
        return $this->hasMany(ValorAtributo::class, 'atributo_id');
    }
}