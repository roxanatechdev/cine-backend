<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comprador extends Model
{
    protected $table = 'comprador';

    protected $primaryKey = 'id_comprador';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'correo',
        'telefono',
        'tipo_comprador',
    ];

    protected function casts(): array
    {
        return [
            'id_comprador' => 'integer',
            'creado_en' => 'datetime',
        ];
    }

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'id_comprador', 'id_comprador');
    }
}
