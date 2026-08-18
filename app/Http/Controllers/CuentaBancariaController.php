<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Banco;
use App\Models\CuentaBancaria;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CuentaBancariaController extends Controller
{
    public function index()
    {
        return view('cuenta_bancaria');
    }

    public function listar(Request $request)
    {
        $query = CuentaBancaria::query()
            ->with(['banco', 'persona.docIdentidad'])
            ->latest('idcuenta_bancaria');

        if (! $request->boolean('incluir_trash')) {
            $query->where('estado_trash', '1');
        }

        if ($request->filled('idcuenta_bancaria')) {
            $query->where('idcuenta_bancaria', $request->integer('idcuenta_bancaria'));
        }

        if ($request->filled('idbanco')) {
            $query->where('idbanco', $request->integer('idbanco'));
        }

        if ($request->filled('idpersona')) {
            $query->where('idpersona', $request->integer('idpersona'));
        }

        if ($request->filled('moneda')) {
            $query->where('moneda', $request->string('moneda'));
        }

        if ($request->filled('tipo_cuenta')) {
            $query->where('tipo_cuenta', $request->string('tipo_cuenta'));
        }

        if ($request->filled('buscar')) {
            $buscar = trim((string) $request->input('buscar'));

            $query->where(function ($subQuery) use ($buscar) {
                $subQuery
                    ->where('cci', 'like', "%{$buscar}%")
                    ->orWhere('cta_cte', 'like', "%{$buscar}%")
                    ->orWhereHas('banco', function ($bancoQuery) use ($buscar) {
                        $bancoQuery
                            ->where('nombre', 'like', "%{$buscar}%")
                            ->orWhere('alias', 'like', "%{$buscar}%");
                    })
                    ->orWhereHas('persona', function ($personaQuery) use ($buscar) {
                        $personaQuery
                            ->where('descripcion', 'like', "%{$buscar}%")
                            ->orWhere('nombre_comercial', 'like', "%{$buscar}%")
                            ->orWhere('nombre_persona_natural', 'like', "%{$buscar}%")
                            ->orWhere('numero_documento', 'like', "%{$buscar}%");
                    });
            });
        }

        return ApiResponse::success($query->get());
    }

    public function show(CuentaBancaria $cuentaBancaria)
    {
        return ApiResponse::success($cuentaBancaria->load(['banco', 'persona.docIdentidad']));
    }

    public function store(Request $request)
    {
        $validated = $this->validarCuentaBancaria($request);

        return DB::transaction(function () use ($request, $validated) {
            $validated['estado_trash'] = '1';
            $validated['user_trash'] = null;
            $validated['user_created'] = $request->user()->id;
            $validated['user_updated'] = $request->user()->id;

            $cuentaBancaria = CuentaBancaria::create($validated);

            return ApiResponse::success(
                $cuentaBancaria->load(['banco', 'persona.docIdentidad']),
                'Cuenta bancaria registrada correctamente.'
            );
        });
    }

    public function update(Request $request, CuentaBancaria $cuentaBancaria)
    {
        $validated = $this->validarCuentaBancaria($request, $cuentaBancaria->idcuenta_bancaria);

        return DB::transaction(function () use ($request, $cuentaBancaria, $validated) {
            $validated['user_updated'] = $request->user()->id;
            $cuentaBancaria->update($validated);

            return ApiResponse::success(
                $cuentaBancaria->load(['banco', 'persona.docIdentidad']),
                'Cuenta bancaria actualizada correctamente.'
            );
        });
    }

    public function destroy(Request $request, CuentaBancaria $cuentaBancaria)
    {
        if ((string) $cuentaBancaria->estado_trash === '0') {
            return ApiResponse::fail('La cuenta bancaria ya se encuentra eliminada.', 400);
        }

        $cuentaBancaria->update([
            'estado_trash' => '0',
            'user_trash' => $request->user()->id,
            'user_updated' => $request->user()->id,
        ]);

        return ApiResponse::success($cuentaBancaria, 'Cuenta bancaria eliminada correctamente.');
    }

    public function restore(Request $request, int $cuentaBancaria)
    {
        $cuentaBancaria = CuentaBancaria::where('idcuenta_bancaria', $cuentaBancaria)
            ->where('estado_trash', '0')
            ->firstOrFail();

        $cuentaBancaria->update([
            'estado_trash' => '1',
            'user_trash' => null,
            'user_updated' => $request->user()->id,
        ]);

        return ApiResponse::success($cuentaBancaria, 'Cuenta bancaria restaurada correctamente.');
    }

    public function getselect2banco()
    {
        try {
            $bancos = Banco::query()
                ->where('estado_trash', '1')
                ->orderBy('nombre')
                ->get(['idbanco', 'nombre', 'alias', 'icono']);

            $options = '<option value=""></option>';

            foreach ($bancos as $banco) {
                $nombre = trim($banco->nombre . (filled($banco->alias) ? " ({$banco->alias})" : ''));
                $options .= '<option value="' . e($banco->idbanco) . '" data-icono="' . e($banco->icono ?? '') . '">' . e($nombre) . '</option>';
            }

            return ApiResponse::success($options, 'Lista de bancos obtenida');
        } catch (\Throwable $e) {
            return ApiResponse::error($e);
        }
    }

    public function getselect2persona()
    {
        try {
            $personas = Persona::query()
                ->where('estado_trash', '1')
                ->orderBy('descripcion')
                ->orderBy('nombre_comercial')
                ->orderBy('nombre_persona_natural')
                ->get([
                    'idpersona',
                    'descripcion',
                    'nombre_comercial',
                    'nombre_persona_natural',
                    'apellido_paterno_persona_natural',
                    'apellido_materno_persona_natural',
                    'numero_documento',
                ]);

            $options = '<option value=""></option>';

            foreach ($personas as $persona) {
                $nombre = collect([
                    $persona->descripcion,
                    $persona->nombre_comercial,
                    trim(collect([
                        $persona->nombre_persona_natural,
                        $persona->apellido_paterno_persona_natural,
                        $persona->apellido_materno_persona_natural,
                    ])->filter()->implode(' ')),
                    $persona->numero_documento ? "Doc: {$persona->numero_documento}" : null,
                ])->first(fn ($valor) => filled($valor)) ?: 'Sin nombre';

                $detalle = $persona->numero_documento ? " - {$persona->numero_documento}" : '';
                $options .= '<option value="' . e($persona->idpersona) . '">' . e($nombre . $detalle) . '</option>';
            }

            return ApiResponse::success($options, 'Lista de personas obtenida');
        } catch (\Throwable $e) {
            return ApiResponse::error($e);
        }
    }

    private function validarCuentaBancaria(Request $request, ?int $idCuentaBancaria = null): array
    {
        return $request->validate([
            'idbanco' => [
                'required',
                'integer',
                Rule::exists('banco', 'idbanco')->where('estado_trash', '1'),
            ],
            'idpersona' => [
                'required',
                'integer',
                Rule::exists('persona', 'idpersona')->where('estado_trash', '1'),
            ],
            'cci' => ['nullable', 'string', 'max:45'],
            'cta_cte' => ['nullable', 'string', 'max:45'],
            'moneda' => ['nullable', 'string', Rule::in(['USD', 'PEN'])],
            'tipo_cuenta' => ['nullable', 'string', Rule::in(['AHORRO', 'CORRIENTE'])],
        ]);
    }
}
