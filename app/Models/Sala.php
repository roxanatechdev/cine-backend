<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sala extends Model
{
    protected $table = 'sala';

    protected $primaryKey = 'id_sala';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'capacidad',
        'margen_limpieza_minutos',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'id_sala' => 'integer',
            'capacidad' => 'integer',
            'margen_limpieza_minutos' => 'integer',
        ];
    }
}
