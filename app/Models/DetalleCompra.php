<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleCompra extends Model
{
    protected $table = 'detalle_compra';

    protected $primaryKey = 'id_detalle_compra';

    public $timestamps = false;

    protected $fillable = [
        'id_compra',
        'id_producto',
        'cantidad',
        'precio_unitario',
    ];

    protected function casts(): array
    {
        return [
            'id_detalle_compra' => 'integer',
            'id_compra' => 'integer',
            'id_producto' => 'integer',
            'cantidad' => 'integer',
            'precio_unitario' => 'decimal:2',
        ];
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'id_compra', 'id_compra');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }
}
