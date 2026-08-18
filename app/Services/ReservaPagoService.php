<?php

namespace App\Services;

use App\Models\CuentaBancaria;
use App\Models\RDocumento;
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
        return RDocumento::with(['cliente', 'tipoComprobanteSunat', 'detalles', 'metodosPago'])
            ->where('origen', 'reserva')
            ->where('estado_trash', '1')
            ->where('observacion_documento', 'like', 'Reserva #' . $idreserva . '%')
            ->orderBy('fecha_emision')
            ->get();
    }

    public function pagosPorReservas(Collection $reservas): array
    {
        $ids = $reservas->pluck('idreserva')->map(fn ($id) => (int) $id)->filter()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $documentos = RDocumento::query()
            ->where('origen', 'reserva')
            ->where('estado_trash', '1')
            ->where(function ($query) use ($ids) {
                foreach ($ids as $id) {
                    $query->orWhere('observacion_documento', 'like', 'Reserva #' . $id . '%');
                }
            })
            ->get(['observacion_documento', 'venta_total']);

        $pagos = [];
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
        return (float) $this->pagosReserva((int) $reserva->idreserva)->sum(fn ($pago) => (float) $pago->venta_total);
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
                'sunat_estado' => 'POR ENVIAR',
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
        $montoActual = round((float) $documento->venta_total, 2);
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
                'observacion_documento' => trim('Reserva #' . $reserva->idreserva . ' - ' . ($data['observacion_amortizar'] ?? '')),
                'user_updated' => $usuarioId,
            ]);

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

            $documento->update([
                'estado_trash' => '0',
                'user_trash' => $usuarioId,
                'user_updated' => $usuarioId,
            ]);

            foreach ($documento->metodosPago as $metodoPago) {
                $metodoPago->update([
                    'estado_trash' => '0',
                    'user_trash' => $usuarioId,
                    'user_updated' => $usuarioId,
                ]);
            }

            return [
                'idrdocumento' => (int) $documento->idrdocumento,
                'idreserva' => $idreserva,
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
        $totalPagadoSinDocumento = max($this->totalPagado($reserva) - (float) $documento->venta_total, 0);

        return max((float) $reserva->total_reserva - $totalPagadoSinDocumento, 0);
    }

    private function idReservaDesdeDocumento(RDocumento $documento): int
    {
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
        if ($documento->origen !== 'reserva' || (string) $documento->estado_trash !== '1') {
            throw ValidationException::withMessages([
                'idrdocumento' => 'El pago seleccionado no esta disponible.',
            ]);
        }
    }
}
