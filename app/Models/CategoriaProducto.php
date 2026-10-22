<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaProducto extends Model
{
    protected $table = 'categoria_producto';

    protected $primaryKey = 'id_categoria_producto';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
