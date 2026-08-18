<?php

namespace App\Services\FacturacionElectronica;

use App\Models\Empresa;
use App\Models\Persona;
use App\Models\RDocumento;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\DocumentInterface;
use Greenter\Model\Response\BaseResult;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\Note;
use Greenter\Model\Sale\Prepayment;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Report\XmlUtils;
use Greenter\See;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use RuntimeException;

class SunatFacturacionService
{
    private const DOCUMENTOS_PERMITIDOS = ['01', '03', '07', '08'];

    public function enviar(RDocumento $documento): array
    {
        $documento = $this->cargarDocumento($documento);
        $greenterDocumento = $this->crearDocumentoGreenter($documento);
        $see = $this->crearSee();
        $xmlFirmado = $see->getXmlSigned($greenterDocumento);
        $nombreArchivo = $greenterDocumento->getName();
        $tipoCarpeta = $this->carpetaPorTipoDocumento($documento);
        $this->guardarArchivo($tipoCarpeta, 'xml', "{$nombreArchivo}.xml", $xmlFirmado);
        $hash = (new XmlUtils())->getHashSign($xmlFirmado);

        try {
            $result = $see->sendXml(get_class($greenterDocumento), $nombreArchivo, $xmlFirmado);
        } catch (\Throwable $e) {
            $this->marcarError($documento, '', $e->getMessage(), $hash);
            $this->guardarArchivo('error', '', "{$nombreArchivo}.txt", $e->getMessage());

            throw $e;
        }

        $respuesta = $this->procesarRespuesta($documento, $result, $nombreArchivo, $tipoCarpeta, $hash);

        return array_merge($respuesta, [
            'xml_name' => "{$nombreArchivo}.xml",
        ]);
    }

    public function generarXml(RDocumento $documento): array
    {
        $documento = $this->cargarDocumento($documento);
        $greenterDocumento = $this->crearDocumentoGreenter($documento);
        $xmlFirmado = $this->crearSee()->getXmlSigned($greenterDocumento);
        $nombreArchivo = $greenterDocumento->getName();
        $tipoCarpeta = $this->carpetaPorTipoDocumento($documento);
        $xmlPath = $this->guardarArchivo($tipoCarpeta, 'xml', "{$nombreArchivo}.xml", $xmlFirmado);

        return [
            'name' => $nombreArchivo,
            'xml_path' => $xmlPath,
            'hash' => (new XmlUtils())->getHashSign($xmlFirmado),
        ];
    }

    private function crearSee(): See
    {
        $empresa = $this->empresaConfigurada();
        $ambiente = trim((string) ($empresa->fe_ambiente ?: 'beta'));
        $service = config("sunat.service.{$ambiente}") ?: config('sunat.service.beta');
        $usaDemoGreenter = $this->usaDemoGreenter($empresa);
        $credenciales = $this->credencialesSol($empresa, $usaDemoGreenter);

        if ($credenciales['ruc'] === '' || $credenciales['usuario'] === '' || $credenciales['clave'] === '') {
            throw new RuntimeException('Configure RUC, usuario SOL y clave SOL en la empresa.');
        }

        $see = new See();
        $see->setCertificate($this->leerCertificado($empresa, $usaDemoGreenter));
        $see->setService($service);
        $see->setClaveSOL($credenciales['ruc'], $credenciales['usuario'], $credenciales['clave']);

        return $see;
    }

    private function credencialesSol(Empresa $empresa, bool $usaDemoGreenter): array
    {
        if ($usaDemoGreenter) {
            return [
                'ruc' => trim((string) config('sunat.greenter_demo.ruc')),
                'usuario' => trim((string) config('sunat.greenter_demo.usuario')),
                'clave' => trim((string) config('sunat.greenter_demo.clave')),
            ];
        }

        return [
            'ruc' => trim((string) $empresa->numero_documento),
            'usuario' => trim((string) $empresa->fe_sol_usuario),
            'clave' => $this->desencriptar($empresa->fe_sol_clave),
        ];
    }

