<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reserva extends Model
{
    protected $table = 'reserva';

    protected $primaryKey = 'idreserva';

    protected $fillable = [
        'idtrabajador',
        'idcliente',
        'llegada_ref_asesor',
        'idorigen_reserva',
        'idllegada_por_empresa',
        'tours_paquete',
        'serie_reserva',
        'nro_reserva',
        'serie_numero',
        'nro_pasajeros',
        'fecha_llegada',
        'hora_llegada',
        'fecha_salida',
        'llegada_por',
        'reserva_hotel',
        'observacion_recojo',
        'itinerario_general',
        'vuelo_ticket',
        'vuelo_costo',
        'vuelo_observacion',
        'total_reserva',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    protected function casts(): array
    {
        return [
            'fecha_llegada' => 'date',
            'fecha_salida' => 'date',
            'total_reserva' => 'decimal:2',
            'vuelo_costo' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'idcliente', 'idpersona');
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'idtrabajador', 'idpersona');
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'llegada_ref_asesor', 'idpersona');
    }

    public function origen(): BelongsTo
    {
        return $this->belongsTo(OrigenReserva::class, 'idorigen_reserva', 'idorigen_reserva');
    }

    public function llegadaEmpresa(): BelongsTo
    {
        return $this->belongsTo(LlegadaPorEmpresa::class, 'idllegada_por_empresa', 'idllegada_por_empresa');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_created', 'id');
    }
    public function detalles(): HasMany
    {
        return $this->hasMany(ReservaDetalle::class, 'idreserva', 'idreserva');
    }

    public function hoteles(): HasMany
    {
        return $this->hasMany(ReservaHotelDetalle::class, 'idreserva', 'idreserva');
    }
}
