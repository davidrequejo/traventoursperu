<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SunatC03UnidadMedida extends Model
{
    protected $table = 'sunat_c03_unidad_medida';

    protected $primaryKey = 'idsunat_c03_unidad_medida';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'nombre',
        'abreviatura',
        'equivalencia',
        'descripcion',
        'estado',
        'estado_delete',
        'user_trash',
        'user_delete',
        'user_created',
        'user_updated',
    ];

    protected function casts(): array
    {
        return [
            'equivalencia' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'user_trash' => 'integer',
            'user_delete' => 'integer',
            'user_created' => 'integer',
            'user_updated' => 'integer',
        ];
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'idsunat_c03', 'idsunat_c03_unidad_medida');
    }
}