    private function leerCertificado(Empresa $empresa, bool $usaDemoGreenter = false): string
    {
        if ($usaDemoGreenter) {
            $path = (string) config('sunat.greenter_demo.certificado');
            if (! File::exists($path)) {
                throw new RuntimeException("No se encontro el certificado demo de Greenter en: {$path}");
            }

            return File::get($path);
        }

        $archivo = trim((string) ($empresa->fe_certificado_archivo_pem ?: $empresa->fe_certificado_archivo_base));
        if ($archivo === '') {
            throw new RuntimeException('Configure el certificado digital de facturacion electronica en la empresa.');
        }

        $path = (string) config('sunat.paths.certificado') . DIRECTORY_SEPARATOR . $archivo;

        if (! File::exists($path)) {
            throw new RuntimeException("No se encontro el certificado digital en: {$path}");
        }

        $contenido = File::get($path);
        $extension = strtolower((string) ($empresa->fe_certificado_tipo ?: pathinfo($path, PATHINFO_EXTENSION)));

        if (in_array($extension, ['p12', 'pfx'], true)) {
            $certificados = [];
            $password = $this->desencriptar($empresa->fe_certificado_password);
            if (! openssl_pkcs12_read($contenido, $certificados, $password)) {
                throw new RuntimeException('No se pudo leer el certificado P12/PFX. Revise la clave del certificado.');
            }

            return trim(($certificados['cert'] ?? '') . PHP_EOL . ($certificados['pkey'] ?? ''));
        }

        return $contenido;
    }

    private function usaDemoGreenter(Empresa $empresa): bool
    {
        return (string) ($empresa->fe_ambiente ?: 'beta') === 'beta'
            && (bool) config('sunat.greenter_demo.enabled', true);
    }

    private function crearDocumentoGreenter(RDocumento $documento): DocumentInterface
    {
        $tipoDocumento = $this->tipoDocumentoCodigo($documento);

        if (! in_array($tipoDocumento, self::DOCUMENTOS_PERMITIDOS, true)) {
            throw new RuntimeException("Tipo de documento SUNAT no soportado: {$tipoDocumento}");
        }

        return match ($tipoDocumento) {
            '07', '08' => $this->crearNota($documento, $tipoDocumento),
            default => $this->crearComprobante($documento, $tipoDocumento),
        };
    }

    private function crearComprobante(RDocumento $documento, string $tipoDocumento): Invoice
    {
        $invoice = new Invoice();
        $this->llenarDatosBase($invoice, $documento, $tipoDocumento);

        $invoice
            ->setTipoOperacion($this->tipoOperacionDocumento($documento))
            ->setValorVenta($this->valorVentaSunat($documento))
            ->setSubTotal($this->subTotalSunat($documento));

        $this->aplicarAnticiposGreenter($invoice, $documento);

        return $invoice;
    }

    private function crearNota(RDocumento $documento, string $tipoDocumento): Note
    {
        [$tipoAfectado, $numeroAfectado] = $this->documentoAfectado($documento);

        $note = new Note();
        $this->llenarDatosBase($note, $documento, $tipoDocumento);

        return $note
            ->setCodMotivo($this->codigoMotivoNota($documento, $tipoDocumento))
            ->setDesMotivo($this->descripcionMotivoNota($documento, $tipoDocumento))
            ->setTipDocAfectado($tipoAfectado)
            ->setNumDocfectado($numeroAfectado)
            ->setValorVenta($this->monto($documento->venta_subtotal))
            ->setSubTotal($this->monto($documento->venta_total));
    }

    private function llenarDatosBase(Invoice|Note $sale, RDocumento $documento, string $tipoDocumento): void
    {
        $subtotal = $sale instanceof Invoice ? $this->valorVentaSunat($documento) : $this->monto($documento->venta_subtotal);
        $total = $sale instanceof Invoice ? $this->totalPagarSunat($documento) : $this->monto($documento->venta_total);
        $afectacionIgv = (string) config('sunat.defaults.afectacion_igv', '20');
        $esGravada = str_starts_with($afectacionIgv, '1');
        $esExonerada = str_starts_with($afectacionIgv, '2');
        $igv = $esGravada ? $this->monto($documento->venta_igv) : 0;

        $sale
            ->setUblVersion('2.1')
            ->setTipoDoc($tipoDocumento)
            ->setSerie(trim((string) $documento->serie_comprobante))
            ->setCorrelativo(trim((string) $documento->numero_comprobante))
            ->setFechaEmision($this->fechaEmision($documento))
            ->setTipoMoneda((string) config('sunat.defaults.tipo_moneda', 'PEN'))
            ->setCompany($this->empresaGreenter())
            ->setClient($this->clienteGreenter($documento->cliente))
            ->setMtoOperGravadas($esGravada ? $subtotal : 0)
            ->setMtoOperExoneradas($esExonerada ? $subtotal : 0)
            ->setMtoOperInafectas(! $esGravada && ! $esExonerada ? $subtotal : 0)
            ->setMtoIGV($igv)
            ->setTotalImpuestos($igv)
            ->setMtoImpVenta($total)
            ->setDetails($this->detalles($documento, $subtotal))
            ->setLegends([
                (new Legend())
                    ->setCode((string) config('sunat.defaults.leyenda_importe_total', '1000'))
                    ->setValue($this->totalEnLetras($total)),
            ]);

        if ($sale instanceof Invoice) {
            $sale->setFormaPago($this->formaPagoDocumento($documento));
        }
    }

