<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = [
        'nombre',
        'tipo',
        'atributos_aplicables',
    ];

    protected $casts = [
        'atributos_aplicables' => 'array',
    ];

    public function productos()
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }

    public function atributos()
    {
        return $this->hasMany(AtributoTecnico::class, 'categoria_id');
    }
}
