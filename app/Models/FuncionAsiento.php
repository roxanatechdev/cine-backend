<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuncionAsiento extends Model
{
    protected $table = 'funcion_asiento';

    protected $primaryKey = 'id_funcion_asiento';

    public $timestamps = false;

    protected $fillable = [
        'id_funcion',
        'id_asiento',
        'precio',
    ];

    protected function casts(): array
    {
        return [
            'id_funcion_asiento' => 'integer',
            'id_funcion' => 'integer',
            'id_asiento' => 'integer',
            'precio' => 'decimal:2',
        ];
    }

    public function funcion(): BelongsTo
    {
        return $this->belongsTo(Funcion::class, 'id_funcion', 'id_funcion');
    }

    public function asiento(): BelongsTo
    {
        return $this->belongsTo(Asiento::class, 'id_asiento', 'id_asiento');
    }
}
