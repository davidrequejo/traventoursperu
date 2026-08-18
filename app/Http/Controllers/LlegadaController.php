<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LlegadaController extends Controller
{
    public function index(Request $request)
    {
        return view('llegadas', [
            'fecha_actual' => $this->fechaConsulta($request)->format('Y-m-d'),
        ]);
    }

    public function listar(Request $request)
    {
        $fecha = $this->fechaConsulta($request);

        $reservas = Reserva::with(['cliente.docIdentidad', 'llegadaEmpresa.tipo', 'hoteles.habitacion.hotel.persona'])
            ->where('estado_trash', '1')
            ->whereDate('fecha_llegada', $fecha->toDateString())
            ->orderBy('hora_llegada')
            ->orderBy('idreserva')
            ->get();

        return ApiResponse::success([
            'fecha' => $fecha->toDateString(),
            'fecha_formateada' => $fecha->format('Y-m-d'),
            'titulo' => 'FECHA LLEGADA : ' . $fecha->format('Y-m-d') . ' (' . $reservas->sum(fn (Reserva $reserva) => (int) ($reserva->nro_pasajeros ?? 0)) . ' pax)',
            'total_reservas' => $reservas->count(),
            'total_pax' => $reservas->sum(fn (Reserva $reserva) => (int) ($reserva->nro_pasajeros ?? 0)),
            'items' => $reservas->map(fn (Reserva $reserva) => $this->formatearLlegada($reserva))->values(),
        ]);
    }

    public function asignarRecojo(Request $request, Reserva $reserva)
    {
        $validated = $request->validate([
            'observacion_recojo' => ['nullable', 'string', 'max:1000'],
        ]);

        $reserva->update([
            'observacion_recojo' => trim((string) ($validated['observacion_recojo'] ?? '')),
            'user_updated' => $request->user()?->id,
        ]);

        return ApiResponse::success($this->formatearLlegada($reserva->fresh(['cliente.docIdentidad', 'llegadaEmpresa.tipo', 'hoteles.habitacion.hotel.persona'])), 'Recojo actualizado correctamente.');
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

    private function formatearLlegada(Reserva $reserva): array
    {
        $cliente = $reserva->cliente;
        $hotel = $reserva->hoteles->first()?->habitacion?->hotel?->persona?->descripcion;
        $habitacion = $reserva->hoteles->first()?->nombre_habitacion;

        return [
            'idreserva' => (int) $reserva->idreserva,
            'codigo' => $reserva->serie_numero,
            'cliente' => $cliente?->descripcion ?: '-',
            'documento' => $cliente?->numero_documento ?: '-',
            'telefono' => $cliente?->celular ?: '-',
            'pax' => (int) ($reserva->nro_pasajeros ?? 0),
            'fecha_llegada' => optional($reserva->fecha_llegada)->format('Y-m-d'),
            'fecha_llegada_texto' => optional($reserva->fecha_llegada)->format('d-m-Y') ?: '-',
            'hora_llegada' => $reserva->hora_llegada ? substr((string) $reserva->hora_llegada, 0, 5) : '-',
            'llegada_empresa' => $reserva->llegadaEmpresa?->descripcion ?: '-',
            'llegada_tipo' => $reserva->llegadaEmpresa?->tipo?->descripcion ?: '-',
            'hotel' => $hotel ?: 'Sin Asignar',
            'habitacion' => $habitacion ?: '',
            'observacion_recojo' => trim((string) ($reserva->observacion_recojo ?? '')),
            'recojo_texto' => trim((string) ($reserva->observacion_recojo ?? '')) ?: 'Sin Asignar',
        ];
    }
}
