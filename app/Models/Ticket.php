<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $table = 'ticket';

    protected $primaryKey = 'id_ticket';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'id_ticket' => 'integer',
            'id_compra' => 'integer',
            'emitido_en' => 'datetime',
            'utilizado_en' => 'datetime',
        ];
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'id_compra', 'id_compra');
    }
}
