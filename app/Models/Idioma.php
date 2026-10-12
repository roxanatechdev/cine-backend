<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Idioma extends Model
{
    /**
     * Tabla física asociada al modelo.
     */
    protected $table = 'idioma';

    /**
     * Clave primaria: código BCP 47, por ejemplo es, en o pt-BR.
     */
    protected $primaryKey = 'codigo';

    /**
     * La clave primaria es textual y no autoincremental.
     */
    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * La tabla idioma no tiene columnas de timestamps.
     */
    public $timestamps = false;

    /**
     * Atributos asignables mediante asignación masiva.
     */
    protected $fillable = [
        'codigo',
        'nombre',
        'activo',
    ];

    /**
     * Conversión de atributos.
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * Películas cuyo idioma original es este idioma.
     */
    public function peliculasOriginales(): HasMany
    {
        return $this->hasMany(
            Pelicula::class,
            'idioma_original_codigo',
            'codigo'
        );
    }
}
