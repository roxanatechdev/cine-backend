<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pelicula extends Model
{
    protected $table = 'pelicula';

    protected $primaryKey = 'id_pelicula';

    public $incrementing = true;

    protected $keyType = 'int';

    const CREATED_AT = 'creado_en';

    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'titulo',
        'sinopsis',
        'id_genero_principal',
        'clasificacion',
        'duracion_minutos',
        'fecha_estreno',
        'idioma_original_codigo',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'id_pelicula' => 'integer',
            'id_genero_principal' => 'integer',
            'duracion_minutos' => 'integer',
            'fecha_estreno' => 'date',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function generoPrincipal(): BelongsTo
    {
        return $this->belongsTo(
            Genero::class,
            'id_genero_principal',
            'id_genero'
        );
    }

    public function idiomaOriginal(): BelongsTo
    {
        return $this->belongsTo(
            Idioma::class,
            'idioma_original_codigo',
            'codigo'
        );
    }

    public function multimedia(): HasMany
    {
        return $this->hasMany(
            PeliculaMultimedia::class,
            'id_pelicula',
            'id_pelicula'
        );
    }

    /**
     * Créditos asociados a la película.
     */
    public function creditos(): HasMany
    {
        return $this->hasMany(
            PeliculaCredito::class,
            'id_pelicula',
            'id_pelicula'
        );
    }
    /**
     * Países de producción asociados a la película.
     */
    public function paises(): BelongsToMany
    {
        return $this->belongsToMany(
            Pais::class,
            'pelicula_pais',
            'id_pelicula',
            'codigo_pais',
            'id_pelicula',
            'codigo'
        );
    }
}
