<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SunatC06DocIdentidad extends Model
{
    protected $table = 'sunat_c06_doc_identidad';

    protected $primaryKey = 'idsunat_c06_doc_identidad';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'nombre',
        'code_sunat',
        'abreviatura',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class, 'tipo_documento', 'idsunat_c06_doc_identidad');
    }

    public function empresas(): HasMany
    {
        return $this->hasMany(Empresa::class, 'tipo_documento', 'idsunat_c06_doc_identidad');
    }
}
