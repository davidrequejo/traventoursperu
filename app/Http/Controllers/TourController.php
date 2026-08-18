<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Tour;
use App\Models\TourTurno;
use App\Models\UbigeoDistrito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TourController extends Controller
{
    private const INFORMACION_IMPORTANTE = 'informaciÃ³n importante';

    public function index()
    {
        return view('tours');
    }

    public function listar(Request $request)
    {
        $query = Tour::query()
            ->with(['turno:idtours_turno,descripcion', 'distrito:idubigeo_distrito,nombre'])
            ->latest('idtours');

        if (! $request->boolean('incluir_trash')) {
            $query->where('estado_trash', '1');
        }

        if ($request->filled('idtours')) {
            $query->where('idtours', $request->integer('idtours'));
        }

        if ($request->filled('buscar')) {
            $buscar = trim((string) $request->input('buscar'));

            $query->where(function ($subQuery) use ($buscar) {
                $subQuery
                    ->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%")
                    ->orWhere('descripcion_inicial', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%")
                    ->orWhere('descripcion_momento_destacados', 'like', "%{$buscar}%")
                    ->orWhere('descripcion_incluye_noincluye', 'like', "%{$buscar}%")
                    ->orWhere('ubicacion_maps', 'like', "%{$buscar}%");
            });
        }

        if (! $request->has('draw')) {
            return ApiResponse::success($query->get());
        }

        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        $length = $length === -1 ? -1 : max(1, min($length, 200));
        $search = trim((string) $request->input('search.value', ''));

        $recordsTotal = (clone $query)->count('idtours');

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('codigo', 'like', "%{$search}%")
                    ->orWhere('nombre', 'like', "%{$search}%")
                    ->orWhere('descripcion_inicial', 'like', "%{$search}%")
                    ->orWhere('descripcion', 'like', "%{$search}%")
                    ->orWhere('descripcion_momento_destacados', 'like', "%{$search}%")
                    ->orWhere('descripcion_incluye_noincluye', 'like', "%{$search}%")
                    ->orWhere('ubicacion_maps', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $query)->count('idtours');
        $this->aplicarOrdenTours($query, $request);

        $rows = $length === -1
            ? $query->get()
            : $query->skip($start)->take($length)->get();

        return response()->json([
            'status' => true,
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    private function aplicarOrdenTours($query, Request $request): void
    {
        $column = (int) $request->input('order.0.column', 1);
        $direction = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        match ($column) {
            1 => $query->orderBy('codigo', $direction),
            2 => $query->orderBy('nombre', $direction),
            3 => $query->orderBy('idtours_turno', $direction),
            4 => $query->orderBy('idubigeo_distrito', $direction),
            5 => $query->orderBy('precio_publico', $direction),
            6 => $query->orderBy('precio_corporativo', $direction),
            7 => $query->orderBy('precio_tours', $direction),
            8 => $query->orderBy('precio_web', $direction),
            9 => $query->orderBy('estado_trash', $direction),
            default => $query->orderBy('idtours', 'desc'),
        };

        if ($column !== 0) {
            $query->orderBy('idtours', 'desc');
        }
    }

    public function show(Tour $tour)
    {
        return ApiResponse::success($tour->load(['turno:idtours_turno,descripcion', 'distrito:idubigeo_distrito,nombre']));
    }

    public function store(Request $request)
    {
        $validated = $this->validarTour($request);

        return DB::transaction(function () use ($request, $validated) {
            $validated['estado_trash'] = '1';
            $validated['user_trash'] = null;
            $validated['user_created'] = $request->user()->id;
            $validated['user_updated'] = $request->user()->id;

            unset($validated['codigo']);
            $tour = Tour::create($validated);
            $tour->update([
                'codigo' => sprintf('TOUR-%05d', $tour->idtours),
            ]);

            return ApiResponse::success($tour->load(['turno:idtours_turno,descripcion', 'distrito:idubigeo_distrito,nombre']), 'Tour registrado correctamente.');
        });
    }

    public function update(Request $request, Tour $tour)
    {
        $validated = $this->validarTour($request, $tour->idtours);

        return DB::transaction(function () use ($request, $tour, $validated) {
            $validated['user_updated'] = $request->user()->id;
            $tour->update($validated);

            return ApiResponse::success($tour->fresh()->load(['turno:idtours_turno,descripcion', 'distrito:idubigeo_distrito,nombre']), 'Tour actualizado correctamente.');
        });
    }

    public function destroy(Request $request, Tour $tour)
    {
        if ((string) $tour->estado_trash === '0') {
            return ApiResponse::fail('El tour ya se encuentra eliminado.', 400);
        }

        $tour->update([
            'estado_trash' => '0',
            'user_trash' => $request->user()->id,
            'user_updated' => $request->user()->id,
        ]);

        return ApiResponse::success($tour, 'Tour eliminado correctamente.');
    }

    public function restore(Request $request, int $tour)
    {
        $tour = Tour::where('idtours', $tour)
            ->where('estado_trash', '0')
            ->firstOrFail();

        $tour->update([
            'estado_trash' => '1',
            'user_trash' => null,
            'user_updated' => $request->user()->id,
        ]);

        return ApiResponse::success($tour, 'Tour restaurado correctamente.');
    }

    public function catalogos()
    {
        $turnos = TourTurno::query()
            ->where('estado_trash', '1')
            ->orderBy('descripcion')
            ->get(['idtours_turno', 'descripcion']);

        return ApiResponse::success(['turnos' => $turnos]);
    }

    public function storeTurno(Request $request)
    {
        $validated = $request->validate([
            'descripcion' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tours_turno', 'descripcion')->where('estado_trash', '1'),
            ],
        ]);

        $turno = TourTurno::create([
            'descripcion' => trim($validated['descripcion']),
            'estado_trash' => '1',
            'user_trash' => null,
            'user_created' => $request->user()->id,
            'user_updated' => $request->user()->id,
        ]);

        return ApiResponse::success($turno, 'Turno registrado correctamente.');
    }

    public function distritos(Request $request)
    {
        $search = trim((string) $request->input('term', ''));
        $page = max((int) $request->input('page', 1), 1);
        $perPage = 15;

        $query = UbigeoDistrito::query()
            ->with('provincia.departamento')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('nombre', 'like', "%{$search}%")
                        ->orWhere('idubigeo_distrito', 'like', "%{$search}%")
                        ->orWhereHas('provincia', fn ($provincia) => $provincia->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('provincia.departamento', fn ($departamento) => $departamento->where('nombre', 'like', "%{$search}%"));
                });
            })
            ->orderBy('nombre');

        $distritos = (clone $query)
            ->offset(($page - 1) * $perPage)
            ->limit($perPage + 1)
            ->get();

        return response()->json([
            'results' => $distritos->take($perPage)->map(function (UbigeoDistrito $distrito) {
                $provincia = $distrito->provincia;
                $departamento = $provincia?->departamento;
                $detalle = collect([$provincia?->nombre, $departamento?->nombre])->filter()->implode(' - ');

                return [
                    'id' => $distrito->idubigeo_distrito,
                    'text' => trim($distrito->nombre . ($detalle ? " ({$detalle})" : '')),
                ];
            }),
            'pagination' => ['more' => $distritos->count() > $perPage],
        ]);
    }

    private function validarTour(Request $request, ?int $idTour = null): array
    {
        $request->merge([
            self::INFORMACION_IMPORTANTE => $request->input('informacion_importante'),
        ]);

        $validated = $request->validate([
            'idtours_turno' => ['required', 'integer', Rule::exists('tours_turno', 'idtours_turno')->where('estado_trash', '1')],
            'idubigeo_distrito' => ['required', 'integer', Rule::exists('ubigeo_distrito', 'idubigeo_distrito')],
            'codigo' => ['nullable', 'string', 'max:10'],
            'nombre' => ['required', 'string', 'max:250'],
            'precio_publico' => ['nullable', 'numeric', 'min:0'],
            'precio_corporativo' => ['nullable', 'numeric', 'min:0'],
            'precio_tours' => ['nullable', 'numeric', 'min:0'],
            'precio_web' => ['nullable', 'numeric', 'min:0'],
            'descripcion_inicial' => ['nullable', 'string', 'max:1000'],
            'duracion' => ['nullable', 'string', 'max:225'],
            'hora_recojo' => ['nullable', 'date_format:H:i'],
            'hora_retorno' => ['nullable', 'date_format:H:i'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'descripcion_momento_destacados' => ['nullable', 'string', 'max:2000'],
            'informacion_importante' => ['nullable', 'string', 'max:2000'],
            'descripcion_incluye_noincluye' => ['nullable', 'string', 'max:2000'],
            'ubicacion_maps' => ['nullable', 'string', 'max:2000'],
            'brochure' => ['nullable', 'string', 'max:255'],
        ]);

        // El código se asigna en el servidor y no puede alterarse desde el formulario.
        unset($validated['codigo']);

        if (array_key_exists('informacion_importante', $validated)) {
            $validated[self::INFORMACION_IMPORTANTE] = $validated['informacion_importante'];
            unset($validated['informacion_importante']);
        }

        return $validated;
    }
}
