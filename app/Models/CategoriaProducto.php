<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaProducto extends Model
{
    protected $table = 'categoria_producto';

    protected $primaryKey = 'id_categoria_producto';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'id_categoria_producto', 'id_categoria_producto');
    }

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
