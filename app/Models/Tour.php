<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tour extends Model
{
    protected $table = 'tours';

    protected $primaryKey = 'idtours';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'idtours_turno',
        'idubigeo_distrito',
        'codigo',
        'nombre',
        'precio_publico',
        'precio_corporativo',
        'precio_tours',
        'precio_web',
        'descripcion_inicial',
        'duracion',
        'hora_recojo',
        'hora_retorno',
        'descripcion',
        'descripcion_momento_destacados',
        'información importante',
        'descripcion_incluye_noincluye',
        'ubicacion_maps',
        'brochure',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    protected function casts(): array
    {
        return [
            'idtours_turno' => 'integer',
            'idubigeo_distrito' => 'integer',
            'precio_publico' => 'decimal:2',
            'precio_corporativo' => 'decimal:2',
            'precio_tours' => 'decimal:2',
            'precio_web' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'user_trash' => 'integer',
            'user_created' => 'integer',
            'user_updated' => 'integer',
        ];
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(TourTurno::class, 'idtours_turno', 'idtours_turno');
    }

    public function distrito(): BelongsTo
    {
        return $this->belongsTo(UbigeoDistrito::class, 'idubigeo_distrito', 'idubigeo_distrito');
    }
}
