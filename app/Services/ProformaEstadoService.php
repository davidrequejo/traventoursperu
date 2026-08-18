<?php

namespace App\Services;

use App\Models\RDocumento;
use App\Models\RDocumentoCuota;
use Illuminate\Support\Facades\DB;

class ProformaEstadoService
{
    private const TOLERANCIA_MONTO = 0.009;

    public function sincronizarEstadosAutomaticos(RDocumento $proforma, int $idUsuario): array
    {
        $resumenProforma = $this->sincronizarEstadoProformaDesdePagos($proforma, $idUsuario);
        $resumenLotes = $this->sincronizarEstadoLotesDesdePagos($proforma, $idUsuario);

        return array_merge($resumenProforma, $resumenLotes, [
            'idrdocumento' => (int) $proforma->idrdocumento,
        ]);
    }

    public function sincronizarEstadoProformaDesdePagos(RDocumento $proforma, int $idUsuario): array
    {
        $cuotas = RDocumentoCuota::query()
            ->where('idrdocumento', (int) $proforma->idrdocumento)
            ->where('estado_trash', '1')
            ->get([
                'idrdocumento_cuota',
                'monto_cuota',
                'estado_cuota',
            ]);

        $idsCuotas = $cuotas->pluck('idrdocumento_cuota')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        $totalesPagados = collect();
        if (! empty($idsCuotas)) {
            $totalesPagados = DB::table('rdocumento_cuota_pago')
                ->whereIn('idrdocumento_cuota', $idsCuotas)
                ->where('estado_trash', '1')
                ->select([
                    'idrdocumento_cuota',
                    DB::raw('SUM(COALESCE(monto_aplicado, 0)) as total_pagado'),
                ])
                ->groupBy('idrdocumento_cuota')
                ->get()
                ->keyBy('idrdocumento_cuota');
        }

        $cantidadTotal = $cuotas->count();
        $cantidadPagada = 0;
        $montoTotalCuotas = 0.0;
        $montoPagadoTotal = 0.0;
        $cuotasActualizadas = 0;

        foreach ($cuotas as $cuota) {
            $idCuota = (int) $cuota->idrdocumento_cuota;
            $montoCuota = round(max((float) ($cuota->monto_cuota ?? 0), 0), 2);
            $montoPagado = round((float) ($totalesPagados[$idCuota]->total_pagado ?? 0), 2);

            $montoTotalCuotas += $montoCuota;
            $montoPagadoTotal += $montoPagado;

            if ($this->cuotaEstaPagada($montoCuota, $montoPagado)) {
                $cantidadPagada++;
            }

            $estadoActual = strtolower(trim((string) ($cuota->estado_cuota ?? 'pendiente')));
            $estadoNuevo = $this->resolverEstadoCuota($estadoActual, $montoCuota, $montoPagado);

            if ($estadoNuevo !== $estadoActual) {
                RDocumentoCuota::query()
                    ->where('idrdocumento_cuota', $idCuota)
                    ->update([
                        'estado_cuota' => $estadoNuevo,
                        'user_updated' => $idUsuario,
                    ]);
                $cuotasActualizadas++;
            }
        }

        $montoTotalCuotas = round($montoTotalCuotas, 2);
        $montoPagadoTotal = round($montoPagadoTotal, 2);

        $estadoProforma = 'pendiente';
        if ($cantidadTotal > 0 && $montoPagadoTotal >= ($montoTotalCuotas - self::TOLERANCIA_MONTO)) {
            $estadoProforma = 'pagado';
        } elseif ($montoPagadoTotal > self::TOLERANCIA_MONTO) {
            $estadoProforma = 'parcial';
        }

        $proforma->update([
            'vc_cantidad_total' => $cantidadTotal,
            'vc_cantidad_pagada' => $cantidadPagada,
            'vc_estado' => $estadoProforma,
            'user_updated' => $idUsuario,
        ]);

        return [
            'vc_estado' => $estadoProforma,
            'vc_cantidad_total' => $cantidadTotal,
            'vc_cantidad_pagada' => $cantidadPagada,
            'monto_total_cuotas' => $montoTotalCuotas,
            'monto_pagado_total' => $montoPagadoTotal,
            'cuotas_actualizadas' => $cuotasActualizadas,
        ];
    }

