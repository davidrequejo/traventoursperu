<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class ReservaHotelDetalle extends Model
{
    public const COLUMNA_OBSERVACION_NORMAL = 'observacion';

    public const COLUMNA_OBSERVACION_UTF8 = "observaci\xC3\xB3n";

    public const COLUMNA_OBSERVACION_LEGACY = "observaci\xC3\x83\xC2\xB3n";

    protected $table = 'reserva_hotel_detalle';

    protected $primaryKey = 'idreserva_hotel_detalle';

    protected $fillable = [
        'idreserva',
        'nombre_habitacion',
        'nro_pax',
        'cantidad_habitacion',
        'fecha_check_in',
        'fecha_check_out',
        'nro_noches',
        'precio',
        'descuento',
        'adicional',
        self::COLUMNA_OBSERVACION_NORMAL,
        self::COLUMNA_OBSERVACION_UTF8,
        self::COLUMNA_OBSERVACION_LEGACY,
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
        'hotel_habitacion_idhotel_habitacion',
    ];

    public static function columnaObservacion(): string
    {
        foreach ([self::COLUMNA_OBSERVACION_NORMAL, self::COLUMNA_OBSERVACION_UTF8, self::COLUMNA_OBSERVACION_LEGACY] as $columna) {
            if (Schema::hasColumn('reserva_hotel_detalle', $columna)) {
                return $columna;
            }
        }

        return self::COLUMNA_OBSERVACION_LEGACY;
    }

    public function getObservacionAttribute(): ?string
    {
        return $this->attributes[self::columnaObservacion()] ?? null;
    }

    public function setObservacionAttribute($value): void
    {
        $this->attributes[self::columnaObservacion()] = $value;
    }

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'idreserva', 'idreserva');
    }

    public function habitacion(): BelongsTo
    {
        return $this->belongsTo(HotelHabitacion::class, 'hotel_habitacion_idhotel_habitacion', 'idhotel_habitacion');
    }
}