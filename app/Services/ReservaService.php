<?php

namespace App\Services;

use App\Models\Persona;
use App\Models\PersonaTipo;
use App\Models\PersonaTipoPersona;
use App\Models\Reserva;
use App\Models\ReservaHotelDetalle;
use App\Models\TourTurno;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservaService
{
    public function persistir(Reserva $reserva, array $data, bool $soloTours, int $usuarioId): Reserva
    {
        $esNueva = ! $reserva->exists;

        return DB::transaction(function () use ($reserva, $data, $soloTours, $usuarioId, $esNueva) {
            $codigo = $data['codigo'];

            $reserva->fill([
                'idtrabajador' => $data['idasesorreserva'],
                'idcliente' => $data['idpersona_cliente'],
                'llegada_ref_asesor' => $data['idasesorreserva'],
                'idorigen_reserva' => $data['idorigenreserva'],
                'idllegada_por_empresa' => $data['llegada_por_empresa'] ?? 1,
                'tours_paquete' => $soloTours ? 'SI' : 'NO',
                'serie_reserva' => strtok($codigo, '-'),
                'nro_reserva' => str_contains($codigo, '-') ? substr(strstr($codigo, '-'), 1) : $codigo,
                'serie_numero' => $codigo,
                'nro_pasajeros' => $data['numero_pasajero'],
                'fecha_llegada' => $data['llegada_fecha'] ?? null,
                'hora_llegada' => $data['llegada_hora'] ?? null,
                'fecha_salida' => $data['salida_fecha'] ?? null,
                'llegada_por' => $data['idllegada_por'] ?? null,
                'reserva_hotel' => $soloTours ? 'NO' : 'SI',
                'observacion_recojo' => $data['detalle_ubicacion_r'] ?? null,
                'itinerario_general' => $data['itinerario_reserva'] ?? null,
                'vuelo_ticket' => $soloTours ? null : ($data['vuelo_ticket'] ?? null),
                'vuelo_costo' => $soloTours ? 0 : ($data['monto_compra_vuelo'] ?? 0),
                'vuelo_observacion' => $soloTours ? null : ($data['obs_vuelo'] ?? null),
                'total_reserva' => $data['total_general_i'] ?? 0,
                'estado_trash' => '1',
                'user_updated' => $usuarioId,
            ]);

            if ($esNueva) {
                $reserva->user_created = $usuarioId;
            }

            $reserva->save();
            $this->guardarDetallesTours($data, $reserva, $usuarioId);

            if ($soloTours) {
                $reserva->hoteles()->delete();
            } else {
                $this->guardarDetallesHotel($data, $reserva, $usuarioId);
            }

            return $reserva->fresh();
        });
    }

    public function cargarParaFormulario(Reserva $reserva): array
    {
        $reserva->load(['detalles', 'hoteles.habitacion.hotel', 'cliente.docIdentidad', 'trabajador', 'origen', 'llegadaEmpresa']);

        return [
            'idreserva' => $reserva->idreserva,
            'numero_serie' => $reserva->serie_numero,
            'idpersona_cliente' => $reserva->idcliente,
            'idorigen_reserva' => $reserva->idorigen_reserva,
            'numero_pasajero' => $reserva->nro_pasajeros,
            'llegada_fecha' => optional($reserva->fecha_llegada)->format('Y-m-d'),
            'llegadahora' => $reserva->hora_llegada ? substr((string) $reserva->hora_llegada, 0, 5) : null,
            'salida_fecha' => optional($reserva->fecha_salida)->format('Y-m-d'),
            'idllegada_por_empresa' => $reserva->idllegada_por_empresa,
            'idpersona_trabajador' => $reserva->idtrabajador,
            'observacion_recojo' => $reserva->observacion_recojo,
            'itinerario_general' => $reserva->itinerario_general,
            'vuelo_ticket' => $reserva->vuelo_ticket,
            'vuelo_costo' => $reserva->vuelo_costo,
            'vuelo_observacion' => $reserva->vuelo_observacion,
            'tours_reserva' => $reserva->tours_paquete,
            'total_reserva' => $reserva->total_reserva,
            'turnos_tours' => TourTurno::where('estado_trash', '1')->get(['idtours_turno', 'descripcion as nombre']),
            'reserva_detalle' => $reserva->detalles,
            'hotel_detalle' => $reserva->hoteles->map(function ($detalle) {
                $habitacion = $detalle->habitacion;
                return [
                    'idreserva_hotel' => $detalle->idreserva_hotel_detalle,
                    'idhotel' => $habitacion?->idhotel,
                    'idhotel_habitacion' => $detalle->hotel_habitacion_idhotel_habitacion,
                    'nombre_habitacion' => $detalle->nombre_habitacion,
                    'nro_pax' => $detalle->nro_pax,
                    'cantidad_habitacion' => $detalle->cantidad_habitacion,
                    'fecha_check_in' => $detalle->fecha_check_in,
                    'fecha_check_out' => $detalle->fecha_check_out,
                    'nro_noches' => $detalle->nro_noches,
                    'precio' => $detalle->precio,
                    'adicional' => $detalle->adicional,
                    'observacion' => $detalle->observacion,
                ];
            }),
        ];
    }

    public function registrarClienteRapido(array $data, int $usuarioId): Persona
    {
        return DB::transaction(function () use ($data, $usuarioId) {
            $tipo = PersonaTipo::where('nombre', 'CLIENTE')->first();
            $personaExistente = Persona::query()
                ->where('tipo_documento', $data['cli_tipo_documento'])
                ->where('numero_documento', $data['cli_numero_documento'])
                ->first();

            if ($personaExistente) {
                if ($tipo && PersonaTipoPersona::where('idpersona', $personaExistente->idpersona)->where('idpersona_tipo', $tipo->idpersona_tipo)->exists()) {
                    throw ValidationException::withMessages([
                        'cli_numero_documento' => 'Esta persona ya esta registrada como cliente.',
                    ]);
                }

                if ($tipo) {
                    PersonaTipoPersona::firstOrCreate(
                        ['idpersona' => $personaExistente->idpersona, 'idpersona_tipo' => $tipo->idpersona_tipo],
                        ['user_created' => $usuarioId, 'user_updated' => $usuarioId]
                    );
                }

                return $personaExistente;
            }

            $persona = Persona::create([
                'codigo' => PersonaCodigoService::siguienteCodigo(),
                'tipo_persona_sunat' => $data['cli_tipo_persona_sunat'],
                'tipo_documento' => $data['cli_tipo_documento'],
                'numero_documento' => $data['cli_numero_documento'],
                'descripcion' => trim($data['cli_nombre_razonsocial']),
                'nombre_comercial' => $data['cli_apellidos_nombrecomercial'] ?? null,
                'nombre_persona_natural' => $data['cli_nombre_persona_natural'] ?? null,
                'apellido_paterno_persona_natural' => $data['cli_apellido_paterno_persona_natural'] ?? null,
                'apellido_materno_persona_natural' => $data['cli_apellido_materno_persona_natural'] ?? null,
                'sexo' => $data['cli_sexo'] ?? null,
                'fecha_nacimiento' => $data['cli_fecha_nacimiento'] ?? null,
                'nacionalidad' => $data['cli_nacionalidad'] ?? null,
                'estado_civil' => $data['cli_estado_civil'] ?? null,
                'correo' => $data['cli_correo'] ?? null,
                'celular' => $data['cli_celular'] ?? null,
                'direccion' => $data['cli_direccion'],
                'direccion_referencia' => $data['cli_direccion_referencia'] ?? null,
                'iddistrito' => $data['cli_distrito'] ?? null,
                'cod_ubigeo' => $data['cli_ubigeo'] ?? null,
                'estado_trash' => '1',
                'user_created' => $usuarioId,
                'user_updated' => $usuarioId,
            ]);

            if ($tipo) {
                PersonaTipoPersona::firstOrCreate(
                    ['idpersona' => $persona->idpersona, 'idpersona_tipo' => $tipo->idpersona_tipo],
                    ['user_created' => $usuarioId, 'user_updated' => $usuarioId]
                );
            }

            return $persona;
        });
    }

    private function guardarDetallesTours(array $data, Reserva $reserva, int $usuarioId): void
    {
        $reserva->detalles()->delete();
        $detalles = $data['detalles_tours'] ?? $this->normalizarDetallesToursLegacy($data);

        foreach ($detalles as $detalle) {
            if (empty($detalle['idtours'])) {
                continue;
            }

            $subtotal = $detalle['subtotal'] ?? 0;
            $reserva->detalles()->create([
                'idtours' => $detalle['idtours'],
                'idtours_turno' => $detalle['idtours_turno'] ?? 1,
                'nombre_tours' => $detalle['nombre_tours'] ?? null,
                'vehiculo' => $detalle['vehiculo'] ?? null,
                'nro_pax' => $detalle['nro_pax'] ?? 0,
                'fecha_tours' => $this->valorNullable($detalle['fecha_tours'] ?? null),
                'observacion' => $this->valorNullable($detalle['observacion'] ?? null),
                'precio' => $detalle['precio'] ?? 0,
                'subtotal' => $subtotal,
                'subtotal_no_descuento' => $subtotal,
                'estado_trash' => '1',
                'user_created' => $usuarioId,
                'user_updated' => $usuarioId,
            ]);
        }
    }

    private function guardarDetallesHotel(array $data, Reserva $reserva, int $usuarioId): void
    {
        $reserva->hoteles()->delete();
        $detalles = $data['detalles_hotel'] ?? $this->normalizarDetallesHotelLegacy($data);

        foreach ($detalles as $detalle) {
            if (empty($detalle['idhotel_habitacion'])) {
                continue;
            }

            $reserva->hoteles()->create([
                'hotel_habitacion_idhotel_habitacion' => $detalle['idhotel_habitacion'],
                'nombre_habitacion' => $detalle['nombre_habitacion'] ?? null,
                'nro_pax' => $detalle['nro_pax'] ?? 0,
                'cantidad_habitacion' => $detalle['cantidad_habitacion'] ?? 1,
                'fecha_check_in' => $this->valorNullable($detalle['fecha_check_in'] ?? null),
                'fecha_check_out' => $this->valorNullable($detalle['fecha_check_out'] ?? null),
                'nro_noches' => $detalle['nro_noches'] ?? 0,
                'precio' => $detalle['precio'] ?? 0,
                'adicional' => $detalle['adicional'] ?? 0,
                'observacion' => $this->valorNullable($detalle['observacion'] ?? null),
                'estado_trash' => '1',
                'user_created' => $usuarioId,
                'user_updated' => $usuarioId,
            ]);
        }
    }
    private function normalizarDetallesToursLegacy(array $data): array
    {
        return collect($data['id_select_tours'] ?? [])->map(function ($idTour, $index) use ($data) {
            return [
                'idtours' => $idTour,
                'idtours_turno' => $data['selecc_idtours_turno'][$index] ?? 1,
                'nombre_tours' => $data['nombre_tours'][$index] ?? null,
                'vehiculo' => $data['vehiculo'][$index] ?? null,
                'nro_pax' => $data['nro_pax_fila'][$index] ?? 0,
                'fecha_tours' => $data['fechaDetalle'][$index] ?? null,
                'observacion' => $data['desc_detalle'][$index] ?? null,
                'precio' => $data['precio_tours'][$index] ?? 0,
                'subtotal' => $data['subtotal_fila'][$index] ?? 0,
            ];
        })->all();
    }

    private function normalizarDetallesHotelLegacy(array $data): array
    {
        return collect($data['idhotel_habitacion'] ?? [])->map(function ($idHabitacion, $index) use ($data) {
            return [
                'idhotel_habitacion' => $idHabitacion,
                'nombre_habitacion' => $data['nombre_hotel_habitacion'][$index] ?? null,
                'nro_pax' => $data['nro_pax'][$index] ?? 0,
                'cantidad_habitacion' => $data['cant_hab'][$index] ?? 1,
                'fecha_check_in' => $data['fechallegada_hotel'][$index] ?? null,
                'fecha_check_out' => $data['fechasalida_hotel'][$index] ?? null,
                'nro_noches' => $data['noches'][$index] ?? 0,
                'precio' => $data['precio_coorporativo'][$index] ?? 0,
                'adicional' => $data['adicional'][$index] ?? 0,
                'observacion' => $data['observacion'][$index] ?? null,
            ];
        })->all();
    }
    private function valorNullable(mixed $valor): mixed
    {
        return $valor === '' ? null : $valor;
    }
}