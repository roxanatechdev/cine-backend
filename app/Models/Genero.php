<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Genero extends Model
{
    protected $table = 'genero';

    protected $primaryKey = 'id_genero';

    public $incrementing = true;

    protected $keyType = 'int';

    // La tabla genero no tiene columnas de timestamps.
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'id_genero' => 'integer',
            'activo' => 'boolean',
        ];
    }

    /**
     * Películas que tienen este género como género principal.
     */
    public function peliculas(): HasMany
    {
        return $this->hasMany(
            Pelicula::class,
            'id_genero_principal',
            'id_genero'
        );
    }
}
