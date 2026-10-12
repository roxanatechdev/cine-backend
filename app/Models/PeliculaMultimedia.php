<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeliculaMultimedia extends Model
{
    /**
     * Tabla física asociada al modelo.
     */
    protected $table = 'pelicula_multimedia';

    /**
     * Clave primaria autoincremental.
     */
    protected $primaryKey = 'id_multimedia';

    public $incrementing = true;

    protected $keyType = 'int';

    /**
     * La tabla utiliza creado_en, pero no actualizado_en.
     */
    public $timestamps = false;

    /**
     * Campos permitidos para asignación masiva.
     */
    protected $fillable = [
        'id_pelicula',
        'tipo',
        'url',
        'texto_alternativo',
        'es_principal',
        'orden',
        'activo',
    ];

    /**
     * Conversión de atributos a tipos PHP.
     */
    protected function casts(): array
    {
        return [
            'id_pelicula' => 'integer',
            'es_principal' => 'boolean',
            'orden' => 'integer',
            'activo' => 'boolean',
            'creado_en' => 'datetime',
        ];
    }

    /**
     * Película a la que pertenece el recurso multimedia.
     */
    public function pelicula(): BelongsTo
    {
        return $this->belongsTo(
            Pelicula::class,
            'id_pelicula',
            'id_pelicula'
        );
    }
}