    private function detalles(RDocumento $documento, ?float $valorVentaObjetivo = null): array
    {
        $afectacionIgv = (string) config('sunat.defaults.afectacion_igv', '20');
        $esGravada = str_starts_with($afectacionIgv, '1');
        $porcentajeIgv = $esGravada ? (float) ($documento->impuesto ?? 0) : 0;
        $igvDocumento = $esGravada ? $this->monto($documento->venta_igv) : 0;
        $detalles = $documento->detalles->values();
        $totalDetalleOriginal = $this->monto($detalles->sum(fn ($detalle) => (float) ($detalle->subtotal ?? 0)));
        $totalDetalleObjetivo = $valorVentaObjetivo !== null ? $this->monto($valorVentaObjetivo) : $totalDetalleOriginal;
        $factor = $totalDetalleOriginal > 0 ? $totalDetalleObjetivo / $totalDetalleOriginal : 1;
        $ultimoIndice = max($detalles->count() - 1, 0);
        $acumuladoValorVenta = 0.00;

        return $detalles
            ->map(function ($detalle, int $index) use ($porcentajeIgv, $igvDocumento, $afectacionIgv, $factor, $ultimoIndice, $totalDetalleObjetivo, &$acumuladoValorVenta) {
                $cantidad = max((float) ($detalle->cantidad ?? 1), 1);
                $valorVentaBase = $this->monto($detalle->subtotal ?? 0);
                $valorVenta = $index === $ultimoIndice
                    ? $this->monto(max($totalDetalleObjetivo - $acumuladoValorVenta, 0))
                    : $this->monto($valorVentaBase * $factor);
                $acumuladoValorVenta = $this->monto($acumuladoValorVenta + $valorVenta);
                $valorUnitario = $this->monto($valorVenta / $cantidad);
                $igvItem = $igvDocumento > 0 ? $this->monto($valorVenta * ($porcentajeIgv / 100)) : 0;
                $precioUnitario = $igvDocumento > 0 ? $this->monto($valorUnitario * (1 + ($porcentajeIgv / 100))) : $valorUnitario;
                $descripcion = trim((string) ($detalle->descripcion ?: $detalle->producto?->nombre ?: 'Item'));
                $codigo = trim((string) ($detalle->producto?->codigo ?: ($index + 1)));
                $unidadMedida = trim((string) ($detalle->producto?->unidadMedida?->abreviatura ?: 'NIU'));

                return (new SaleDetail())
                    ->setUnidad($unidadMedida)
                    ->setCantidad($cantidad)
                    ->setCodProducto($codigo)
                    ->setDescripcion($descripcion)
                    ->setMtoValorUnitario($valorUnitario)
                    ->setMtoValorVenta($valorVenta)
                    ->setMtoBaseIgv($valorVenta)
                    ->setPorcentajeIgv($porcentajeIgv)
                    ->setIgv($igvItem)
                    ->setTipAfeIgv($afectacionIgv)
                    ->setTotalImpuestos($igvItem)
                    ->setMtoPrecioUnitario($precioUnitario);
            })
            ->all();
    }

    private function tipoOperacionDocumento(RDocumento $documento): string
    {
        $tipoDocumento = $this->tipoDocumentoCodigo($documento);
        $codigo = trim((string) ($documento->tipoOperacionSunat?->codigo ?? ''));
        if ($tipoDocumento === '03' && $codigo === '0104') {
            return '0101';
        }

        if ($codigo !== '') {
            return $codigo;
        }

        if (trim((string) ($documento->ant_es_anticipo ?? 'NO')) === 'SI') {
            return $tipoDocumento === '01' ? '0104' : '0101';
        }

        return (string) config('sunat.defaults.tipo_operacion', '0101');
    }

