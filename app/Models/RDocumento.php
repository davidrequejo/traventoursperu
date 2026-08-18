<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RDocumento extends Model
{
    protected $table = 'rdocumento';

    protected $primaryKey = 'idrdocumento';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'idpersona_cliente',
        'origen',
        'crear_enviar_sunat',
        'idsunat_c01',
        'idsunat_c51',
        'tipo_comprobante',
        'serie_comprobante',
        'numero_comprobante',
        'fecha_emision',
        'impuesto',
        'venta_subtotal',
        'venta_descuento',
        'venta_igv',
        'venta_total',
        'venta_cuotas',
        'vc_cantidad_total',
        'vc_cantidad_pagada',
        'vc_estado',
        'total_recibido',
        'total_vuelto',
        'ua_deduce_anticipos',
        'ant_es_anticipo',
        'ant_monto_original',
        'ant_monto_aplicado',
        'ant_monto_saldo',
        'ant_estado',
        'ua_total_bruto',
        'ua_monto_disponible_total',
        'ua_monto_deducido',
        'ua_total_pagar',
        'idsunat_c09',
        'nc_tipo_comprobante',
        'nc_serie_y_numero',
        'cot_tiempo_entrega',
        'cot_validez',
        'cot_estado',
        'idsunat_c18',
        'idsunat_c20',
        'gr_peso_total',
        'gr_peso_total_um',
        'gr_idconductor',
        'gr_placa',
        'gr_numero_licencia',
        'gr_partida_direccion',
        'gr_partida_distrito',
        'gr_partida_ubigeo',
        'gr_llegada_direccion',
        'gr_llegada_distrito',
        'gr_llegada_ubigeo',
        'sunat_estado',
        'sunat_observacion',
        'sunat_code',
        'sunat_mensaje',
        'sunat_hash',
        'sunat_error',
        'observacion_documento',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    protected function casts(): array
    {
        return [
            'idpersona_cliente' => 'integer',
            'idsunat_c01' => 'integer',
            'idsunat_c51' => 'integer',
            'idsunat_c09' => 'integer',
            'idsunat_c18' => 'integer',
            'idsunat_c20' => 'integer',
            'fecha_emision' => 'datetime',
            'impuesto' => 'decimal:2',
            'venta_subtotal' => 'decimal:2',
            'venta_descuento' => 'decimal:2',
            'venta_igv' => 'decimal:2',
            'venta_total' => 'decimal:2',
            'vc_cantidad_total' => 'integer',
            'vc_cantidad_pagada' => 'integer',
            'total_recibido' => 'decimal:2',
            'total_vuelto' => 'decimal:2',
            'ant_monto_original' => 'decimal:2',
            'ant_monto_aplicado' => 'decimal:2',
            'ant_monto_saldo' => 'decimal:2',
            'ua_total_bruto' => 'decimal:2',
            'ua_monto_disponible_total' => 'decimal:2',
            'ua_monto_deducido' => 'decimal:2',
            'ua_total_pagar' => 'decimal:2',
            'gr_peso_total' => 'decimal:2',
            'gr_idconductor' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'user_trash' => 'integer',
            'user_created' => 'integer',
            'user_updated' => 'integer',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'idpersona_cliente', 'idpersona');
    }

    public function tipoComprobanteSunat(): BelongsTo
    {
        return $this->belongsTo(SunatC01TipoComprobante::class, 'idsunat_c01', 'idsunat_c01_tipo_comprobante');
    }

    public function tipoOperacionSunat(): BelongsTo
    {
        return $this->belongsTo(SunatC51TipoOperacion::class, 'idsunat_c51', 'idsunat_c51_tipo_operacion');
    }

    public function codigoNotaCreditoSunat(): BelongsTo
    {
        return $this->belongsTo(SunatC09CodigoNotaCredito::class, 'idsunat_c09', 'idsunat_c09_codigo_nota_credito');
    }

    public function modalidadTransporteSunat(): BelongsTo
    {
        return $this->belongsTo(SunatC18CodigoModalidadTransporte::class, 'idsunat_c18', 'idsunat_c18_codigo_modalidad_transporte');
    }

    public function motivoTrasladoSunat(): BelongsTo
    {
        return $this->belongsTo(SunatC20CodigoMotivoTraslado::class, 'idsunat_c20', 'idsunat_c20_codigo_motivo_traslado');
    }

    public function conductor(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'gr_idconductor', 'idpersona');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(RDocumentoDetalle::class, 'idrdocumento', 'idrdocumento');
    }

    public function documentosRelacionados(): HasMany
    {
        return $this->hasMany(RDocumentoRelacionado::class, 'idrdocumento', 'idrdocumento');
    }

    public function anticiposDeducidos(): HasMany
    {
        return $this->documentosRelacionados()->where('tipo_relacion', 'anticipo_deducido');
    }

    public function relacionadoDeDocumentos(): HasMany
    {
        return $this->hasMany(RDocumentoRelacionado::class, 'idrdocumento_r', 'idrdocumento');
    }

    public function metodosPago(): HasMany
    {
        return $this->hasMany(RDocumentoMetodoPago::class, 'idrdocumento', 'idrdocumento');
    }

    public function cuotas(): HasMany
    {
        return $this->hasMany(RDocumentoCuota::class, 'idrdocumento', 'idrdocumento');
    }

    public function cuotasPagos(): HasMany
    {
        return $this->hasMany(RDocumentoCuotaPago::class, 'idrdocumento', 'idrdocumento');
    }

    public function archivos(): HasMany
    {
        return $this->hasMany(RDocumentoArchivo::class, 'idrdocumento', 'idrdocumento');
    }
}
