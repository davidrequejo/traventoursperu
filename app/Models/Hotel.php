<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotel extends Model
{
    protected $table = 'hotel';

    protected $primaryKey = 'idhotel';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'idpersona',
        'idhotel_tipo',
        'estrellas',
        'tarifa_x_pers_paq',
        'check_in',
        'check_out',
        'descripcion',
        'gogle_maps',
        'imagen_principal',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    protected function casts(): array
    {
        return [
            'idpersona' => 'integer',
            'idhotel_tipo' => 'integer',
            'tarifa_x_pers_paq' => 'decimal:2',
            'user_trash' => 'integer',
            'user_created' => 'integer',
            'user_updated' => 'integer',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'idpersona', 'idpersona');
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(HotelTipo::class, 'idhotel_tipo', 'idhotel_tipo');
    }

    public function habitaciones(): HasMany
    {
        return $this->hasMany(HotelHabitacion::class, 'idhotel', 'idhotel');
    }
}