    private function documentoDeduceAnticipos(RDocumento $documento): bool
    {
        return trim((string) ($documento->ua_deduce_anticipos ?? 'NO')) === 'SI'
            && $this->monto($documento->ua_monto_deducido ?? 0) > 0;
    }

    private function valorVentaSunat(RDocumento $documento): float
    {
        if ($this->documentoDeduceAnticipos($documento)) {
            $bruto = $this->monto($documento->ua_total_bruto ?? 0);
            if ($bruto > 0) {
                return $bruto;
            }
        }

        return $this->monto($documento->venta_subtotal);
    }

    private function subTotalSunat(RDocumento $documento): float
    {
        if ($this->documentoDeduceAnticipos($documento)) {
            $bruto = $this->monto($documento->ua_total_bruto ?? 0);
            if ($bruto > 0) {
                return $bruto;
            }
        }

        return $this->monto($documento->venta_total);
    }

    private function totalPagarSunat(RDocumento $documento): float
    {
        if ($this->documentoDeduceAnticipos($documento)) {
            $totalPagar = $this->monto($documento->ua_total_pagar ?? 0);
            if ($totalPagar > 0) {
                return $totalPagar;
            }
        }

        return $this->monto($documento->venta_total);
    }

    private function aplicarAnticiposGreenter(Invoice $invoice, RDocumento $documento): void
    {
        if (! $this->documentoDeduceAnticipos($documento)) {
            return;
        }

        $anticipos = $documento->documentosRelacionados
            ->where('tipo_relacion', 'anticipo_deducido')
            ->where('estado_relacion', 'activo')
            ->sortBy('orden_xml')
            ->values()
            ->map(function ($relacion) {
                $codigoC12 = trim((string) ($relacion->tipoDocumentoRelacionadoSunat?->codigo ?? ''));
                $serieNumero = trim((string) ($relacion->serie_numero_relacionado ?? ''));
                $monto = $this->monto($relacion->monto_aplicado ?? 0);

                if ($codigoC12 === '' || $serieNumero === '' || $monto <= 0) {
                    return null;
                }

                return (new Prepayment())
                    ->setTipoDocRel($codigoC12)
                    ->setNroDocRel($serieNumero)
                    ->setTotal($monto);
            })
            ->filter()
            ->values()
            ->all();

        if (empty($anticipos)) {
            throw new RuntimeException('El comprobante deduce anticipos, pero no tiene relaciones activas de anticipo para el XML.');
        }

        $invoice
            ->setAnticipos($anticipos)
            ->setTotalAnticipos($this->monto($documento->ua_monto_deducido ?? 0));
    }

    private function formaPagoDocumento(RDocumento $documento): FormaPagoContado
    {
        $formaPago = strtolower(trim((string) ($documento->venta_cuotas ?: 'contado')));

        if (in_array($formaPago, ['no', 'n', '0', 'false'], true)) {
            $formaPago = 'contado';
        }

        if ($formaPago !== 'contado') {
            throw new RuntimeException("Forma de pago SUNAT no soportada por la facturacion basica: {$formaPago}");
        }

        return new FormaPagoContado();
    }

    private function empresaGreenter(): Company
    {
        $empresa = $this->empresaConfigurada();
        $ruc = trim((string) $empresa->numero_documento);
        $razonSocial = trim((string) $empresa->nombre_razon_social);
        $nombreComercial = trim((string) $empresa->nombre_comercial);

        if ($ruc === '' || $razonSocial === '') {
            throw new RuntimeException('Configure los datos fiscales del emisor en la empresa.');
        }

        $company = (new Company())
            ->setRuc($ruc)
            ->setRazonSocial($razonSocial)
            ->setAddress((new Address())
                ->setUbigueo(trim((string) ($empresa->codubigueo ?: $empresa->ubigueo)))
                ->setDepartamento(trim((string) $empresa->departamento))
                ->setProvincia(trim((string) $empresa->provincia))
                ->setDistrito(trim((string) $empresa->distrito))
                ->setDireccion(trim((string) $empresa->domicilio_fiscal))
                ->setCodigoPais(trim((string) ($empresa->codigo_pais ?: 'PE')))
                ->setCodLocal(trim((string) ($empresa->fe_codigo_local ?: '0000'))));

        return $nombreComercial !== ''
            ? $company->setNombreComercial($nombreComercial)
            : $company;
    }

