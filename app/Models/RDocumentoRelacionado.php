<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RDocumentoRelacionado extends Model
{
    protected $table = 'rdocumento_relacionado';

    protected $primaryKey = 'idrdocumento_relacionado';

    public $incrementing = true;

    public $timestamps = false;

    protected $keyType = 'int';

    protected $fillable = [
        'idrdocumento',
        'idrdocumento_r',
        'idsunat_c12',
        'tipo_relacion',
        'serie_numero_relacionado',
        'orden_xml',
        'monto_original',
        'monto_aplicado',
        'estado_relacion',
    ];

    protected function casts(): array
    {
        return [
            'idrdocumento' => 'integer',
            'idrdocumento_r' => 'integer',
            'idsunat_c12' => 'integer',
            'orden_xml' => 'integer',
            'monto_original' => 'decimal:2',
            'monto_aplicado' => 'decimal:2',
        ];
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(RDocumento::class, 'idrdocumento', 'idrdocumento');
    }

    public function documentoRelacionado(): BelongsTo
    {
        return $this->belongsTo(RDocumento::class, 'idrdocumento_r', 'idrdocumento');
    }

    public function tipoDocumentoRelacionadoSunat(): BelongsTo
    {
        return $this->belongsTo(SunatC12DocumentoRelacionado::class, 'idsunat_c12', 'idsunat_c12_documento_relacionado');
    }
}
