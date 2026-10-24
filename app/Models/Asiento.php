<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asiento extends Model
{
    protected $table = 'asiento';

    protected $primaryKey = 'id_asiento';

    public $timestamps = false;

    protected $fillable = [
        'id_sala',
        'fila',
        'numero',
        'tipo',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'id_asiento' => 'integer',
            'id_sala' => 'integer',
            'numero' => 'integer',
            'activo' => 'integer',
        ];
    }

    public function sala(): BelongsTo
    {
        return $this->belongsTo(Sala::class, 'id_sala', 'id_sala');
    }
}
