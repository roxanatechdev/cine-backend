<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Persona extends Model
{
    protected $table = 'persona';

    protected $primaryKey = 'id_persona';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'nombre_completo',
        'nombre_busqueda',
        'foto',
    ];

    protected function casts(): array
    {
        return [
            'id_persona' => 'integer',
        ];
    }

    /**
     * Películas en las que participa esta persona,
     * junto con su rol y los datos del crédito.
     */
    public function peliculas(): BelongsToMany
    {
        return $this->belongsToMany(
            Pelicula::class,
            'pelicula_credito',
            'id_persona',
            'id_pelicula',
            'id_persona',
            'id_pelicula'
        )->withPivot([
            'rol',
            'personaje',
            'orden',
        ]);
    }
}
