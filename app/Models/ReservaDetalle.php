<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservaDetalle extends Model
{
    protected $table = 'reserva_detalle';

    protected $primaryKey = 'idreserva_detalle';

    protected $fillable = [
        'idreserva',
        'idtours',
        'idtours_turno',
        'nombre_tours',
        'vehiculo',
        'nro_pax',
        'fecha_tours',
        'observacion',
        'precio',
        'descuento',
        'descuento_porcentaje',
        'subtotal',
        'subtotal_no_descuento',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'idreserva', 'idreserva');
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'idtours', 'idtours');
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(TourTurno::class, 'idtours_turno', 'idtours_turno');
    }
}
