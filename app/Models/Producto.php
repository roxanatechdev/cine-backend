<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Producto extends Model
{
    protected $table = 'producto';
    protected $primaryKey = 'id_producto';
    public $timestamps = false;

    protected $fillable = [
        'id_categoria_producto', 'nombre', 'descripcion', 'precio_actual',
        'imagen', 'stock', 'estado', 'tamano', 'tipo_variante',
        'contenido_ml', 'peso_gramos', 'es_promocion', 'precio_promocional',
        'promocion_inicio', 'promocion_fin',
    ];

    protected function casts(): array
    {
        return [
            'precio_actual' => 'decimal:2',
            'stock' => 'integer',
            'contenido_ml' => 'integer',
            'peso_gramos' => 'integer',
            'es_promocion' => 'boolean',
            'precio_promocional' => 'decimal:2',
            'promocion_inicio' => 'datetime',
            'promocion_fin' => 'datetime',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaProducto::class, 'id_categoria_producto', 'id_categoria_producto');
    }
}
