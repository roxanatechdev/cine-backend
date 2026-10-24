<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reembolso extends Model
{
    protected $table = 'reembolso';

    protected $primaryKey = 'id_reembolso';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'id_reembolso' => 'integer',
            'id_pago' => 'integer',
            'importe' => 'decimal:2',
            'solicitado_en' => 'datetime',
            'completado_en' => 'datetime',
        ];
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'id_pago', 'id_pago');
    }
}
