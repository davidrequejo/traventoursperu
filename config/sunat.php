<?php

use Greenter\Ws\Services\SunatEndpoints;

return [
    'service' => [
        'beta' => SunatEndpoints::FE_BETA,
        'production' => SunatEndpoints::FE_PRODUCCION,
    ],

    'greenter_demo' => [
        'enabled' => true,
        'ruc' => '20000000001',
        'usuario' => 'MODDATOS',
        'clave' => 'moddatos',
        'certificado' => public_path('assets/modulo/facturacion/certificado/greenter-demo.pem'),
    ],

    'defaults' => [
        'tipo_operacion' => '0101',
        'tipo_moneda' => 'PEN',
        'afectacion_igv' => '20',
        'leyenda_importe_total' => '1000',
    ],

    'paths' => [
        'base' => public_path('assets/modulo/facturacion'),
        'certificado' => public_path('assets/modulo/facturacion/certificado'),
        'error' => public_path('assets/modulo/facturacion/error'),
        'pdf' => public_path('assets/modulo/facturacion/pdf'),
        'documentos' => [
            '01' => 'factura',
            '03' => 'boleta',
            '07' => 'nota_credito',
            '08' => 'nota_debito',
        ],
    ],
];
