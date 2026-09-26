<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ValorAtributo extends Model
{
    protected $table = 'valores_atributo';

    protected $fillable = [
        'atributo_id',
        'valor',
        'orden',
    ];

    public function atributo()
    {
        return $this->belongsTo(AtributoTecnico::class, 'atributo_id');
    }
}