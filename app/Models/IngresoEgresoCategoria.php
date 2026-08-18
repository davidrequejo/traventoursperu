<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngresoEgresoCategoria extends Model
{
    protected $table = 'ingreso_egreso_categoria';

    protected $primaryKey = 'idingreso_egreso_categoria';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'user_trash' => 'integer',
            'user_created' => 'integer',
            'user_updated' => 'integer',
        ];
    }
}