    public function sincronizarEstadoLotesDesdePagos(RDocumento $proforma, int $idUsuario): array
    {
        $idsLote = DB::table('rdocumento_detalle')
            ->where('idrdocumento', (int) $proforma->idrdocumento)
            ->whereNotNull('idlote')
            ->pluck('idlote')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (! count($idsLote)) {
            return [
                'estado_lote' => null,
                'lotes_actualizados' => 0,
                'lotes_estados' => [],
            ];
        }

        $statsPorLote = DB::table('rdocumento_detalle as d')
            ->join('rdocumento as r', 'r.idrdocumento', '=', 'd.idrdocumento')
            ->whereIn('d.idlote', $idsLote)
            ->whereNotNull('d.idlote')
            ->where('r.idsunat_c01', (int) $proforma->idsunat_c01)
            ->groupBy('d.idlote')
            ->select([
                'd.idlote',
                DB::raw("MAX(CASE WHEN r.estado_trash = '1' THEN 1 ELSE 0 END) as tiene_proforma_activa"),
                DB::raw("MAX(CASE WHEN r.estado_trash = '1' AND LOWER(COALESCE(r.vc_estado, 'pendiente')) = 'pagado' THEN 1 ELSE 0 END) as tiene_proforma_pagada"),
            ])
            ->get()
            ->keyBy('idlote');

        $estadoPorLote = [];
        foreach ($idsLote as $idLote) {
            $stats = $statsPorLote[$idLote] ?? null;
            $tieneActiva = (int) ($stats->tiene_proforma_activa ?? 0) === 1;
            $tienePagada = (int) ($stats->tiene_proforma_pagada ?? 0) === 1;

            $estadoPorLote[$idLote] = $tienePagada
                ? 'vendido'
                : ($tieneActiva ? 'separado' : 'disponible');
        }

        $actualizados = 0;
        $ahora = now();
        foreach (['vendido', 'separado', 'disponible'] as $estado) {
            $idsEstado = collect($estadoPorLote)
                ->filter(fn ($valor) => $valor === $estado)
                ->keys()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            if (! count($idsEstado)) {
                continue;
            }

            $actualizados += DB::table('lote')
                ->whereIn('idlote', $idsEstado)
                ->where('estado_trash', '1')
                ->where(function ($query) use ($estado) {
                    $query
                        ->whereNull('estado_lote')
                        ->orWhere('estado_lote', '!=', $estado);
                })
                ->update([
                    'estado_lote' => $estado,
                    'user_updated' => $idUsuario,
                    'updated_at' => $ahora,
                ]);
        }

        $estadosUnicos = collect($estadoPorLote)->values()->unique()->values();
        $estadoResumen = null;
        if ($estadosUnicos->count() === 1) {
            $estadoResumen = (string) $estadosUnicos->first();
        } elseif ($estadosUnicos->count() > 1) {
            $estadoResumen = 'mixto';
        }

        return [
            'estado_lote' => $estadoResumen,
            'lotes_actualizados' => (int) $actualizados,
            'lotes_estados' => $estadoPorLote,
        ];
    }

    private function cuotaEstaPagada(float $montoCuota, float $montoPagado): bool
    {
        if ($montoCuota <= 0) {
            return true;
        }

        return $montoPagado >= ($montoCuota - self::TOLERANCIA_MONTO);
    }

    private function resolverEstadoCuota(string $estadoActual, float $montoCuota, float $montoPagado): string
    {
        if ($this->cuotaEstaPagada($montoCuota, $montoPagado)) {
            return 'pagado';
        }

        if ($montoPagado > self::TOLERANCIA_MONTO) {
            return 'pendiente';
        }

        return $estadoActual === 'vencido' ? 'vencido' : 'pendiente';
    }
}
