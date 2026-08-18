<?php

namespace App\Http\Controllers;

use App\Models\SunatC03UnidadMedida;
use App\Models\SunatC05TipoTributo;
use App\Models\SunatC06DocIdentidad;
use App\Models\SunatC07AfeccionDeIgv;
use App\Models\SunatC09CodigoNotaCredito;
use App\Models\SunatC10CodigoNotaDebito;
use App\Models\SunatC11CodigoValorVenta;
use App\Models\SunatC18CodigoModalidadTransporte;
use App\Models\SunatC20CodigoMotivoTraslado;

class SunatCatalogoCodigoController extends Controller
{
    public function index()
    {
        $catalogos = collect([
            [
                'key' => 'c03',
                'codigo' => 'C03',
                'titulo' => 'Unidades de Medida',
                'descripcion' => 'Codigos de unidad comercial usados en items y servicios.',
                'icono' => 'ti ti-ruler-measure',
                'columns' => ['Codigo', 'Nombre', 'Equivalencia', 'Descripcion'],
                'rows' => SunatC03UnidadMedida::query()
                    ->select('abreviatura as codigo', 'nombre', 'equivalencia', 'descripcion')
                    ->orderBy('nombre')
                    ->get(),
            ],
            [
                'key' => 'c05',
                'codigo' => 'C05',
                'titulo' => 'Tipos de Tributo',
                'descripcion' => 'Codigos tributarios SUNAT y equivalencias UNECE.',
                'icono' => 'ti ti-receipt-tax',
                'columns' => ['Codigo', 'Nombre', 'Abreviatura', 'UNECE 5153'],
                'rows' => SunatC05TipoTributo::query()
                    ->select('code_sunat as codigo', 'nombre', 'abreviatura', 'unece5153')
                    ->orderBy('code_sunat')
                    ->get(),
            ],
            [
                'key' => 'c06',
                'codigo' => 'C06',
                'titulo' => 'Documentos de Identidad',
                'descripcion' => 'Tipos de documento aceptados para personas y empresas.',
                'icono' => 'ti ti-id',
                'columns' => ['Codigo', 'Nombre', 'Abreviatura'],
                'rows' => SunatC06DocIdentidad::query()
                    ->select('code_sunat as codigo', 'nombre', 'abreviatura')
                    ->orderBy('code_sunat')
                    ->get(),
            ],
            [
                'key' => 'c07',
                'codigo' => 'C07',
                'titulo' => 'Afectacion de IGV',
                'descripcion' => 'Codigos de afectacion al IGV para operaciones gravadas, exoneradas e inafectas.',
                'icono' => 'ti ti-percentage',
                'columns' => ['Codigo', 'Nombre', 'Tributo'],
                'rows' => SunatC07AfeccionDeIgv::query()
                    ->select('codigo', 'nombre', 'codigo_tributario')
                    ->orderBy('codigo')
                    ->get(),
            ],
            [
                'key' => 'c09',
                'codigo' => 'C09',
                'titulo' => 'Motivos de Nota de Credito',
                'descripcion' => 'Codigos de motivo aplicables a notas de credito.',
                'icono' => 'ti ti-file-minus',
                'columns' => ['Codigo', 'Nombre'],
                'rows' => SunatC09CodigoNotaCredito::query()
                    ->select('codigo', 'nombre')
                    ->orderBy('codigo')
                    ->get(),
            ],
            [
                'key' => 'c10',
                'codigo' => 'C10',
                'titulo' => 'Motivos de Nota de Debito',
                'descripcion' => 'Codigos de motivo aplicables a notas de debito.',
                'icono' => 'ti ti-file-plus',
                'columns' => ['Codigo', 'Nombre'],
                'rows' => SunatC10CodigoNotaDebito::query()
                    ->select('codigo', 'nombre')
                    ->orderBy('codigo')
                    ->get(),
            ],
            [
                'key' => 'c11',
                'codigo' => 'C11',
                'titulo' => 'Valor de Venta',
                'descripcion' => 'Clasificacion SUNAT del valor de venta.',
                'icono' => 'ti ti-report-money',
                'columns' => ['Codigo', 'Nombre'],
                'rows' => SunatC11CodigoValorVenta::query()
                    ->select('codigo', 'nombre')
                    ->orderBy('codigo')
                    ->get(),
            ],
            [
                'key' => 'c18',
                'codigo' => 'C18',
                'titulo' => 'Modalidad de Transporte',
                'descripcion' => 'Modalidades SUNAT para guias de remision.',
                'icono' => 'ti ti-truck-delivery',
                'columns' => ['Codigo', 'Nombre'],
                'rows' => SunatC18CodigoModalidadTransporte::query()
                    ->select('codigo', 'nombre')
                    ->orderBy('codigo')
                    ->get(),
            ],
            [
                'key' => 'c20',
                'codigo' => 'C20',
                'titulo' => 'Motivos de Traslado',
                'descripcion' => 'Motivos SUNAT usados para el traslado de bienes.',
                'icono' => 'ti ti-route',
                'columns' => ['Codigo', 'Nombre'],
                'rows' => SunatC20CodigoMotivoTraslado::query()
                    ->select('codigo', 'nombre')
                    ->orderBy('codigo')
                    ->get(),
            ],
        ]);

        return view('sunat_catalogo_codigo', [
            'catalogos' => $catalogos,
            'totalRegistros' => $catalogos->sum(fn ($catalogo) => $catalogo['rows']->count()),
        ]);
    }
}
