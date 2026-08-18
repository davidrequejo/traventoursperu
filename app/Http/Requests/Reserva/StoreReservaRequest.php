<?php

namespace App\Http\Requests\Reserva;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'detalles_tours' => $this->decodificarDetalleJson('detalles_tours_json'),
            'detalles_hotel' => $this->decodificarDetalleJson('detalles_hotel_json'),
        ]);
    }

    public function rules(): array
    {
        $soloTours = $this->soloTours();

        return [
            'idpersona_cliente' => ['required', 'integer', Rule::exists('persona', 'idpersona')->where('estado_trash', '1')],
            'idasesorreserva' => ['required', 'integer', Rule::exists('persona', 'idpersona')->where('estado_trash', '1')],
            'idorigenreserva' => ['required', 'integer', Rule::exists('origen_reserva', 'idorigen_reserva')->where('estado_trash', '1')],
            'idllegada_por' => [Rule::requiredIf(! $soloTours), 'nullable', 'integer', Rule::exists('llegada_tipo', 'idllegada_tipo')->where('estado_trash', '1')],
            'llegada_por_empresa' => [Rule::requiredIf(! $soloTours), 'nullable', 'integer', Rule::exists('llegada_por_empresa', 'idllegada_por_empresa')->where('estado_trash', '1')],
            'codigo' => ['required', 'string', 'max:20', Rule::unique('reserva', 'serie_numero')->ignore($this->reservaIdIgnorado(), 'idreserva')],
            'numero_pasajero' => ['required', 'integer', 'min:1'],
            'cant_ninos' => ['nullable', 'integer', 'min:0'],
            'cant_adultos' => ['nullable', 'integer', 'min:0'],
            'cant_ancianos' => ['nullable', 'integer', 'min:0'],
            'llegada_fecha' => [Rule::requiredIf(! $soloTours), 'nullable', 'date'],
            'llegada_hora' => [Rule::requiredIf(! $soloTours), 'nullable', 'date_format:H:i'],
            'salida_fecha' => [Rule::requiredIf(! $soloTours), 'nullable', 'date', 'after_or_equal:llegada_fecha'],
            'detalle_ubicacion_r' => ['nullable', 'string'],
            'itinerario_reserva' => ['nullable', 'string'],
            'vuelo_ticket' => ['nullable', 'string', 'max:255'],
            'obs_vuelo' => ['nullable', 'string'],
            'detalles_tours_json' => ['required', 'json'],
            'detalles_tours' => ['required', 'array', 'min:1'],
            'detalles_tours.*.idtours' => ['required', 'integer', Rule::exists('tours', 'idtours')->where('estado_trash', '1')],
            'detalles_tours.*.idtours_turno' => ['nullable', 'integer', Rule::exists('tours_turno', 'idtours_turno')->where('estado_trash', '1')],
            'detalles_tours.*.nombre_tours' => ['nullable', 'string'],
            'detalles_tours.*.vehiculo' => ['nullable', 'string'],
            'detalles_tours.*.nro_pax' => ['required', 'numeric', 'min:1'],
            'detalles_tours.*.fecha_tours' => ['nullable', 'date'],
            'detalles_tours.*.observacion' => ['nullable', 'string'],
            'detalles_tours.*.precio' => ['required', 'numeric', 'min:0'],
            'detalles_tours.*.subtotal' => ['required', 'numeric', 'min:0'],
            'detalles_hotel_json' => ['required', 'json'],
            'detalles_hotel' => $soloTours ? ['nullable', 'array'] : ['required', 'array', 'min:1'],
            'detalles_hotel.*.idhotel_habitacion' => ['nullable', 'integer', Rule::exists('hotel_habitacion', 'idhotel_habitacion')->where('estado_trash', '1')],
            'detalles_hotel.*.nombre_habitacion' => ['nullable', 'string'],
            'detalles_hotel.*.nro_pax' => ['nullable', 'numeric', 'min:0'],
            'detalles_hotel.*.cantidad_habitacion' => ['nullable', 'numeric', 'min:1'],
            'detalles_hotel.*.fecha_check_in' => ['nullable', 'date'],
            'detalles_hotel.*.fecha_check_out' => ['nullable', 'date'],
            'detalles_hotel.*.nro_noches' => ['nullable', 'numeric', 'min:0'],
            'detalles_hotel.*.precio' => ['nullable', 'numeric', 'min:0'],
            'detalles_hotel.*.adicional' => ['nullable', 'numeric'],
            'detalles_hotel.*.observacion' => ['nullable', 'string'],
            'total_general_i' => ['required', 'numeric', 'min:0'],
            'monto_compra_vuelo' => ['nullable', 'numeric', 'min:0'],
            'es_tour_solo' => ['nullable'],
        ];
    }

    public function soloTours(): bool
    {
        return $this->boolean('es_tour_solo');
    }

    protected function reservaIdIgnorado(): ?int
    {
        return null;
    }

    private function decodificarDetalleJson(string $campo): array
    {
        $json = $this->input($campo);
        if (! is_string($json) || trim($json) === '') {
            return [];
        }

        $detalle = json_decode($json, true);
        return is_array($detalle) ? $detalle : [];
    }
}