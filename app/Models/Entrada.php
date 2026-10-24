<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entrada extends Model
{
    protected $table = 'entrada';

    protected $primaryKey = 'id_entrada';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'id_entrada' => 'integer',
            'id_compra' => 'integer',
            'id_funcion_asiento' => 'integer',
            'precio_venta' => 'decimal:2',
            'reservada_en' => 'datetime',
            'confirmada_en' => 'datetime',
            'utilizada_en' => 'datetime',
            'cerrada_en' => 'datetime',
        ];
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'id_compra', 'id_compra');
    }

    public function funcionAsiento(): BelongsTo
    {
        return $this->belongsTo(FuncionAsiento::class, 'id_funcion_asiento', 'id_funcion_asiento');
    }
}
