<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Producto extends Model
{
    protected $table = 'producto';

    protected $primaryKey = 'idproducto';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'idsunat_c03',
        'idcategoria',
        'idmarca',
        'tipo',
        'codigo',
        'codigo_alterno',
        'nombre',
        'stock',
        'stock_minimo',
        'precio_compra',
        'precio_venta',
        'descripcion_adicional',
        'imagen',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    protected function casts(): array
    {
        return [
            'idsunat_c03' => 'integer',
            'idcategoria' => 'integer',
            'idmarca' => 'integer',
            'stock' => 'decimal:2',
            'stock_minimo' => 'decimal:2',
            'precio_compra' => 'decimal:2',
            'precio_venta' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'user_trash' => 'integer',
            'user_created' => 'integer',
            'user_updated' => 'integer',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'idcategoria', 'idcategoria');
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class, 'idmarca', 'idmarca');
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(SunatC03UnidadMedida::class, 'idsunat_c03', 'idsunat_c03_unidad_medida');
    }
}
