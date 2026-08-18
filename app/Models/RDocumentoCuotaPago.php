<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RDocumentoCuotaPago extends Model
{
    protected $table = 'rdocumento_cuota_pago';

    protected $primaryKey = 'idrdocumento_cuota_pago';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'idrdocumento_cuota',
        'idrdocumento',
        'idrdocumento_detalle',
        'monto_aplicado',
        'fecha_pago',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    protected function casts(): array
    {
        return [
            'idrdocumento_cuota' => 'integer',
            'idrdocumento' => 'integer',
            'idrdocumento_detalle' => 'integer',
            'monto_aplicado' => 'decimal:2',
            'fecha_pago' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'user_trash' => 'integer',
            'user_created' => 'integer',
            'user_updated' => 'integer',
        ];
    }

    public function cuota(): BelongsTo
    {
        return $this->belongsTo(RDocumentoCuota::class, 'idrdocumento_cuota', 'idrdocumento_cuota');
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(RDocumento::class, 'idrdocumento', 'idrdocumento');
    }

    public function detalle(): BelongsTo
    {
        return $this->belongsTo(RDocumentoDetalle::class, 'idrdocumento_detalle', 'idrdocumento_detalle');
    }
}
