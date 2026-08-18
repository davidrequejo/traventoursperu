<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RDocumentoMetodoPago extends Model
{
    protected $table = 'rdocumento_metodo_pago';

    protected $primaryKey = 'idrdocumento_metodo_pago';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'idrdocumento',
        'idcuenta_bancaria',
        'monto',
        'codigo_voucher',
        'comprobante',
        'comprobante_nombre_visible',
        'comprobante_nombre_original',
        'comprobante_size_bytes',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    protected function casts(): array
    {
        return [
            'idrdocumento' => 'integer',
            'idcuenta_bancaria' => 'integer',
            'monto' => 'decimal:2',
            'comprobante_size_bytes' => 'decimal:4',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'user_trash' => 'integer',
            'user_created' => 'integer',
            'user_updated' => 'integer',
        ];
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(RDocumento::class, 'idrdocumento', 'idrdocumento');
    }

    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class, 'idcuenta_bancaria', 'idcuenta_bancaria');
    }
}
