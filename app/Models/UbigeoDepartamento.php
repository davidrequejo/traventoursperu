<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UbigeoDepartamento extends Model
{
    protected $table = 'ubigeo_departamento';
    protected $primaryKey = 'idubigeo_departamento';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'idubigeo_departamento',
        'nombre',
        'region',
        'macroregion_inei',
        'macroregion_minsa',
        'iso_3166_2',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function provincias(): HasMany
    {
        return $this->hasMany(UbigeoProvincia::class, 'idubigeo_departamento', 'idubigeo_departamento');
    }

    public function distritos(): HasMany
    {
        return $this->hasMany(UbigeoDistrito::class, 'idubigeo_departamento', 'idubigeo_departamento');
    }
}
