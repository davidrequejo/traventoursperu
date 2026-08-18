<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Hotel;
use App\Models\HotelTipo;
use App\Models\Persona;
use App\Models\PersonaTipo;
use App\Models\PersonaTipoPersona;
use App\Models\UbigeoDistrito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HotelController extends Controller
{
    public function index()
    {
        return view('hoteles');
    }

    public function listar(Request $request)
    {
        $query = Hotel::with([
            'tipo:idhotel_tipo,nombre',
            'persona:idpersona,tipo_persona_sunat,tipo_documento,numero_documento,descripcion,celular,direccion,correo,iddistrito',
            'persona.distrito:idubigeo_distrito,nombre',
        ])->latest('idhotel');

        if (! $request->boolean('incluir_trash')) {
            $query->where('estado_trash', '1');
        }

        return ApiResponse::success($query->get());
    }

    public function show(Hotel $hotel)
    {
        return ApiResponse::success($hotel->load([
            'tipo',
            'persona.distrito',
            'habitaciones' => fn ($query) => $query->where('estado_trash', '1'),
        ]));
    }

    public function store(Request $request)
    {
        return $this->guardar($request);
    }

    public function update(Request $request, Hotel $hotel)
    {
        return $this->guardar($request, $hotel);
    }

    private function guardar(Request $request, ?Hotel $hotel = null)
    {
        $validado = $this->validarHotel($request);

        return DB::transaction(function () use ($request, $validado, $hotel) {
            $nuevo = ! $hotel;
            $persona = $this->guardarPersonaHotel($request, $validado['persona']);
            $hotelData = $validado['hotel'] + ['idpersona' => $persona->idpersona];

            if ($nuevo) {
                $hotel = Hotel::create($hotelData + [
                    'estado_trash' => '1',
                    'user_created' => $request->user()->id,
                    'user_updated' => $request->user()->id,
                ]);
            } else {
                $hotel->update($hotelData + [
                    'user_updated' => $request->user()->id,
                ]);
            }

            $this->guardarHabitaciones($request, $hotel, $validado['habitaciones']);

            return ApiResponse::success(
                $hotel->fresh()->load(['persona.distrito', 'tipo', 'habitaciones' => fn ($query) => $query->where('estado_trash', '1')]),
                $nuevo ? 'Hotel registrado correctamente.' : 'Hotel actualizado correctamente.'
            );
        });
    }

    private function validarHotel(Request $request): array
    {
        $request->merge([
            'check_in' => blank($request->input('check_in')) ? null : substr($request->input('check_in'), 0, 5),
            'check_out' => blank($request->input('check_out')) ? null : substr($request->input('check_out'), 0, 5),
        ]);

        $data = $request->validate([
            'idpersona' => ['nullable', 'integer', Rule::exists('persona', 'idpersona')->where('estado_trash', '1')],
            'persona.tipo_persona_sunat' => ['required', Rule::in(['NATURAL', 'JURIDICA'])],
            'persona.tipo_documento' => ['required', 'integer', 'exists:sunat_c06_doc_identidad,idsunat_c06_doc_identidad'],
            'persona.numero_documento' => ['required', 'string', 'max:20'],
            'persona.descripcion' => ['required', 'string', 'max:250'],
            'persona.celular' => ['nullable', 'string', 'max:20'],
            'persona.direccion' => ['nullable', 'string', 'max:255'],
            'persona.iddistrito' => ['nullable', 'integer', 'exists:ubigeo_distrito,idubigeo_distrito'],
            'persona.correo' => ['nullable', 'email', 'max:150'],
            'idhotel_tipo' => ['required', 'integer', Rule::exists('hotel_tipo', 'idhotel_tipo')->where('estado_trash', '1')],
            'estrellas' => ['nullable', 'string', 'max:45'],
            'tarifa_x_pers_paq' => ['nullable', 'numeric', 'min:0'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'descripcion' => ['nullable', 'string'],
            'gogle_maps' => ['nullable', 'string'],
            'habitaciones' => ['nullable', 'array'],
            'habitaciones.*.idhotel_habitacion' => ['nullable', 'integer', 'exists:hotel_habitacion,idhotel_habitacion'],
            'habitaciones.*.nombre' => ['nullable', 'string', 'max:120'],
            'habitaciones.*.cant_huespeds' => ['nullable', 'integer', 'min:1'],
            'habitaciones.*.precio_coorporativo' => ['nullable', 'numeric', 'min:0'],
            'habitaciones.*.precio_normal' => ['nullable', 'numeric', 'min:0'],
            'habitaciones.*.precio_temp_alta' => ['nullable', 'numeric', 'min:0'],
            'habitaciones.*.descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        return [
            'persona' => $data['persona'],
            'hotel' => collect($data)->only([
                'idhotel_tipo',
                'estrellas',
                'tarifa_x_pers_paq',
                'check_in',
                'check_out',
                'descripcion',
                'gogle_maps',
            ])->all(),
            'habitaciones' => collect($data['habitaciones'] ?? [])
                ->filter(fn ($habitacion) => filled($habitacion['nombre'] ?? null))
                ->values()
                ->all(),
        ];
    }

    private function guardarPersonaHotel(Request $request, array $data): Persona
    {
        $persona = $request->filled('idpersona')
            ? Persona::where('estado_trash', '1')->findOrFail($request->integer('idpersona'))
            : Persona::where('tipo_documento', $data['tipo_documento'])
                ->where('numero_documento', $data['numero_documento'])
                ->where('estado_trash', '1')
                ->first();

        $duplicado = Persona::where('tipo_documento', $data['tipo_documento'])
            ->where('numero_documento', $data['numero_documento'])
            ->when($persona, fn ($query) => $query->where('idpersona', '<>', $persona->idpersona))
            ->exists();

        if ($duplicado) {
            throw ValidationException::withMessages([
                'persona.numero_documento' => 'Ya existe una persona con ese tipo y numero de documento.',
            ]);
        }

        if ($persona) {
            $persona->update($data + ['user_updated' => $request->user()->id]);
        } else {
            $persona = Persona::create($data + [
                'estado_trash' => '1',
                'user_created' => $request->user()->id,
                'user_updated' => $request->user()->id,
            ]);
        }

        $tipoHotel = $this->tipoPersonaHotel($request);
        PersonaTipoPersona::firstOrCreate(
            ['idpersona' => $persona->idpersona, 'idpersona_tipo' => $tipoHotel->idpersona_tipo],
            ['user_created' => $request->user()->id, 'user_updated' => $request->user()->id]
        );

        return $persona;
    }

    private function tipoPersonaHotel(Request $request): PersonaTipo
    {
        $tipo = PersonaTipo::whereRaw('UPPER(TRIM(nombre)) = ?', ['HOTEL'])->first();

        if ($tipo) {
            if ($tipo->estado_trash !== '1') {
                $tipo->update([
                    'estado_trash' => '1',
                    'user_updated' => $request->user()->id,
                ]);
            }

            return $tipo;
        }

        return PersonaTipo::create([
            'nombre' => 'HOTEL',
            'descripcion' => 'Personas vinculadas al modulo de hoteles',
            'estado_trash' => '1',
            'user_created' => $request->user()->id,
            'user_updated' => $request->user()->id,
        ]);
    }
    private function guardarHabitaciones(Request $request, Hotel $hotel, array $habitaciones): void
    {
        $idsEnviados = collect($habitaciones)->pluck('idhotel_habitacion')->filter()->map(fn ($id) => (int) $id)->all();

        $hotel->habitaciones()
            ->when($idsEnviados, fn ($query) => $query->whereNotIn('idhotel_habitacion', $idsEnviados))
            ->update([
                'estado_trash' => '0',
                'user_trash' => $request->user()->id,
                'user_updated' => $request->user()->id,
            ]);

        foreach ($habitaciones as $habitacion) {
            $data = collect($habitacion)->only([
                'nombre',
                'cant_huespeds',
                'precio_coorporativo',
                'precio_normal',
                'precio_temp_alta',
                'descripcion',
            ])->all();

            if (! empty($habitacion['idhotel_habitacion'])) {
                $existente = $hotel->habitaciones()->whereKey($habitacion['idhotel_habitacion'])->first();

                if (! $existente) {
                    throw ValidationException::withMessages([
                        'habitaciones' => 'Una de las habitaciones no pertenece al hotel seleccionado.',
                    ]);
                }

                $existente->update($data + [
                    'estado_trash' => '1',
                    'user_updated' => $request->user()->id,
                ]);

                continue;
            }

            $hotel->habitaciones()->create($data + [
                'estado_trash' => '1',
                'user_created' => $request->user()->id,
                'user_updated' => $request->user()->id,
            ]);
        }
    }

    public function destroy(Request $request, Hotel $hotel)
    {
        $hotel->update([
            'estado_trash' => '0',
            'user_trash' => $request->user()->id,
            'user_updated' => $request->user()->id,
        ]);

        return ApiResponse::success($hotel);
    }

    public function catalogos()
    {
        return ApiResponse::success([
            'tipos' => HotelTipo::where('estado_trash', '1')->get(['idhotel_tipo', 'nombre']),
        ]);
    }

    public function distritos(Request $request)
    {
        $search = $request->input('term', '');

        return response()->json([
            'results' => UbigeoDistrito::where('nombre', 'like', "%{$search}%")
                ->limit(15)
                ->get()
                ->map(fn ($distrito) => ['id' => $distrito->idubigeo_distrito, 'text' => $distrito->nombre]),
        ]);
    }

    public function buscarPersonaHotel(Request $request)
    {
        $validado = $request->validate(['numero_documento' => 'required|string|max:20']);
        $persona = Persona::with('distrito:idubigeo_distrito,nombre')
            ->where('numero_documento', $validado['numero_documento'])
            ->where('estado_trash', '1')
            ->first();

        return ApiResponse::success(['existe_persona' => (bool) $persona, 'persona' => $persona]);
    }

    public function updatePersona(Request $request, Persona $persona)
    {
        $validado = $request->validate([
            'tipo_persona_sunat' => 'required|in:NATURAL,JURIDICA',
            'tipo_documento' => 'required|integer|exists:sunat_c06_doc_identidad,idsunat_c06_doc_identidad',
            'numero_documento' => 'required|string|max:20',
            'descripcion' => 'required|string|max:250',
            'celular' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'iddistrito' => 'nullable|integer|exists:ubigeo_distrito,idubigeo_distrito',
            'correo' => 'nullable|email|max:150',
        ]);

        $duplicado = Persona::where('tipo_documento', $validado['tipo_documento'])
            ->where('numero_documento', $validado['numero_documento'])
            ->where('idpersona', '<>', $persona->idpersona)
            ->exists();

        if ($duplicado) {
            return ApiResponse::fail('Ya existe una persona con ese tipo y numero de documento.', 422);
        }

        $persona->update($validado + ['user_updated' => $request->user()->id]);

        return ApiResponse::success($persona->fresh()->load('distrito'), 'Persona actualizada correctamente.');
    }

    public function storePersona(Request $request)
    {
        $validado = $request->validate([
            'tipo_persona_sunat' => 'required|in:NATURAL,JURIDICA',
            'tipo_documento' => 'required|integer|exists:sunat_c06_doc_identidad,idsunat_c06_doc_identidad',
            'numero_documento' => 'required|string|max:20',
            'descripcion' => 'required|string|max:250',
            'celular' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'iddistrito' => 'nullable|integer|exists:ubigeo_distrito,idubigeo_distrito',
            'correo' => 'nullable|email|max:150',
        ]);

        $persona = Persona::firstOrCreate(
            ['tipo_documento' => $validado['tipo_documento'], 'numero_documento' => $validado['numero_documento']],
            $validado + ['estado_trash' => '1', 'user_created' => $request->user()->id, 'user_updated' => $request->user()->id]
        );

        $tipo = $this->tipoPersonaHotel($request);
        PersonaTipoPersona::firstOrCreate(
            ['idpersona' => $persona->idpersona, 'idpersona_tipo' => $tipo->idpersona_tipo],
            ['user_created' => $request->user()->id, 'user_updated' => $request->user()->id]
        );

        return ApiResponse::success($persona, 'Persona vinculada como hotel correctamente.');
    }

    public function personas(Request $request)
    {
        $search = trim((string) $request->input('term', ''));
        $rows = Persona::with('distrito:idubigeo_distrito,nombre')
            ->where('estado_trash', '1')
            ->whereHas('tiposPersona.personaTipo', fn ($query) => $query->where('nombre', 'HOTEL')->where('estado_trash', '1'))
            ->where(function ($query) use ($search) {
                $query->where('descripcion', 'like', "%{$search}%")
                    ->orWhere('numero_documento', 'like', "%{$search}%");
            })
            ->limit(15)
            ->get();

        return response()->json([
            'results' => $rows->map(fn ($persona) => [
                'id' => $persona->idpersona,
                'text' => trim($persona->descripcion . ' (' . $persona->numero_documento . ')'),
                'persona' => $persona,
            ]),
        ]);
    }

    public function showTipo(HotelTipo $tipo)
    {
        return ApiResponse::success($tipo);
    }

    public function updateTipo(Request $request, HotelTipo $tipo)
    {
        $validado = $request->validate(['nombre' => 'required|string|max:45']);
        $tipo->update($validado + ['user_updated' => $request->user()->id]);

        return ApiResponse::success($tipo, 'Tipo actualizado correctamente.');
    }

    public function destroyTipo(Request $request, HotelTipo $tipo)
    {
        $tipo->update([
            'estado_trash' => '0',
            'user_trash' => $request->user()->id,
            'user_updated' => $request->user()->id,
        ]);

        return ApiResponse::success($tipo, 'Tipo eliminado correctamente.');
    }

    public function storeTipo(Request $request)
    {
        $validado = $request->validate(['nombre' => 'required|string|max:45']);

        return ApiResponse::success(HotelTipo::create($validado + [
            'estado_trash' => '1',
            'user_created' => $request->user()->id,
            'user_updated' => $request->user()->id,
        ]));
    }
}
