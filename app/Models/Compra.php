<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compra extends Model
{
    protected $table = 'compra';

    protected $primaryKey = 'id_compra';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'id_compra' => 'integer',
            'id_comprador' => 'integer',
            'creada_en' => 'datetime',
            'vence_reserva' => 'datetime',
            'total' => 'decimal:2',
            'cancelada_en' => 'datetime',
            'stock_apartado' => 'boolean',
        ];
    }

    public function detallesCompra(): HasMany
    {
        return $this->hasMany(DetalleCompra::class, 'id_compra', 'id_compra');
    }

    public function comprador(): BelongsTo
    {
        return $this->belongsTo(Comprador::class, 'id_comprador', 'id_comprador');
    }
}
