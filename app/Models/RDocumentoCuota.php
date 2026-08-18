<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RDocumentoCuota extends Model
{
    protected $table = 'rdocumento_cuota';

    protected $primaryKey = 'idrdocumento_cuota';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'idrdocumento',
        'idreserva',
        'tipo',
        'numero_cuota',
        'fecha_cuota',
        'monto_cuota',
        'estado_cuota',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    protected function casts(): array
    {
        return [
            'idrdocumento' => 'integer',
            'idreserva' => 'integer',
            'tipo' => 'string',
            'numero_cuota' => 'integer',
            'fecha_cuota' => 'date',
            'monto_cuota' => 'decimal:2',
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

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'idreserva', 'idreserva');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(RDocumentoCuotaPago::class, 'idrdocumento_cuota', 'idrdocumento_cuota');
    }
}
