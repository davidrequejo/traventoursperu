<?php

namespace App\Console\Commands;

use App\Mail\AlertaIncidenciasSunatMail;
use App\Models\RDocumento;
use App\Services\FacturacionElectronica\SunatFacturacionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarComprobantesSunatPendientes extends Command
{
    protected $signature = 'facturacion:enviar-pendientes
        {--limit=20 : Cantidad maxima de comprobantes por ejecucion}
        {--documento= : ID de rdocumento especifico para enviar}
        {--all : Procesa todos los comprobantes pendientes en bloques del tamano indicado por --limit}
        {--dry-run : Solo muestra los comprobantes pendientes, no envia}';

    protected $description = 'Envia automaticamente a SUNAT los comprobantes pendientes de facturacion.';

    private const TIPOS_PERMITIDOS = ['01', '03', '07', '08'];

    public function handle(SunatFacturacionService $sunatFacturacionService): int
    {
        $limit = max(1, min((int) $this->option('limit'), 100));
        $documentoId = $this->option('documento');
        $procesarTodos = (bool) $this->option('all');
        $dryRun = (bool) $this->option('dry-run');

        $lock = Cache::store('file')->lock('facturacion:enviar-pendientes', 900);

        if (! $lock->get()) {
            $this->warn('Ya existe una ejecucion de envio SUNAT en proceso.');
            Log::warning('Envio automatico SUNAT omitido: ya existe una ejecucion en proceso.');

            return self::SUCCESS;
        }

        try {
            $documentos = $this->documentosPendientes($limit, $documentoId)->get();

            if ($documentos->isEmpty()) {
                $this->info('No hay comprobantes pendientes para enviar a SUNAT.');

                return self::SUCCESS;
            }

            if ($dryRun) {
                $this->table(
                    ['ID', 'Tipo', 'Serie', 'Numero', 'Estado'],
                    $documentos->map(fn (RDocumento $documento) => [
                        $documento->idrdocumento,
                        $documento->tipo_comprobante,
                        $documento->serie_comprobante,
                        $documento->numero_comprobante,
                        $documento->sunat_estado,
                    ])->all()
                );

                $this->info("Dry-run: {$documentos->count()} comprobante(s) pendiente(s).");

                return self::SUCCESS;
            }

            $resumen = [
                'procesados' => 0,
                'aceptados' => 0,
                'rechazados' => 0,
                'errores' => 0,
                'omitidos' => 0,
            ];
            $incidencias = [];

            do {
                foreach ($documentos as $documento) {
                    $resultado = $this->enviarDocumento($documento, $sunatFacturacionService);
                    $resumen['procesados']++;
                    $resumen[$resultado['estado']]++;

                    if ($resultado['incidencia'] !== null) {
                        $incidencias[] = $resultado['incidencia'];
                    }
                }

                if (! $procesarTodos || $documentoId) {
                    break;
                }

                $documentos = $this->documentosPendientes($limit, null)->get();
            } while ($documentos->isNotEmpty());

            $this->info(sprintf(
                'Envio SUNAT finalizado. Procesados: %d, aceptados: %d, rechazados: %d, errores: %d, omitidos: %d.',
                $resumen['procesados'],
                $resumen['aceptados'],
                $resumen['rechazados'],
                $resumen['errores'],
                $resumen['omitidos']
            ));

            Log::info('Envio automatico SUNAT finalizado.', $resumen);
            $this->enviarAlertaIncidencias($resumen, $incidencias);

            return self::SUCCESS;
        } finally {
            optional($lock)->release();
        }
    }

    private function documentosPendientes(int $limit, mixed $documentoId)
    {
        return RDocumento::query()
            ->where('estado_trash', '1')
            ->whereIn('tipo_comprobante', self::TIPOS_PERMITIDOS)
            ->whereRaw("UPPER(TRIM(COALESCE(sunat_estado, ''))) = ?", ['POR ENVIAR'])
            ->when($documentoId, fn ($query) => $query->whereKey((int) $documentoId))
            ->orderBy('idrdocumento')
            ->limit($limit);
    }

    private function enviarDocumento(RDocumento $documento, SunatFacturacionService $sunatFacturacionService): array
    {
        $etiqueta = $this->etiquetaDocumento($documento);

        $reservado = RDocumento::query()
            ->whereKey($documento->getKey())
            ->whereRaw("UPPER(TRIM(COALESCE(sunat_estado, ''))) = ?", ['POR ENVIAR'])
            ->update([
                'sunat_estado' => 'ENVIANDO',
                'sunat_mensaje' => 'Envio automatico en proceso.',
                'sunat_error' => null,
            ]);

        if ($reservado !== 1) {
            $this->warn("Omitido {$etiqueta}: ya no esta pendiente.");

            return ['estado' => 'omitidos', 'incidencia' => null];
        }

        try {
            $this->line("Enviando {$etiqueta}...");
            $resultado = $sunatFacturacionService->enviar($documento->fresh());
            $estado = strtoupper(trim((string) ($resultado['estado'] ?? '')));

            if ($estado === 'ACEPTADA') {
                $this->info("Aceptado {$etiqueta}.");
                Log::info('Comprobante aceptado por SUNAT desde cron.', [
                    'idrdocumento' => $documento->getKey(),
                    'resultado' => $resultado,
                ]);

                return ['estado' => 'aceptados', 'incidencia' => null];
            }

            if ($estado === 'RECHAZADA') {
                $mensaje = (string) ($resultado['mensaje'] ?? 'SUNAT no acepto el comprobante.');
                $this->warn("Rechazado {$etiqueta}: {$mensaje}");
                Log::warning('Comprobante rechazado por SUNAT desde cron.', [
                    'idrdocumento' => $documento->getKey(),
                    'resultado' => $resultado,
                ]);

                return [
                    'estado' => 'rechazados',
                    'incidencia' => $this->incidencia($documento, 'RECHAZADA', $mensaje, $resultado),
                ];
            }

            $mensaje = (string) ($resultado['mensaje'] ?? 'SUNAT no acepto el comprobante.');
            $this->error("Error {$etiqueta}: {$mensaje}");
            Log::error('Comprobante con error SUNAT desde cron.', [
                'idrdocumento' => $documento->getKey(),
                'resultado' => $resultado,
            ]);

            return [
                'estado' => 'errores',
                'incidencia' => $this->incidencia($documento, $estado ?: 'ERROR_TECNICO', $mensaje, $resultado),
            ];
        } catch (\Throwable $e) {
            $this->marcarError($documento, $e);
            $this->error("Error {$etiqueta}: {$e->getMessage()}");
            Log::error('Error enviando comprobante a SUNAT desde cron.', [
                'idrdocumento' => $documento->getKey(),
                'error' => $e->getMessage(),
            ]);

            return [
                'estado' => 'errores',
                'incidencia' => $this->incidencia($documento, 'ERROR_TECNICO', $e->getMessage()),
            ];
        }
    }

    private function marcarError(RDocumento $documento, \Throwable $e): void
    {
        $documento->fresh()?->forceFill([
            'sunat_estado' => 'ERROR',
            'sunat_mensaje' => $e->getMessage(),
            'sunat_error' => $e->getMessage(),
        ])->save();
    }

    private function etiquetaDocumento(RDocumento $documento): string
    {
        return trim(collect([
            "#{$documento->idrdocumento}",
            $documento->tipo_comprobante,
            collect([$documento->serie_comprobante, $documento->numero_comprobante])->filter()->implode('-'),
        ])->filter()->implode(' '));
    }

    private function incidencia(RDocumento $documento, string $resultado, string $mensaje, array $respuesta = []): array
    {
        $documentoActual = $documento->fresh() ?: $documento;

        return [
            'id' => $documentoActual->idrdocumento,
            'comprobante' => collect([$documentoActual->serie_comprobante, $documentoActual->numero_comprobante])->filter()->implode('-'),
            'resultado' => $resultado,
            'mensaje' => trim(collect([
                $mensaje,
                filled($respuesta['code'] ?? null) ? 'Codigo: ' . $respuesta['code'] : null,
                filled($documentoActual->sunat_error) ? 'Error: ' . $documentoActual->sunat_error : null,
            ])->filter()->implode(' | ')),
        ];
    }

    private function enviarAlertaIncidencias(array $resumen, array $incidencias): void
    {
        if ($incidencias === []) {
            return;
        }

        $destinatarios = array_values((array) config('sunat.alerts.emails', []));

        if ($destinatarios === []) {
            $this->warn('Hay incidencias SUNAT, pero no hay correos configurados en SUNAT_ALERT_EMAILS.');
            Log::warning('Incidencias SUNAT sin correo de alerta configurado.', [
                'resumen' => $resumen,
                'incidencias' => $incidencias,
            ]);

            return;
        }

        $resumenCorreo = array_merge($resumen, [
            'fecha' => now()->format('d/m/Y H:i:s'),
        ]);

        try {
            Mail::to($destinatarios)->send(new AlertaIncidenciasSunatMail(
                $resumenCorreo,
                $incidencias,
                $this->ambienteSunat(),
                'CRON',
            ));

            $this->info('Correo de incidencias SUNAT enviado a: ' . implode(', ', $destinatarios));
            Log::info('Correo de incidencias SUNAT enviado.', [
                'destinatarios' => $destinatarios,
                'incidencias' => count($incidencias),
            ]);
        } catch (\Throwable $e) {
            $this->error('No se pudo enviar el correo de incidencias SUNAT: ' . $e->getMessage());
            Log::error('No se pudo enviar el correo de incidencias SUNAT.', [
                'destinatarios' => $destinatarios,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function ambienteSunat(): string
    {
        $empresa = \App\Models\Empresa::query()
            ->where('estado_trash', '1')
            ->latest('idempresa')
            ->first();

        return strtoupper((string) ($empresa?->fe_ambiente ?: 'PRODUCCION'));
    }
}
