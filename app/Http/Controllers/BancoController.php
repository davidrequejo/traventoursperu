<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Banco;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BancoController extends Controller
{
    private function getIconoDirectory(): string
    {
        return 'assets/modulo/banco/icono';
    }

    public function index()
    {
       // return view('banco');
    }

    public function listar(Request $request)
    {
        $query = Banco::query()
            ->latest('idbanco');

        if (! $request->boolean('incluir_trash')) {
            $query->where('estado_trash', '1');
        }

        if ($request->filled('idbanco')) {
            $query->where('idbanco', $request->integer('idbanco'));
        }

        if ($request->filled('buscar')) {
            $buscar = trim((string) $request->input('buscar'));

            $query->where(function ($subQuery) use ($buscar) {
                $subQuery
                    ->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('alias', 'like', "%{$buscar}%")
                    ->orWhere('formato_cta', 'like', "%{$buscar}%")
                    ->orWhere('formato_cci', 'like', "%{$buscar}%")
                    ->orWhere('formato_detracciones', 'like', "%{$buscar}%");
            });
        }

        return ApiResponse::success($query->get());
    }

    public function show(Banco $banco)
    {
        return ApiResponse::success($banco);
    }

    public function store(Request $request)
    {
        $validated = $this->validarBanco($request);

        return DB::transaction(function () use ($request, $validated) {
            $icono = $request->file('icono_file') ?: $request->file('imagen');

            if ($icono) {
                $validated['icono'] = $this->guardarIcono($icono);
            }

            $validated['estado_trash'] = '1';
            $validated['user_trash'] = null;
            $validated['user_created'] = $request->user()->id;
            $validated['user_updated'] = $request->user()->id;

            $banco = Banco::create($validated);

            return ApiResponse::success($banco, 'Banco registrado correctamente.');
        });
    }

    public function update(Request $request, Banco $banco)
    {
        $validated = $this->validarBanco($request, $banco->idbanco);

        return DB::transaction(function () use ($request, $banco, $validated) {
            $icono = $request->file('icono_file') ?: $request->file('imagen');

            if ($icono) {
                $this->eliminarIcono($banco->icono);
                $validated['icono'] = $this->guardarIcono($icono);
            } elseif (
                ($request->has('icono_actual') && blank($request->input('icono_actual')))
                || ($request->has('imagenactual') && blank($request->input('imagenactual')))
            ) {
                $this->eliminarIcono($banco->icono);
                $validated['icono'] = null;
            }

            $validated['user_updated'] = $request->user()->id;
            $banco->update($validated);

            return ApiResponse::success($banco, 'Banco actualizado correctamente.');
        });
    }

    public function destroy(Request $request, Banco $banco)
    {
        if ((string) $banco->estado_trash === '0') {
            return ApiResponse::fail('El banco ya se encuentra eliminado.', 400);
        }

        $banco->update([
            'estado_trash' => '0',
            'user_trash' => $request->user()->id,
            'user_updated' => $request->user()->id,
        ]);

        return ApiResponse::success($banco, 'Banco eliminado correctamente.');
    }

    public function restore(Request $request, int $banco)
    {
        $banco = Banco::where('idbanco', $banco)
            ->where('estado_trash', '0')
            ->firstOrFail();

        $banco->update([
            'estado_trash' => '1',
            'user_trash' => null,
            'user_updated' => $request->user()->id,
        ]);

        return ApiResponse::success($banco, 'Banco restaurado correctamente.');
    }

    private function validarBanco(Request $request, ?int $idBanco = null): array
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:65'],
            'alias' => ['nullable', 'string', 'max:65'],
            'formato_cta' => ['nullable', 'string', 'max:50'],
            'formato_cci' => ['nullable', 'string', 'max:50'],
            'formato_detracciones' => ['nullable', 'string', 'max:50'],
            'icono' => ['nullable', 'string', 'max:100'],
            'icono_file' => ['nullable', 'image', 'max:2048'],
            'icono_actual' => ['nullable', 'string'],
            'imagen' => ['nullable', 'image', 'max:2048'],
            'imagenactual' => ['nullable', 'string'],
        ]);

        unset($validated['icono_file'], $validated['icono_actual'], $validated['imagen'], $validated['imagenactual']);

        return $validated;
    }

    private function guardarIcono($icono): string
    {
        $extension = $icono->getClientOriginalExtension();
        $nombreArchivo = 'banco_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
        $directorio = public_path($this->getIconoDirectory());

        if (! file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $icono->move($directorio, $nombreArchivo);

        return $nombreArchivo;
    }

    private function eliminarIcono(?string $nombreArchivo): void
    {
        if (blank($nombreArchivo)) {
            return;
        }

        $ruta = public_path($this->getIconoDirectory() . '/' . $nombreArchivo);

        if (file_exists($ruta)) {
            unlink($ruta);
        }
    }
}
