<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Persona extends Model
{
    protected $table = 'persona';

    protected $primaryKey = 'idpersona';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'idcargo_trabajador',
        'codigo',
        'tipo_persona_sunat',
        'tipo_documento',
        'numero_documento',
        'descripcion',
        'nombre_comercial',
        'nombre_persona_natural',
        'apellido_paterno_persona_natural',
        'apellido_materno_persona_natural',
        'fecha_nacimiento',
        'nacionalidad',
        'estado_civil',
        'idconyuge',
        'celular',
        'direccion',
        'direccion_referencia',
        'iddistrito',
        'cod_ubigeo',
        'correo',
        'sexo',
        'numero_licencia',
        'placa_vehiculo',
        'foto_perfil',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'idconyuge' => 'integer',
        ];
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(PersonaCargo::class, 'idcargo_trabajador', 'idpersona_cargo');
    }

    public function docIdentidad(): BelongsTo
    {
        return $this->belongsTo(SunatC06DocIdentidad::class, 'tipo_documento', 'idsunat_c06_doc_identidad');
    }

    public function distrito(): BelongsTo
    {
        return $this->belongsTo(UbigeoDistrito::class, 'iddistrito', 'idubigeo_distrito');
    }

    public function conyuge(): BelongsTo
    {
        return $this->belongsTo(self::class, 'idconyuge', 'idpersona');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'idpersona', 'idpersona');
    }

    public function tiposPersona(): HasMany
    {
        return $this->hasMany(PersonaTipoPersona::class, 'idpersona', 'idpersona');
    }

    public function empresas(): HasMany
    {
        return $this->hasMany(Empresa::class, 'idpersona', 'idpersona');
    }
}
