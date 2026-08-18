<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RDocumentoDetalle extends Model
{
    protected $table = 'rdocumento_detalle';

    protected $primaryKey = 'idrdocumento_detalle';

    public $incrementing = true;

    public $timestamps = false;

    protected $keyType = 'int';

    protected $fillable = [
        'idrdocumento',
        'idlote',
        'idproducto',
        'descripcion',
        'v_tipo_comprobante',
        'v_fecha_emision',
        'cantidad',
        'precio_compra',
        'precio_venta',
        'precio_venta_descuento',
        'descuento',
        'descuento_porcentaje',
        'subtotal',
        'subtotal_no_descuento',
    ];

    protected function casts(): array
    {
        return [
            'idrdocumento' => 'integer',
            'idlote' => 'integer',
            'idproducto' => 'integer',
            'v_fecha_emision' => 'datetime',
            'cantidad' => 'decimal:2',
            'precio_compra' => 'decimal:2',
            'precio_venta' => 'decimal:2',
            'precio_venta_descuento' => 'decimal:2',
            'descuento' => 'decimal:2',
            'descuento_porcentaje' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'subtotal_no_descuento' => 'decimal:2',
        ];
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(RDocumento::class, 'idrdocumento', 'idrdocumento');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'idlote', 'idlote');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'idproducto', 'idproducto');
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'idlote', 'idtours');
    }
}
