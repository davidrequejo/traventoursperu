<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class UbigeoDistrito extends Model
{
    protected $table = 'ubigeo_distrito';
    protected $primaryKey = 'idubigeo_distrito';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'idubigeo_distrito',
        'idubigeo_departamento',
        'idubigeo_provincia',
        'nombre',
        'codigo_postal',
        'ubigeo_reniec',
        'ubigeo_inei',
        'superficie',
        'altitud',
        'latitud',
        'longitud',
        'frontera',
        'estado',
    ];

    protected $casts = [
        'superficie' => 'decimal:4',
        'altitud' => 'decimal:4',
        'latitud' => 'decimal:4',
        'longitud' => 'decimal:4',
        'frontera' => 'boolean',
    ];

    public function departamento()
    {
        return $this->belongsTo(UbigeoDepartamento::class, 'idubigeo_departamento', 'idubigeo_departamento');
    }

    public function provincia()
    {
        return $this->belongsTo(UbigeoProvincia::class, 'idubigeo_provincia', 'idubigeo_provincia');
    }


    public function scopeConUbicacion(Builder $query): Builder
    {
        return $query
            ->join('ubigeo_provincia as p', 'ubigeo_distrito.idubigeo_provincia', '=', 'p.idubigeo_provincia')
            ->join('ubigeo_departamento as dep', 'p.idubigeo_departamento', '=', 'dep.idubigeo_departamento')
            ->select([
                'ubigeo_distrito.idubigeo_distrito',
                'ubigeo_distrito.nombre as distrito',
                'ubigeo_distrito.codigo_postal',
                'ubigeo_distrito.ubigeo_inei',
                'p.idubigeo_provincia',
                'p.nombre as provincia',
                'dep.idubigeo_departamento',
                'dep.nombre as departamento',
            ]);
    }
}
