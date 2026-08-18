<?php

namespace App\Services;

use App\Models\CuentaBancaria;
use App\Models\RDocumento;
use App\Models\RDocumentoCuota;
use App\Models\RDocumentoDetalle;
use App\Models\RDocumentoMetodoPago;
use App\Models\Reserva;
use App\Models\SerieComprobante;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservaPagoService
{
    public function pagosReserva(int $idreserva): Collection
    {
        $documentos = RDocumento::with(['cliente', 'tipoComprobanteSunat', 'detalles', 'metodosPago'])
            ->join('rdocumento_cuota as rc', 'rc.idrdocumento', '=', 'rdocumento.idrdocumento')
            ->where('rc.idreserva', $idreserva)
            ->where('rc.estado_trash', '1')
            ->where('rdocumento.estado_trash', '1')
            ->select('rdocumento.*')
            ->selectRaw('rc.idrdocumento_cuota as reserva_idrdocumento_cuota')
            ->selectRaw('rc.tipo as reserva_tipo_pago')
            ->selectRaw('rc.monto_cuota as reserva_monto_aplicado')
            ->orderBy('rdocumento.fecha_emision')
            ->get();

        $idsRelacionados = $documentos->pluck('idrdocumento')->map(fn ($id) => (int) $id)->all();

        $legacy = RDocumento::with(['cliente', 'tipoComprobanteSunat', 'detalles', 'metodosPago'])
            ->where('origen', 'reserva')
            ->where('estado_trash', '1')
            ->where('observacion_documento', 'like', 'Reserva #' . $idreserva . '%')
            ->when(! empty($idsRelacionados), fn ($query) => $query->whereNotIn('idrdocumento', $idsRelacionados))
            ->orderBy('fecha_emision')
            ->get()
            ->each(function (RDocumento $documento) {
                $documento->setAttribute('reserva_tipo_pago', 'legacy');
                $documento->setAttribute('reserva_monto_aplicado', (float) $documento->venta_total);
            });

        return $documentos->concat($legacy)->sortBy('fecha_emision')->values();
    }

    public function pagosPorReservas(Collection $reservas): array
    {
        $ids = $reservas->pluck('idreserva')->map(fn ($id) => (int) $id)->filter()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $pagos = DB::table('rdocumento_cuota as rc')
            ->join('rdocumento as rd', 'rd.idrdocumento', '=', 'rc.idrdocumento')
            ->whereIn('rc.idreserva', $ids)
            ->where('rc.estado_trash', '1')
            ->where('rd.estado_trash', '1')
            ->select('rc.idreserva', DB::raw('SUM(rc.monto_cuota) as total_pagado'))
            ->groupBy('rc.idreserva')
            ->pluck('total_pagado', 'idreserva')
            ->map(fn ($total) => (float) $total)
            ->all();

        $documentos = RDocumento::query()
            ->where('origen', 'reserva')
            ->where('estado_trash', '1')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('rdocumento_cuota as rc')
                    ->whereColumn('rc.idrdocumento', 'rdocumento.idrdocumento')
                    ->where('rc.estado_trash', '1');
            })
            ->where(function ($query) use ($ids) {
                foreach ($ids as $id) {
                    $query->orWhere('observacion_documento', 'like', 'Reserva #' . $id . '%');
                }
            })
            ->get(['observacion_documento', 'venta_total']);

        foreach ($documentos as $documento) {
            if (! preg_match('/Reserva #(\d+)/', (string) $documento->observacion_documento, $matches)) {
                continue;
            }

            $idReserva = (int) $matches[1];
            $pagos[$idReserva] = ($pagos[$idReserva] ?? 0) + (float) $documento->venta_total;
        }

        return $pagos;
    }

    public function totalPagado(Reserva $reserva): float
    {
        return (float) $this->pagosReserva((int) $reserva->idreserva)
            ->sum(fn ($pago) => (float) ($pago->reserva_monto_aplicado ?? $pago->venta_total));
    }

    public function saldoPendiente(Reserva $reserva): float
    {
        return max((float) $reserva->total_reserva - $this->totalPagado($reserva), 0);
    }

    public function registrarAmortizacion(array $data, int $usuarioId): array
    {
        return DB::transaction(function () use ($data, $usuarioId) {
            $reserva = Reserva::lockForUpdate()->findOrFail((int) $data['idreserva_amortizar']);
            $pendiente = $this->saldoPendiente($reserva);
            $monto = round((float) $data['monto_amortizar'], 2);

            if ($monto > $pendiente + 0.009) {
                throw ValidationException::withMessages([
                    'monto_amortizar' => 'El monto a amortizar no puede ser mayor al saldo pendiente.',
                    'pendiente' => number_format($pendiente, 2, '.', ''),
                ]);
            }

            $serie = SerieComprobante::lockForUpdate()->findOrFail((int) $data['f_serie_comprobante']);
            if ((int) $serie->idsunat_c01_tipo_comprobante !== (int) $data['f_idsunat_c01']) {
                throw ValidationException::withMessages([
                    'f_serie_comprobante' => 'La serie seleccionada no corresponde al tipo de comprobante.',
                ]);
            }

            $numero = (int) ($serie->numero ?? 0) + 1;
            $serie->update(['numero' => $numero, 'user_updated' => $usuarioId]);

            $documento = RDocumento::create([
                'idpersona_cliente' => (int) $data['p_idpersona_cliente'],
                'origen' => 'reserva',
                'crear_enviar_sunat' => 'NO',
                'idsunat_c01' => (int) $serie->idsunat_c01_tipo_comprobante,
                'tipo_comprobante' => (string) $data['f_tipo_comprobante'],
                'serie_comprobante' => $serie->serie,
                'numero_comprobante' => (string) $numero,
                'fecha_emision' => now('America/Lima'),
                'impuesto' => 0,
                'venta_subtotal' => $monto,
                'venta_descuento' => 0,
                'venta_igv' => 0,
                'venta_total' => $monto,
                'venta_cuotas' => 'contado',
                'vc_cantidad_total' => 1,
                'vc_cantidad_pagada' => 1,
                'vc_estado' => 'pagado',
                'total_recibido' => $monto,
                'total_vuelto' => 0,
                'sunat_estado' => $this->estadoSunatInicial((string) $data['f_tipo_comprobante']),
                'observacion_documento' => trim('Reserva #' . $reserva->idreserva . ' - ' . ($data['observacion_amortizar'] ?? '')),
                'estado_trash' => '1',
                'user_created' => $usuarioId,
                'user_updated' => $usuarioId,
            ]);

            RDocumentoDetalle::create([
                'idrdocumento' => (int) $documento->idrdocumento,
                'descripcion' => $data['detalle_comprobante_amortizar'] ?: ('Amortizacion reserva ' . ($reserva->serie_numero ?? $reserva->idreserva)),
                'v_tipo_comprobante' => (string) $data['f_tipo_comprobante'],
                'v_fecha_emision' => now('America/Lima'),
                'cantidad' => 1,
                'precio_compra' => 0,
                'precio_venta' => $monto,
                'precio_venta_descuento' => $monto,
                'descuento' => 0,
                'descuento_porcentaje' => 0,
                'subtotal' => $monto,
                'subtotal_no_descuento' => $monto,
            ]);

            RDocumentoMetodoPago::create([
                'idrdocumento' => (int) $documento->idrdocumento,
                'idcuenta_bancaria' => (int) $data['f_metodo_pago_1'],
                'monto' => $monto,
                'codigo_voucher' => null,
                'comprobante_nombre_visible' => null,
                'estado_trash' => '1',
                'user_created' => $usuarioId,
                'user_updated' => $usuarioId,
            ]);

            RDocumentoCuota::create([
                'idrdocumento' => (int) $documento->idrdocumento,
                'idreserva' => (int) $reserva->idreserva,
                'tipo' => 'pago',
                'numero_cuota' => 1,
                'fecha_cuota' => now('America/Lima')->toDateString(),
                'monto_cuota' => $monto,
                'estado_cuota' => 'pagado',
                'estado_trash' => '1',
                'user_created' => $usuarioId,
                'user_updated' => $usuarioId,
            ]);

            return [
                'idrdocumento' => (int) $documento->idrdocumento,
                'comprobante' => trim($documento->serie_comprobante . '-' . $documento->numero_comprobante),
                'idreserva' => (int) $reserva->idreserva,
            ];
        });
    }

    public function datosEdicion(RDocumento $documento): array
    {
        $this->validarDocumentoReserva($documento);
        $documento->loadMissing(['detalles', 'metodosPago.cuentaBancaria.banco']);

        $idreserva = $this->idReservaDesdeDocumento($documento);
        $reserva = Reserva::findOrFail($idreserva);
        $relacion = $this->relacionReservaDocumento($documento, $idreserva);
        $montoActual = round((float) ($relacion?->monto_cuota ?? $documento->venta_total), 2);
        $saldoDisponible = $this->saldoPendienteExcluyendoDocumento($reserva, $documento);
        $serie = SerieComprobante::query()
            ->where('serie', $documento->serie_comprobante)
            ->where('idsunat_c01_tipo_comprobante', $documento->idsunat_c01)
            ->first();
        $metodoPago = $documento->metodosPago->first();

        return [
            'idrdocumento' => (int) $documento->idrdocumento,
            'idreserva_amortizar' => $idreserva,
            'p_idpersona_cliente' => (int) $documento->idpersona_cliente,
            'f_tipo_comprobante' => (string) $documento->tipo_comprobante,
            'f_idsunat_c01' => (int) $documento->idsunat_c01,
            'f_serie_comprobante' => $serie?->idserie_comprobante,
            'f_serie_comprobante_label' => $serie?->serie ?: $documento->serie_comprobante,
            'f_metodo_pago_1' => $metodoPago?->idcuenta_bancaria,
            'f_metodo_pago_1_label' => $this->cuentaBancariaLabel($metodoPago?->cuentaBancaria),
            'monto_amortizar' => number_format($montoActual, 2, '.', ''),
            'total_amortizar' => number_format($saldoDisponible, 2, '.', ''),
            'saldo_amortizar' => number_format(max($saldoDisponible - $montoActual, 0), 2, '.', ''),
            'observacion_amortizar' => $this->observacionEditable($documento),
            'detalle_comprobante_amortizar' => $documento->detalles->first()?->descripcion,
        ];
    }

    public function actualizarAmortizacion(RDocumento $documento, array $data, int $usuarioId): array
    {
        return DB::transaction(function () use ($documento, $data, $usuarioId) {
            $documento = RDocumento::lockForUpdate()->with(['detalles', 'metodosPago'])->findOrFail((int) $documento->idrdocumento);
            $this->validarDocumentoReserva($documento);

            $reserva = Reserva::lockForUpdate()->findOrFail((int) $data['idreserva_amortizar']);
            if ($this->idReservaDesdeDocumento($documento) !== (int) $reserva->idreserva) {
                throw ValidationException::withMessages([
                    'idreserva_amortizar' => 'El pago no pertenece a la reserva indicada.',
                ]);
            }

            $monto = round((float) $data['monto_amortizar'], 2);
            $saldoDisponible = $this->saldoPendienteExcluyendoDocumento($reserva, $documento);

            if ($monto > $saldoDisponible + 0.009) {
                throw ValidationException::withMessages([
                    'monto_amortizar' => 'El monto del pago no puede ser mayor al saldo disponible.',
                    'pendiente' => number_format($saldoDisponible, 2, '.', ''),
                ]);
            }

            $serie = SerieComprobante::findOrFail((int) $data['f_serie_comprobante']);
            if ((int) $serie->idsunat_c01_tipo_comprobante !== (int) $data['f_idsunat_c01']) {
                throw ValidationException::withMessages([
                    'f_serie_comprobante' => 'La serie seleccionada no corresponde al tipo de comprobante.',
                ]);
            }

            $documento->update([
                'idpersona_cliente' => (int) $data['p_idpersona_cliente'],
                'idsunat_c01' => (int) $serie->idsunat_c01_tipo_comprobante,
                'tipo_comprobante' => (string) $data['f_tipo_comprobante'],
                'serie_comprobante' => $serie->serie,
                'venta_subtotal' => $monto,
                'venta_total' => $monto,
                'total_recibido' => $monto,
                'sunat_estado' => $this->estadoSunatInicial((string) $data['f_tipo_comprobante']),
                'observacion_documento' => trim('Reserva #' . $reserva->idreserva . ' - ' . ($data['observacion_amortizar'] ?? '')),
                'user_updated' => $usuarioId,
            ]);

            $this->actualizarRelacionReservaDocumento($documento, $reserva, $monto, $usuarioId, 'pago');

            $detalle = $documento->detalles->first();
            if ($detalle) {
                $detalle->update([
                    'descripcion' => $data['detalle_comprobante_amortizar'] ?: ('Amortizacion reserva ' . ($reserva->serie_numero ?? $reserva->idreserva)),
                    'v_tipo_comprobante' => (string) $data['f_tipo_comprobante'],
                    'precio_venta' => $monto,
                    'precio_venta_descuento' => $monto,
                    'subtotal' => $monto,
                    'subtotal_no_descuento' => $monto,
                ]);
            }

            $metodoPago = $documento->metodosPago->first();
            if ($metodoPago) {
                $metodoPago->update([
                    'idcuenta_bancaria' => (int) $data['f_metodo_pago_1'],
                    'monto' => $monto,
                    'user_updated' => $usuarioId,
                ]);
            }

            return [
                'idrdocumento' => (int) $documento->idrdocumento,
                'comprobante' => trim($documento->serie_comprobante . '-' . $documento->numero_comprobante),
                'idreserva' => (int) $reserva->idreserva,
            ];
        });
    }

    public function eliminarAmortizacion(RDocumento $documento, int $usuarioId): array
    {
        return DB::transaction(function () use ($documento, $usuarioId) {
            $documento = RDocumento::lockForUpdate()->with('metodosPago')->findOrFail((int) $documento->idrdocumento);
            $this->validarDocumentoReserva($documento);
            $idreserva = $this->idReservaDesdeDocumento($documento);
            $relacion = $this->relacionReservaDocumento($documento, $idreserva);

            if ($relacion && $relacion->tipo === 'asociacion') {
                $relacion->update([
                    'estado_trash' => '0',
                    'user_trash' => $usuarioId,
                    'user_updated' => $usuarioId,
                ]);
            } else {
                $documento->update([
                    'estado_trash' => '0',
                    'user_trash' => $usuarioId,
                    'user_updated' => $usuarioId,
                ]);

                if ($relacion) {
                    $relacion->update([
                        'estado_trash' => '0',
                        'user_trash' => $usuarioId,
                        'user_updated' => $usuarioId,
                    ]);
                }

                foreach ($documento->metodosPago as $metodoPago) {
                    $metodoPago->update([
                        'estado_trash' => '0',
                        'user_trash' => $usuarioId,
                        'user_updated' => $usuarioId,
                    ]);
                }
            }

            return [
                'idrdocumento' => (int) $documento->idrdocumento,
                'idreserva' => $idreserva,
            ];
        });
    }

    public function comprobantesAsociables(int $idreserva, ?string $term = null, bool $todos = false): Collection
    {
        $reserva = Reserva::findOrFail($idreserva);
        $term = trim((string) $term);

        return RDocumento::with(['cliente', 'tipoComprobanteSunat'])
            ->where('estado_trash', '1')
            ->where(function ($query) {
                $query->whereNull('origen')->orWhere('origen', '<>', 'reserva');
            })
            ->when(! $todos, fn ($query) => $query->where('idpersona_cliente', (int) $reserva->idcliente))
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('rdocumento_cuota as rc')
                    ->whereColumn('rc.idrdocumento', 'rdocumento.idrdocumento')
                    ->where('rc.estado_trash', '1');
            })
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($subQuery) use ($term) {
                    $subQuery->where('serie_comprobante', 'like', "%{$term}%")
                        ->orWhere('numero_comprobante', 'like', "%{$term}%")
                        ->orWhere('observacion_documento', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('fecha_emision')
            ->limit(30)
            ->get()
            ->map(function (RDocumento $documento) {
                $documento->setAttribute('monto_disponible_reserva', (float) $documento->venta_total);
                return $documento;
            });
    }

    public function asociarComprobante(array $data, int $usuarioId): array
    {
        return DB::transaction(function () use ($data, $usuarioId) {
            $reserva = Reserva::lockForUpdate()->findOrFail((int) $data['idreserva']);
            $documento = RDocumento::lockForUpdate()->findOrFail((int) $data['idrdocumento']);

            if ((string) $documento->estado_trash !== '1') {
                throw ValidationException::withMessages([
                    'idrdocumento' => 'El comprobante seleccionado no esta disponible.',
                ]);
            }

            if (RDocumentoCuota::query()
                ->where('idrdocumento', (int) $documento->idrdocumento)
                ->where('estado_trash', '1')
                ->exists()) {
                throw ValidationException::withMessages([
                    'idrdocumento' => 'El comprobante ya se encuentra asociado.',
                ]);
            }

            $monto = round((float) $data['monto_cuota'], 2);
            $saldo = $this->saldoPendiente($reserva);
            $totalDocumento = round((float) $documento->venta_total, 2);

            if ($monto <= 0) {
                throw ValidationException::withMessages([
                    'monto_cuota' => 'Ingrese un monto valido.',
                ]);
            }

            if ($monto > $saldo + 0.009) {
                throw ValidationException::withMessages([
                    'monto_cuota' => 'El monto aplicado no puede ser mayor al saldo pendiente.',
                    'pendiente' => number_format($saldo, 2, '.', ''),
                ]);
            }

            if ($monto > $totalDocumento + 0.009) {
                throw ValidationException::withMessages([
                    'monto_cuota' => 'El monto aplicado no puede ser mayor al total del comprobante.',
                ]);
            }

            RDocumentoCuota::create([
                'idrdocumento' => (int) $documento->idrdocumento,
                'idreserva' => (int) $reserva->idreserva,
                'tipo' => 'asociacion',
                'numero_cuota' => 1,
                'fecha_cuota' => now('America/Lima')->toDateString(),
                'monto_cuota' => $monto,
                'estado_cuota' => 'pagado',
                'estado_trash' => '1',
                'user_created' => $usuarioId,
                'user_updated' => $usuarioId,
            ]);

            return [
                'idrdocumento' => (int) $documento->idrdocumento,
                'comprobante' => trim($documento->serie_comprobante . '-' . $documento->numero_comprobante),
                'idreserva' => (int) $reserva->idreserva,
                'monto_aplicado' => number_format($monto, 2, '.', ''),
            ];
        });
    }

    private function cuentaBancariaLabel(?CuentaBancaria $cuenta): ?string
    {
        if (! $cuenta) {
            return null;
        }

        $nombre = trim((string) ($cuenta->banco?->alias ?: $cuenta->banco?->nombre ?: 'Cuenta'));
        $numero = trim((string) ($cuenta->cta_cte ?: $cuenta->cci ?: 'Sin numero'));
        $moneda = strtoupper(trim((string) ($cuenta->moneda ?? '')));

        return trim($nombre . ' - ' . $numero . ($moneda !== '' ? " ({$moneda})" : ''));
    }
    private function saldoPendienteExcluyendoDocumento(Reserva $reserva, RDocumento $documento): float
    {
        $relacion = $this->relacionReservaDocumento($documento, (int) $reserva->idreserva);
        $montoActual = (float) ($relacion?->monto_cuota ?? $documento->venta_total);
        $totalPagadoSinDocumento = max($this->totalPagado($reserva) - $montoActual, 0);

        return max((float) $reserva->total_reserva - $totalPagadoSinDocumento, 0);
    }

    private function idReservaDesdeDocumento(RDocumento $documento): int
    {
        $relacion = RDocumentoCuota::query()
            ->where('idrdocumento', (int) $documento->idrdocumento)
            ->where('estado_trash', '1')
            ->orderByDesc('idrdocumento_cuota')
            ->first();

        if ($relacion?->idreserva) {
            return (int) $relacion->idreserva;
        }

        if (! preg_match('/Reserva #(\d+)/', (string) $documento->observacion_documento, $matches)) {
            throw ValidationException::withMessages([
                'idrdocumento' => 'No se pudo identificar la reserva asociada al pago.',
            ]);
        }

        return (int) $matches[1];
    }

    private function observacionEditable(RDocumento $documento): string
    {
        return trim((string) preg_replace('/^Reserva #\d+\s*-\s*/', '', (string) $documento->observacion_documento));
    }

    private function validarDocumentoReserva(RDocumento $documento): void
    {
        if ((string) $documento->estado_trash !== '1') {
            throw ValidationException::withMessages([
                'idrdocumento' => 'El pago seleccionado no esta disponible.',
            ]);
        }

        if ($documento->origen === 'reserva') {
            return;
        }

        if (RDocumentoCuota::query()
            ->where('idrdocumento', (int) $documento->idrdocumento)
            ->where('estado_trash', '1')
            ->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'idrdocumento' => 'El pago seleccionado no esta disponible.',
        ]);
    }

    private function relacionReservaDocumento(RDocumento $documento, int $idreserva): ?RDocumentoCuota
    {
        return RDocumentoCuota::query()
            ->where('idrdocumento', (int) $documento->idrdocumento)
            ->where('idreserva', $idreserva)
            ->where('estado_trash', '1')
            ->orderByDesc('idrdocumento_cuota')
            ->first();
    }

    private function actualizarRelacionReservaDocumento(
        RDocumento $documento,
        Reserva $reserva,
        float $monto,
        int $usuarioId,
        string $tipo
    ): RDocumentoCuota {
        $relacion = $this->relacionReservaDocumento($documento, (int) $reserva->idreserva);
        $datos = [
            'idrdocumento' => (int) $documento->idrdocumento,
            'idreserva' => (int) $reserva->idreserva,
            'tipo' => $tipo,
            'numero_cuota' => 1,
            'fecha_cuota' => now('America/Lima')->toDateString(),
            'monto_cuota' => $monto,
            'estado_cuota' => 'pagado',
            'estado_trash' => '1',
            'user_updated' => $usuarioId,
        ];

        if ($relacion) {
            $relacion->update($datos);
            return $relacion;
        }

        $datos['user_created'] = $usuarioId;

        return RDocumentoCuota::create($datos);
    }

    private function estadoSunatInicial(string $tipoComprobante): string
    {
        return trim($tipoComprobante) === '12' ? 'ACEPTADA' : 'POR ENVIAR';
    }
}
