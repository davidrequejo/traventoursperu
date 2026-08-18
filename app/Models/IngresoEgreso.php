<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngresoEgreso extends Model
{
    protected $table = 'ingreso_egreso';

    protected $primaryKey = 'idingreso_egreso';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'idproveedor',
        'idtrabajador',
        'idotros_gastos_categoria',
        'tipo_movimiento',
        'tipo_comprobante',
        'serie_comprobante',
        'fecha_ingreso',
        'name_day',
        'name_month',
        'name_year',
        'precio_sin_igv',
        'precio_igv',
        'val_igv',
        'precio_con_igv',
        'descripcion_comprobante',
        'comprobante',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
            'precio_sin_igv' => 'decimal:2',
            'precio_igv' => 'decimal:2',
            'val_igv' => 'decimal:2',
            'precio_con_igv' => 'decimal:2',
            'name_year' => 'integer',
            'user_trash' => 'integer',
            'user_created' => 'integer',
            'user_updated' => 'integer',
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'idproveedor', 'idpersona');
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'idtrabajador', 'idpersona');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(IngresoEgresoCategoria::class, 'idotros_gastos_categoria', 'idingreso_egreso_categoria');
    }
}
