<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pais extends Model
{
    protected $table = 'pais';

    protected $primaryKey = 'codigo';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
    ];

    public function peliculas(): BelongsToMany
    {
        return $this->belongsToMany(
            Pelicula::class,
            'pelicula_pais',
            'codigo_pais',
            'id_pelicula',
            'codigo',
            'id_pelicula'
        );
    }
}
