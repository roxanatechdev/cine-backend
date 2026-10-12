<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeliculaCredito extends Model
{
    /**
     * Tabla física asociada al modelo.
     */
    protected $table = 'pelicula_credito';

    /**
     * La tabla utiliza una clave primaria compuesta:
     * id_pelicula + id_persona + rol.
     */
    protected $primaryKey = 'id_pelicula';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * La tabla no utiliza timestamps convencionales.
     */
    public $timestamps = false;

    /**
     * Campos asignables mediante asignación masiva.
     */
    protected $fillable = [
        'id_pelicula',
        'id_persona',
        'rol',
        'personaje',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'id_pelicula' => 'integer',
            'id_persona' => 'integer',
            'orden' => 'integer',
        ];
    }

    /**
     * Película a la que pertenece el crédito.
     */
    public function pelicula(): BelongsTo
    {
        return $this->belongsTo(
            Pelicula::class,
            'id_pelicula',
            'id_pelicula'
        );
    }

    /**
     * Persona asociada al crédito.
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(
            Persona::class,
            'id_persona',
            'id_persona'
        );
    }
}
