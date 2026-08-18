<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\ReservaDetalle;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalidaController extends Controller
{
    public function index(Request $request)
    {
        return view('salidas', [
            'fecha_actual' => $this->fechaConsulta($request)->format('Y-m-d'),
        ]);
    }

    public function listar(Request $request)
    {
        $fecha = $this->fechaConsulta($request);

        $detalles = ReservaDetalle::with(['reserva.cliente.docIdentidad', 'reserva.asesor', 'tour', 'turno'])
            ->where('estado_trash', '1')
            ->whereDate('fecha_tours', $fecha->toDateString())
            ->whereHas('reserva', fn ($query) => $query->where('estado_trash', '1'))
            ->orderBy('nombre_tours')
            ->orderBy('idreserva_detalle')
            ->get();

        $grupos = $detalles
            ->groupBy(fn (ReservaDetalle $detalle) => (string) ($detalle->idtours ?: $detalle->nombre_tours ?: 'sin-tour'))
            ->map(fn ($rows) => $this->formatearGrupo($rows))
            ->sortBy('tour')
            ->values();

        return ApiResponse::success([
            'fecha' => $fecha->toDateString(),
            'titulo' => 'Cronograma de salidas: ' . $fecha->format('Y-m-d'),
            'total_tours' => $grupos->count(),
            'total_pax' => $grupos->sum('pax_total'),
            'items' => $grupos,
        ]);
    }

    private function fechaConsulta(Request $request): Carbon
    {
        $fecha = $request->input('fecha', now()->toDateString());

        try {
            return Carbon::parse($fecha)->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }
    }

    private function formatearGrupo($detalles): array
    {
        /** @var \App\Models\ReservaDetalle $primero */
        $primero = $detalles->first();
        $tour = $primero?->tour;
        $nombreTour = $tour?->nombre ?: $primero?->nombre_tours ?: 'Sin tour';

        return [
            'idtours' => $primero?->idtours,
            'tour' => $nombreTour,
            'hora_recojo' => $tour?->hora_recojo ? substr((string) $tour->hora_recojo, 0, 5) : '-',
            'hora_retorno' => $tour?->hora_retorno ? substr((string) $tour->hora_retorno, 0, 5) : '-',
            'pax_total' => $detalles->sum(fn (ReservaDetalle $detalle) => (int) ($detalle->nro_pax ?? 0)),
            'pax_compartido' => $detalles
                ->filter(fn (ReservaDetalle $detalle) => strcasecmp((string) ($detalle->vehiculo ?? ''), 'Compartido') === 0)
                ->sum(fn (ReservaDetalle $detalle) => (int) ($detalle->nro_pax ?? 0)),
            'pax_privado' => $detalles
                ->filter(fn (ReservaDetalle $detalle) => strcasecmp((string) ($detalle->vehiculo ?? ''), 'Privado') === 0)
                ->sum(fn (ReservaDetalle $detalle) => (int) ($detalle->nro_pax ?? 0)),
            'detalles' => $detalles->map(fn (ReservaDetalle $detalle) => $this->formatearDetalle($detalle))->values(),
        ];
    }

    private function formatearDetalle(ReservaDetalle $detalle): array
    {
        $reserva = $detalle->reserva;
        $cliente = $reserva?->cliente;

        return [
            'idreserva_detalle' => (int) $detalle->idreserva_detalle,
            'idreserva' => (int) ($detalle->idreserva ?? 0),
            'codigo_reserva' => $reserva?->serie_numero ?: '-',
            'cliente' => $cliente?->descripcion ?: '-',
            'documento' => $cliente?->numero_documento ?: '-',
            'telefono' => $cliente?->celular ?: '-',
            'asesor' => $reserva?->asesor?->descripcion ?: $reserva?->trabajador?->descripcion ?: '-',
            'vehiculo' => $detalle->vehiculo ?: 'Compartido',
            'turno' => $detalle->turno?->descripcion ?: '-',
            'pax' => (int) ($detalle->nro_pax ?? 0),
            'fecha_tours' => $detalle->fecha_tours ? Carbon::parse($detalle->fecha_tours)->format('Y-m-d') : '-',
            'fecha_tours_texto' => $detalle->fecha_tours ? Carbon::parse($detalle->fecha_tours)->format('d-m-Y') : '-',
            'observacion' => trim((string) ($detalle->observacion ?? '')),
            'recojo' => trim((string) ($reserva?->observacion_recojo ?? '')) ?: 'Sin Asignar',
        ];
    }
}
