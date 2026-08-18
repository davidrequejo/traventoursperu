<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\LlegadaPorEmpresa;
use App\Models\LlegadaTipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LlegadaPorEmpresaController extends Controller
{
    public function aerolineas()
    {
        return $this->index('aerolineas');
    }

    public function agencias()
    {
        return $this->index('agencias');
    }

    public function listarAerolineas(Request $request)
    {
        return $this->listar($request, 'aerolineas');
    }

    public function listarAgencias(Request $request)
    {
        return $this->listar($request, 'agencias');
    }

    public function storeAerolinea(Request $request)
    {
        return $this->store($request, 'aerolineas');
    }

    public function storeAgencia(Request $request)
    {
        return $this->store($request, 'agencias');
    }

    public function showAerolinea(LlegadaPorEmpresa $empresa)
    {
        return $this->show($empresa, 'aerolineas');
    }

    public function showAgencia(LlegadaPorEmpresa $empresa)
    {
        return $this->show($empresa, 'agencias');
    }

    public function updateAerolinea(Request $request, LlegadaPorEmpresa $empresa)
    {
        return $this->update($request, $empresa, 'aerolineas');
    }

    public function updateAgencia(Request $request, LlegadaPorEmpresa $empresa)
    {
        return $this->update($request, $empresa, 'agencias');
    }

    public function destroyAerolinea(Request $request, LlegadaPorEmpresa $empresa)
    {
        return $this->destroy($request, $empresa, 'aerolineas');
    }

    public function destroyAgencia(Request $request, LlegadaPorEmpresa $empresa)
    {
        return $this->destroy($request, $empresa, 'agencias');
    }

    public function restoreAerolinea(Request $request, int $empresa)
    {
        return $this->restore($request, $empresa, 'aerolineas');
    }

    public function restoreAgencia(Request $request, int $empresa)
    {
        return $this->restore($request, $empresa, 'agencias');
    }

    private function index(string $modulo)
    {
        $config = $this->configModulo($modulo);

        return view('llegadas_empresa', $config + ['modulo' => $modulo]);
    }

    private function listar(Request $request, string $modulo)
    {
        $tipo = $this->tipoModulo($modulo);
        $query = LlegadaPorEmpresa::with('tipo')
            ->where('idllegada_tipo', $tipo->idllegada_tipo)
            ->latest('idllegada_por_empresa');

        if (! $request->boolean('incluir_trash')) {
            $query->where('estado_trash', '1');
        }

        return ApiResponse::success($query->get());
    }

    private function store(Request $request, string $modulo)
    {
        $tipo = $this->tipoModulo($modulo);
        $validated = $this->validarEmpresa($request, $tipo->idllegada_tipo);

        return DB::transaction(function () use ($request, $tipo, $validated) {
            $empresa = LlegadaPorEmpresa::create($validated + [
                'idllegada_tipo' => $tipo->idllegada_tipo,
                'estado_trash' => '1',
                'user_trash' => null,
                'user_created' => $request->user()->id,
                'user_updated' => $request->user()->id,
            ]);

            return ApiResponse::success($empresa->load('tipo'), 'Registro creado correctamente.');
        });
    }

    private function show(LlegadaPorEmpresa $empresa, string $modulo)
    {
        $this->asegurarTipoModulo($empresa, $modulo);

        return ApiResponse::success($empresa->load('tipo'));
    }

    private function update(Request $request, LlegadaPorEmpresa $empresa, string $modulo)
    {
        $tipo = $this->asegurarTipoModulo($empresa, $modulo);
        $validated = $this->validarEmpresa($request, $tipo->idllegada_tipo, $empresa->idllegada_por_empresa);

        return DB::transaction(function () use ($request, $empresa, $validated) {
            $empresa->update($validated + ['user_updated' => $request->user()->id]);

            return ApiResponse::success($empresa->fresh()->load('tipo'), 'Registro actualizado correctamente.');
        });
    }

    private function destroy(Request $request, LlegadaPorEmpresa $empresa, string $modulo)
    {
        $this->asegurarTipoModulo($empresa, $modulo);

        if ((string) $empresa->estado_trash === '0') {
            return ApiResponse::fail('El registro ya se encuentra eliminado.', 400);
        }

        $empresa->update([
            'estado_trash' => '0',
            'user_trash' => $request->user()->id,
            'user_updated' => $request->user()->id,
        ]);

        return ApiResponse::success($empresa, 'Registro enviado a papelera.');
    }

    private function restore(Request $request, int $idEmpresa, string $modulo)
    {
        $tipo = $this->tipoModulo($modulo);
        $empresa = LlegadaPorEmpresa::where('idllegada_por_empresa', $idEmpresa)
            ->where('idllegada_tipo', $tipo->idllegada_tipo)
            ->where('estado_trash', '0')
            ->firstOrFail();

        $empresa->update([
            'estado_trash' => '1',
            'user_trash' => null,
            'user_updated' => $request->user()->id,
        ]);

        return ApiResponse::success($empresa, 'Registro restaurado correctamente.');
    }

    private function validarEmpresa(Request $request, int $idTipo, ?int $idEmpresa = null): array
    {
        return $request->validate([
            'descripcion' => [
                'required',
                'string',
                'max:225',
                Rule::unique('llegada_por_empresa', 'descripcion')
                    ->where(fn ($query) => $query->where('idllegada_tipo', $idTipo))
                    ->ignore($idEmpresa, 'idllegada_por_empresa'),
            ],
        ]);
    }

    private function asegurarTipoModulo(LlegadaPorEmpresa $empresa, string $modulo): LlegadaTipo
    {
        $tipo = $this->tipoModulo($modulo);

        abort_unless((int) $empresa->idllegada_tipo === (int) $tipo->idllegada_tipo, 404);

        return $tipo;
    }

    private function tipoModulo(string $modulo): LlegadaTipo
    {
        return LlegadaTipo::where('descripcion', $this->configModulo($modulo)['tipo_descripcion'])
            ->where('estado_trash', '1')
            ->firstOrFail();
    }

    private function configModulo(string $modulo): array
    {
        return match ($modulo) {
            'aerolineas' => [
                'titulo' => 'Aerolíneas',
                'singular' => 'Aerolínea',
                'tipo_descripcion' => 'AEROPUERTO',
                'icono' => 'ri-flight-takeoff-line',
                'ruta_base' => '/aerolineas',
            ],
            'agencias' => [
                'titulo' => 'Agencias',
                'singular' => 'Agencia',
                'tipo_descripcion' => 'TERMINAL RERRESTRE',
                'icono' => 'ri-building-2-line',
                'ruta_base' => '/agencias',
            ],
            default => abort(404),
        };
    }
}