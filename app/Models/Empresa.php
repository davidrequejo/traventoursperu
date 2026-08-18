<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Empresa extends Model
{
    protected $table = 'empresa';

    protected $primaryKey = 'idempresa';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $hidden = [
        'fe_sol_clave',
        'fe_certificado_password',
    ];

    protected $fillable = [
        'idpersona',
        'nombre_razon_social',
        'nombre_comercial',
        'domicilio_fiscal',
        'tipo_documento',
        'numero_documento',
        'telefono1',
        'telefono2',
        'correo',
        'web',
        'web_consulta_cp',
        'logo',
        'logo_c_r',
        'ubigueo',
        'codubigueo',
        'distrito',
        'provincia',
        'departamento',
        'codigo_pais',
        'pie_impresion',
        'igv',
        'fe_activo',
        'fe_ambiente',
        'fe_sol_usuario',
        'fe_sol_clave',
        'fe_certificado_archivo_base',
        'fe_certificado_archivo_pem',
        'fe_certificado_archivo_cer',
        'fe_certificado_hash',
        'fe_certificado_password',
        'fe_certificado_tipo',
        'fe_codigo_local',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    protected function casts(): array
    {
        return [
            'idpersona' => 'integer',
            'tipo_documento' => 'integer',
            'igv' => 'decimal:2',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'idpersona', 'idpersona');
    }

    public function docIdentidad(): BelongsTo
    {
        return $this->belongsTo(SunatC06DocIdentidad::class, 'tipo_documento', 'idsunat_c06_doc_identidad');
    }
}
