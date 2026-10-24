<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'pago';

    protected $primaryKey = 'id_pago';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'id_pago' => 'integer',
            'id_compra' => 'integer',
            'importe' => 'decimal:2',
            'creado_en' => 'datetime',
            'aprobado_en' => 'datetime',
        ];
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'id_compra', 'id_compra');
    }

}
