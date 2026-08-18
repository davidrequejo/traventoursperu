<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\SerieComprobante;
use App\Models\SunatC01TipoComprobante;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SerieComprobanteController extends Controller
{
    public function index()
    {
        return view('serie_comprobante');
    }

    public function listar(Request $request)
    {
        $query = SerieComprobante::query()
            ->from('serie_comprobante as sc')
            ->leftJoin('sunat_c01_tipo_comprobante as tc', 'tc.idsunat_c01_tipo_comprobante', '=', 'sc.idsunat_c01_tipo_comprobante')
            ->leftJoin('sunat_c01_tipo_comprobante as tca', 'tca.idsunat_c01_tipo_comprobante', '=', 'sc.tipo_comprobante_adicional')
            ->select('sc.*')
            ->with([
                'tipoComprobante:idsunat_c01_tipo_comprobante,codigo,nombre,abreviatura',
                'comprobanteAdicional:idsunat_c01_tipo_comprobante,codigo,nombre,abreviatura',
            ]);

        if (! $request->boolean('incluir_trash')) {
            $query->where('sc.estado_trash', '1');
        }

        // Fallback para consumo no-DataTable.
        if (! $request->has('draw')) {
            return ApiResponse::success(
                $query
                    ->orderByDesc('sc.idserie_comprobante')
                    ->get()
            );
        }

        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        $length = $length <= 0 ? 10 : min($length, 200);
        $search = trim((string) $request->input('search.value', ''));

        $recordsTotal = (clone $query)->count('sc.idserie_comprobante');

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('sc.idserie_comprobante', 'like', "%{$search}%")
                    ->orWhere('sc.serie', 'like', "%{$search}%")
                    ->orWhere('sc.numero', 'like', "%{$search}%")
                    ->orWhere('sc.predeterminado', 'like', "%{$search}%")
                    ->orWhere('sc.estado_trash', 'like', "%{$search}%")
                    ->orWhere('tc.codigo', 'like', "%{$search}%")
                    ->orWhere('tc.nombre', 'like', "%{$search}%")
                    ->orWhere('tc.abreviatura', 'like', "%{$search}%")
                    ->orWhere('tca.codigo', 'like', "%{$search}%")
                    ->orWhere('tca.nombre', 'like', "%{$search}%")
                    ->orWhere('tca.abreviatura', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $query)->count('sc.idserie_comprobante');

        $this->aplicarOrdenDataTable($query, $request);

        $rows = $query
            ->skip($start)
            ->take($length)
            ->get();

        return response()->json([
            'status' => true,
            'code_error' => 0,
            'message' => 'OK',
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    public function tiposComprobantes()
    {
        return ApiResponse::success(
            SunatC01TipoComprobante::query()
                ->where('estado_trash', '1')
                ->orderBy('codigo')
                ->get(['idsunat_c01_tipo_comprobante', 'codigo', 'nombre', 'abreviatura'])
        );
    }

    public function validarSerie(Request $request)
    {
        $request->merge([
            'serie' => trim((string) $request->input('serie')),
        ]);

        $validator = Validator::make($request->all(), [
            'serie' => ['required', 'string', 'max:10'],
            'idserie_comprobante' => ['nullable', 'integer'],
        ], [
            'serie.required' => 'Campo requerido.',
            'serie.max' => 'MAXIMO 10 caracteres.',
        ]);

        if ($validator->fails()) {
            $errores = $validator->errors()->toArray();
            $primerMensaje = $validator->errors()->first('serie') ?: 'Campo requerido.';

            // Endpoint usado por jQuery remote: debe responder string/true.
            return response()->json($errores['serie'][0] ?? $primerMensaje);
        }

        $existe = SerieComprobante::query()
            ->where('serie', $request->input('serie'))
            ->when(
                $request->filled('idserie_comprobante'),
                fn ($query) => $query->where('idserie_comprobante', '<>', $request->input('idserie_comprobante'))
            )
            ->exists();

        return response()->json($existe ? 'La serie ya esta registrada.' : true);
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);

        $serie = DB::transaction(function () use ($request, $validated) {
            $serie = SerieComprobante::create([
                ...$validated,
                'predeterminado' => $request->boolean('predeterminado') ? '1' : '0',
                'estado_trash' => '1',
                'user_trash' => null,
                'user_created' => Auth::id(),
                'user_updated' => Auth::id(),
            ]);

            $this->sincronizarPredeterminado($serie);

            return $serie;
        });

        return ApiResponse::success($serie->load(['tipoComprobante', 'comprobanteAdicional']), 'Serie registrada correctamente.');
    }

    public function show(SerieComprobante $serieComprobante)
    {
        return ApiResponse::success($serieComprobante->load(['tipoComprobante', 'comprobanteAdicional']));
    }

    public function update(Request $request, SerieComprobante $serieComprobante)
    {
        $validated = $this->validar($request, $serieComprobante->idserie_comprobante);

        DB::transaction(function () use ($request, $serieComprobante, $validated) {
            $serieComprobante->update([
                ...$validated,
                'predeterminado' => $request->boolean('predeterminado') ? '1' : '0',
                'user_updated' => Auth::id(),
            ]);

            $this->sincronizarPredeterminado($serieComprobante);
        });

        return ApiResponse::success($serieComprobante->load(['tipoComprobante', 'comprobanteAdicional']), 'Serie actualizada correctamente.');
    }

    public function destroy(Request $request, SerieComprobante $serieComprobante)
    {
        if ((string) $serieComprobante->estado_trash === '0') {
            return ApiResponse::fail('La serie ya se encuentra desactivada.', 400);
        }

        $serieComprobante->update([
            'estado_trash' => '0',
            'user_trash' => Auth::id(),
            'user_updated' => Auth::id(),
        ]);

        return ApiResponse::success($serieComprobante, 'Serie desactivada correctamente.');
    }

    public function restore(Request $request, SerieComprobante $serieComprobante)
    {
        if ((string) $serieComprobante->estado_trash === '1') {
            return ApiResponse::fail('La serie ya se encuentra activa.', 400);
        }

        $serieComprobante->update([
            'estado_trash' => '1',
            'user_trash' => null,
            'user_updated' => Auth::id(),
        ]);

        return ApiResponse::success($serieComprobante, 'Serie restaurada correctamente.');
    }

    private function validar(Request $request, ?int $id = null): array
    {
        $request->merge([
            'serie' => trim((string) $request->input('serie')),
        ]);

        $validator = Validator::make($request->all(), [
            'idsunat_c01_tipo_comprobante' => [
                'required',
                'integer',
                Rule::exists('sunat_c01_tipo_comprobante', 'idsunat_c01_tipo_comprobante')->where('estado_trash', '1'),
            ],
            'serie' => [
                'required',
                'string',
                'max:10',
                Rule::unique('serie_comprobante', 'serie')->ignore($id, 'idserie_comprobante'),
            ],
            'numero' => ['nullable', 'integer', 'min:0'],
            'tipo_comprobante_adicional' => [
                'nullable',
                'integer',
                Rule::exists('sunat_c01_tipo_comprobante', 'idsunat_c01_tipo_comprobante')->where('estado_trash', '1'),
            ],
            'predeterminado' => ['nullable', 'string', 'max:1', 'in:0,1,on'],
        ], [
            'idsunat_c01_tipo_comprobante.required' => 'Campo requerido.',
            'serie.required' => 'Campo requerido.',
            'serie.max' => 'MAXIMO 10 caracteres.',
            'serie.unique' => 'La serie ya esta registrada.',
        ]);

        if ($validator->fails()) {
            throw new HttpResponseException(
                ApiResponse::validation(
                    $validator->errors()->toArray(),
                    $validator->errors()->first() ?: 'Revise los campos del formulario.'
                )
            );
        }

        return $validator->validated();
    }

    private function sincronizarPredeterminado(SerieComprobante $serie): void
    {
        if ((string) $serie->predeterminado !== '1') {
            return;
        }

        SerieComprobante::query()
            ->where('idsunat_c01_tipo_comprobante', $serie->idsunat_c01_tipo_comprobante)
            ->when(
                $serie->tipo_comprobante_adicional,
                fn ($query) => $query->where('tipo_comprobante_adicional', $serie->tipo_comprobante_adicional),
                fn ($query) => $query->whereNull('tipo_comprobante_adicional')
            )
            ->where('idserie_comprobante', '<>', $serie->idserie_comprobante)
            ->update(['predeterminado' => '0']);
    }

    private function aplicarOrdenDataTable($query, Request $request): void
    {
        $columnIndex = (int) $request->input('order.0.column', 1);
        $direction = strtolower((string) $request->input('order.0.dir', 'desc'));
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';

        $columnMap = [
            1 => 'sc.idserie_comprobante',
            3 => 'sc.serie',
            4 => 'sc.numero',
            6 => 'sc.predeterminado',
            7 => 'sc.estado_trash',
        ];

        if ($columnIndex === 2) {
            $query->orderBy('tc.codigo', $direction)
                ->orderBy('tc.abreviatura', $direction);
            return;
        }

        if ($columnIndex === 5) {
            $query->orderBy('tca.codigo', $direction)
                ->orderBy('tca.abreviatura', $direction);
            return;
        }

        $column = $columnMap[$columnIndex] ?? 'sc.idserie_comprobante';
        $query->orderBy($column, $direction);
    }
}
