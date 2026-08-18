<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UbigeoProvincia extends Model
{
    protected $table = 'ubigeo_provincia';
    protected $primaryKey = 'idubigeo_provincia';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'idubigeo_provincia',
        'idubigeo_departamento',
        'nombre',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(UbigeoDepartamento::class, 'idubigeo_departamento', 'idubigeo_departamento');
    }

    public function distritos(): HasMany
    {
        return $this->hasMany(UbigeoDistrito::class, 'idubigeo_provincia', 'idubigeo_provincia');
    }
}