    private function clienteGreenter(?Persona $cliente): Client
    {
        $numero = trim((string) ($cliente?->numero_documento ?? ''));
        $tipoDoc = trim((string) ($cliente?->docIdentidad?->code_sunat ?? ''));

        if ($tipoDoc === '') {
            $tipoDoc = strlen($numero) === 11 ? '6' : (strlen($numero) === 8 ? '1' : '0');
        }

        return (new Client())
            ->setTipoDoc($tipoDoc)
            ->setNumDoc($numero !== '' ? $numero : '-')
            ->setRznSocial($this->nombrePersona($cliente))
            ->setEmail(trim((string) ($cliente?->correo ?? '')));
    }

    private function procesarRespuesta(RDocumento $documento, ?BaseResult $result, string $nombreArchivo, string $tipoCarpeta, ?string $hash): array
    {
        if (! $result) {
            $this->marcarError($documento, '', 'SUNAT no devolvio respuesta.', $hash);
            return ['estado' => 'ERROR', 'mensaje' => 'SUNAT no devolvio respuesta.'];
        }

        if (! $result->isSuccess()) {
            $error = $result->getError();
            $code = trim((string) ($error?->getCode() ?? ''));
            $mensaje = trim((string) ($error?->getMessage() ?? 'Error al enviar a SUNAT.'));
            $this->marcarError($documento, $code, $mensaje, $hash);
            $this->guardarArchivo('error', '', "{$nombreArchivo}.txt", $mensaje);

            return ['estado' => 'ERROR', 'code' => $code, 'mensaje' => $mensaje];
        }

        $cdr = method_exists($result, 'getCdrResponse') ? $result->getCdrResponse() : null;
        $cdrZip = method_exists($result, 'getCdrZip') ? $result->getCdrZip() : null;
        if ($cdrZip) {
            $this->guardarArchivo($tipoCarpeta, 'cdr', "R-{$nombreArchivo}.zip", $cdrZip);
        }
        $observaciones = implode(PHP_EOL, $cdr?->getNotes() ?? []);
        $aceptado = (bool) $cdr?->isAccepted();

        $documento->forceFill([
            'sunat_estado' => $aceptado ? 'ACEPTADA' : 'RECHAZADA',
            'sunat_observacion' => $observaciones,
            'sunat_code' => trim((string) ($cdr?->getCode() ?? '')),
            'sunat_mensaje' => trim((string) ($cdr?->getDescription() ?? '')),
            'sunat_hash' => $hash,
            'sunat_error' => null,
        ])->save();

        if ($aceptado && $this->tipoDocumentoCodigo($documento) === '07') {
            $documento->documentosRelacionados
                ->pluck('documentoRelacionado')
                ->filter()
                ->each(function (RDocumento $documentoRelacionado) {
                    $documentoRelacionado->forceFill([
                        'sunat_estado' => 'ANULADO',
                    ])->save();
                });
        }

        return [
            'estado' => $documento->sunat_estado,
            'code' => $documento->sunat_code,
            'mensaje' => $documento->sunat_mensaje,
            'observacion' => $documento->sunat_observacion,
            'cdr_name' => $cdrZip ? "R-{$nombreArchivo}.zip" : null,
            'hash' => $hash,
        ];
    }

    private function marcarError(RDocumento $documento, string $code, string $mensaje, ?string $hash): void
    {
        $documento->forceFill([
            'sunat_estado' => 'ERROR',
            'sunat_code' => $code,
            'sunat_mensaje' => $mensaje,
            'sunat_error' => $mensaje,
            'sunat_hash' => $hash,
        ])->save();
    }

    private function cargarDocumento(RDocumento $documento): RDocumento
    {
        return RDocumento::query()
            ->with([
                'tipoComprobanteSunat',
                'tipoOperacionSunat',
                'codigoNotaCreditoSunat',
                'cliente.docIdentidad',
                'detalles.producto.unidadMedida',
                'documentosRelacionados.tipoDocumentoRelacionadoSunat',
                'documentosRelacionados.documentoRelacionado.tipoComprobanteSunat',
            ])
            ->findOrFail($documento->getKey());
    }

    private function documentoAfectado(RDocumento $documento): array
    {
        $tipo = trim((string) ($documento->nc_tipo_comprobante ?? ''));
        $numero = trim((string) ($documento->nc_serie_y_numero ?? ''));
        $relacionado = $documento->documentosRelacionados->first()?->documentoRelacionado;

        $tipo = $tipo ?: trim((string) ($relacionado?->tipoComprobanteSunat?->codigo ?? ''));
        $numero = $numero ?: trim(collect([$relacionado?->serie_comprobante, $relacionado?->numero_comprobante])->filter()->implode('-'));

        if ($tipo === '' || $numero === '') {
            throw new RuntimeException('La nota requiere comprobante afectado: tipo y serie-numero.');
        }

        return [$tipo, $numero];
    }

