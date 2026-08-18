<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuentaBancaria extends Model
{
    protected $table = 'cuenta_bancaria';

    protected $primaryKey = 'idcuenta_bancaria';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'idbanco',
        'idpersona',
        'cci',
        'cta_cte',
        'moneda',
        'tipo_cuenta',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    protected function casts(): array
    {
        return [
            'idbanco' => 'integer',
            'idpersona' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'user_trash' => 'integer',
            'user_created' => 'integer',
            'user_updated' => 'integer',
        ];
    }

    public function banco(): BelongsTo
    {
        return $this->belongsTo(Banco::class, 'idbanco', 'idbanco');
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'idpersona', 'idpersona');
    }
}
