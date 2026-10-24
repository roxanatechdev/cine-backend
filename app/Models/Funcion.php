<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Funcion extends Model
{
    protected $table = 'funcion';

    protected $primaryKey = 'id_funcion';

    public $timestamps = false;


    protected $fillable = [
        'id_pelicula',
        'id_sala',
        'inicio',
        'formato',
        'precio_base',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'id_funcion' => 'integer',
            'id_pelicula' => 'integer',
            'id_sala' => 'integer',
            'inicio' => 'datetime',
            'precio_base' => 'decimal:2',
            'creado_en' => 'datetime',
        ];
    }

    public function pelicula(): BelongsTo
    {
        return $this->belongsTo(Pelicula::class, 'id_pelicula', 'id_pelicula');
    }

    public function sala(): BelongsTo
    {
        return $this->belongsTo(Sala::class, 'id_sala', 'id_sala');
    }

    public function asientosFuncion(): HasMany
    {
        return $this->hasMany(FuncionAsiento::class, 'id_funcion', 'id_funcion');
    }
}