    private function codigoMotivoNota(RDocumento $documento, string $tipoDocumento): string
    {
        $codigo = trim((string) ($documento->codigoNotaCreditoSunat?->codigo ?: $documento->idsunat_c09));
        return $codigo !== '' ? str_pad($codigo, 2, '0', STR_PAD_LEFT) : ($tipoDocumento === '07' ? '01' : '01');
    }

    private function descripcionMotivoNota(RDocumento $documento, string $tipoDocumento): string
    {
        $observacion = trim((string) ($documento->observacion_documento ?? ''));
        $motivoNotaCredito = trim((string) ($documento->codigoNotaCreditoSunat?->nombre ?? ''));

        return $observacion !== ''
            ? $observacion
            : ($motivoNotaCredito !== '' ? $motivoNotaCredito : ($tipoDocumento === '07' ? 'Anulacion de la operacion' : 'Intereses por mora'));
    }

    private function tipoDocumentoCodigo(RDocumento $documento): string
    {
        return trim((string) ($documento->tipoComprobanteSunat?->codigo ?: $documento->tipo_comprobante));
    }

    private function carpetaPorTipoDocumento(RDocumento $documento): string
    {
        return (string) config('sunat.paths.documentos.' . $this->tipoDocumentoCodigo($documento), 'xml');
    }

    private function guardarArchivo(string $carpeta, string $subcarpeta, string $nombre, string $contenido): string
    {
        $base = (string) config('sunat.paths.base');
        $path = trim($subcarpeta) === ''
            ? $base . DIRECTORY_SEPARATOR . $carpeta
            : $base . DIRECTORY_SEPARATOR . $carpeta . DIRECTORY_SEPARATOR . $subcarpeta;

        File::ensureDirectoryExists($path);
        $fullPath = $path . DIRECTORY_SEPARATOR . $nombre;
        File::put($fullPath, $contenido);

        return $fullPath;
    }

    private function fechaEmision(RDocumento $documento): Carbon
    {
        return $documento->fecha_emision instanceof Carbon
            ? $documento->fecha_emision
            : Carbon::parse($documento->fecha_emision ?: now());
    }

    private function nombrePersona(?Persona $persona): string
    {
        if (! $persona) {
            return 'CLIENTE VARIOS';
        }

        $nombreNatural = trim(collect([
            $persona->nombre_persona_natural,
            $persona->apellido_paterno_persona_natural,
            $persona->apellido_materno_persona_natural,
        ])->filter()->implode(' '));

        return collect([
            $persona->descripcion,
            $persona->nombre_comercial,
            $nombreNatural,
        ])->first(fn ($valor) => filled($valor)) ?: 'CLIENTE VARIOS';
    }

    private function totalEnLetras(float $total): string
    {
        $entero = (int) floor($total);
        $centimos = (int) round(($total - $entero) * 100);

        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter('es_PE', \NumberFormatter::SPELLOUT);
            $letras = mb_strtoupper($formatter->format($entero));
            return "SON {$letras} CON " . str_pad((string) $centimos, 2, '0', STR_PAD_LEFT) . '/100 SOLES';
        }

        return 'SON ' . number_format($total, 2, '.', '') . ' SOLES';
    }

    private function monto(mixed $valor): float
    {
        return round((float) ($valor ?? 0), 2);
    }

    private function resolverPath(string $path): string
    {
        if ($path === '') {
            return '';
        }

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) || str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }

    private function empresaConfigurada(): Empresa
    {
        $empresa = Empresa::query()
            ->where('estado_trash', '1')
            ->latest('idempresa')
            ->first();

        if (! $empresa) {
            throw new RuntimeException('No existe una empresa activa configurada.');
        }

        if ((string) $empresa->fe_activo !== '1') {
            throw new RuntimeException('La facturacion electronica no esta activa en la configuracion de empresa.');
        }

        return $empresa;
    }

    private function desencriptar(?string $valor): string
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return '';
        }

        try {
            return Crypt::decryptString($valor);
        } catch (\Throwable) {
            return $valor;
        }
    }
}
