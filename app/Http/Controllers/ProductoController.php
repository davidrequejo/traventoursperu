<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\SunatC03UnidadMedida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    private const PRODUCTO_RESERVADO_ID = 1;

    private function getImagenDirectory(): string
    {
        return 'assets/modulo/producto/imagen';
    }

    public function index()
    {
        return view('producto');
    }

    public function listar(Request $request)
    {
        $query = Producto::query()
            ->select('producto.*')
            ->leftJoin('categoria', 'categoria.idcategoria', '=', 'producto.idcategoria')
            ->leftJoin('marca', 'marca.idmarca', '=', 'producto.idmarca')
            ->leftJoin('sunat_c03_unidad_medida', 'sunat_c03_unidad_medida.idsunat_c03_unidad_medida', '=', 'producto.idsunat_c03')
            ->with(['categoria:idcategoria,nombre', 'marca:idmarca,nombre', 'unidadMedida:idsunat_c03_unidad_medida,nombre,abreviatura'])
            ->latest('producto.idproducto');

        if (! $request->boolean('incluir_trash')) {
            $query->where('producto.estado_trash', '1');
        }

        if ($request->filled('idproducto')) {
            $query->where('producto.idproducto', $request->integer('idproducto'));
        }

        if ($request->filled('idcategoria')) {
            $query->where('producto.idcategoria', $request->integer('idcategoria'));
        }

        if ($request->filled('idmarca')) {
            $query->where('producto.idmarca', $request->integer('idmarca'));
        }

        if ($request->filled('tipo')) {
            $query->where('producto.tipo', strtoupper((string) $request->input('tipo')));
        }

        if (! $request->has('draw')) {
            if ($request->filled('buscar')) {
                $this->aplicarBusquedaProductos($query, trim((string) $request->input('buscar')));
            }

            return ApiResponse::success($query->get());
        }

        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        $length = $length === -1 ? -1 : max(1, min($length, 200));
        $search = trim((string) $request->input('search.value', ''));

        $recordsTotal = (clone $query)->count('producto.idproducto');

        if ($search !== '') {
            $this->aplicarBusquedaProductos($query, $search);
        }

        $recordsFiltered = (clone $query)->count('producto.idproducto');
        $this->aplicarOrdenProductos($query, $request);

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

    private function aplicarBusquedaProductos($query, string $search): void
    {
        $query->where(function ($subQuery) use ($search) {
            $subQuery
                ->where('producto.codigo', 'like', "%{$search}%")
                ->orWhere('producto.codigo_alterno', 'like', "%{$search}%")
                ->orWhere('producto.nombre', 'like', "%{$search}%")
                ->orWhere('producto.descripcion_adicional', 'like', "%{$search}%")
                ->orWhere('producto.tipo', 'like', "%{$search}%")
                ->orWhere('categoria.nombre', 'like', "%{$search}%")
                ->orWhere('marca.nombre', 'like', "%{$search}%")
                ->orWhere('sunat_c03_unidad_medida.nombre', 'like', "%{$search}%")
                ->orWhere('sunat_c03_unidad_medida.abreviatura', 'like', "%{$search}%");
        });
    }

    private function aplicarOrdenProductos($query, Request $request): void
    {
        $column = (int) $request->input('order.0.column', 1);
        $direction = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        match ($column) {
            1 => $query->orderBy('producto.idproducto', $direction),
            2 => $query->orderBy('producto.nombre', $direction),
            3 => $query->orderBy('producto.tipo', $direction),
            4 => $query->orderBy('marca.nombre', $direction),
            5 => $query->orderBy('categoria.nombre', $direction),
            6 => $query->orderBy('sunat_c03_unidad_medida.nombre', $direction),
            7 => $query->orderBy('producto.stock', $direction),
            8 => $query->orderBy('producto.precio_venta', $direction),
            9 => $query->orderBy('producto.estado_trash', $direction),
            default => $query->orderBy('producto.idproducto', 'desc'),
        };

        if ($column !== 0) {
            $query->orderBy('producto.idproducto', 'desc');
        }
    }

    public function show(Producto $producto)
    {
        return ApiResponse::success($producto->load(['categoria:idcategoria,nombre', 'marca:idmarca,nombre', 'unidadMedida:idsunat_c03_unidad_medida,nombre,abreviatura']));
    }

    public function store(Request $request)
    {
        $validated = $this->validarProducto($request);

        return DB::transaction(function () use ($request, $validated) {
            $imagen = $request->file('imagen_file') ?: $request->file('imagen');

            $validated['codigo'] = null;

            if ($imagen) {
                $validated['imagen'] = $this->guardarImagen($imagen);
            }

            $validated['estado_trash'] = '1';
            $validated['user_trash'] = null;
            $validated['user_created'] = $request->user()->id;
            $validated['user_updated'] = $request->user()->id;

            $producto = Producto::create($validated);

            $producto->update([
                'codigo' => $this->generarCodigoProducto($producto->idproducto),
            ]);

            return ApiResponse::success(
                $producto->fresh()->load(['categoria:idcategoria,nombre', 'marca:idmarca,nombre', 'unidadMedida:idsunat_c03_unidad_medida,nombre,abreviatura']),
                'Producto registrado correctamente.'
            );
        });
    }

    public function update(Request $request, Producto $producto)
    {
        $validated = $this->validarProducto($request, $producto->idproducto);

        return DB::transaction(function () use ($request, $producto, $validated) {
            $imagen = $request->file('imagen_file') ?: $request->file('imagen');

            if ($imagen) {
                $this->eliminarImagen($producto->imagen);
                $validated['imagen'] = $this->guardarImagen($imagen);
            } elseif ($request->has('imagen_actual') && blank($request->input('imagen_actual'))) {
                $this->eliminarImagen($producto->imagen);
                $validated['imagen'] = null;
            }

            $validated['codigo'] = $this->generarCodigoProducto((int) $producto->idproducto);

            $validated['user_updated'] = $request->user()->id;
            $producto->update($validated);

            return ApiResponse::success(
                $producto->fresh()->load(['categoria:idcategoria,nombre', 'marca:idmarca,nombre', 'unidadMedida:idsunat_c03_unidad_medida,nombre,abreviatura']),
                'Producto actualizado correctamente.'
            );
        });
    }

    public function destroy(Request $request, Producto $producto)
    {
        if ($this->esProductoReservado((int) $producto->idproducto)) {
            return ApiResponse::forbidden('El producto con ID 1 esta reservado y no puede desactivarse ni eliminarse.');
        }

        if ((string) $producto->estado_trash === '0') {
            return ApiResponse::fail('El producto ya se encuentra eliminado.', 400);
        }

        $producto->update([
            'estado_trash' => '0',
            'user_trash' => $request->user()->id,
            'user_updated' => $request->user()->id,
        ]);

        return ApiResponse::success($producto, 'Producto eliminado correctamente.');
    }

    public function restore(Request $request, int $producto)
    {
        $producto = Producto::where('idproducto', $producto)
            ->where('estado_trash', '0')
            ->firstOrFail();

        $producto->update([
            'estado_trash' => '1',
            'user_trash' => null,
            'user_updated' => $request->user()->id,
        ]);

        return ApiResponse::success($producto, 'Producto restaurado correctamente.');
    }

    public function catalogos()
    {
        $categorias = Categoria::query()
            ->where('estado_trash', '1')
            ->orderBy('nombre')
            ->get(['idcategoria', 'nombre']);

        $marcas = Marca::query()
            ->where('estado_trash', '1')
            ->orderBy('nombre')
            ->get(['idmarca', 'nombre']);

        $unidades = SunatC03UnidadMedida::query()
            ->orderBy('nombre')
            ->get(['idsunat_c03_unidad_medida', 'nombre', 'abreviatura']);

        return ApiResponse::success([
            'categorias' => $categorias,
            'marcas' => $marcas,
            'unidades_medida' => $unidades,
        ]);
    }

    private function validarProducto(Request $request, ?int $idProducto = null): array
    {
        $tipo = strtoupper(trim((string) $request->input('tipo')));
        $codigoAlterno = trim((string) $request->input('codigo_alterno'));
        $nombre = trim((string) $request->input('nombre'));
        $descripcionAdicional = trim((string) $request->input('descripcion_adicional'));

        $request->merge([
            'tipo' => $tipo,
            'codigo_alterno' => $codigoAlterno === '' ? null : $codigoAlterno,
            'nombre' => $nombre,
            'descripcion_adicional' => $descripcionAdicional === '' ? null : $descripcionAdicional,
            'stock_minimo' => $request->input('stock_minimo') === '' ? null : $request->input('stock_minimo'),
            'precio_compra' => $request->input('precio_compra') === '' ? null : $request->input('precio_compra'),
            'precio_venta' => $request->input('precio_venta') === '' ? null : $request->input('precio_venta'),
        ]);

        $validated = $request->validate([
            'idsunat_unidad_medida' => [
                'required',
                'integer',
                Rule::exists('sunat_c03_unidad_medida', 'idsunat_c03_unidad_medida'),
            ],
            'idcategoria' => [
                'required',
                'integer',
                Rule::exists('categoria', 'idcategoria')->where('estado_trash', '1'),
            ],
            'idmarca' => [
                'required',
                'integer',
                Rule::exists('marca', 'idmarca')->where('estado_trash', '1'),
            ],
            'tipo' => ['required', 'string', Rule::in(['PR', 'SR'])],
            'codigo_alterno' => ['nullable', 'string', 'max:50'],
            'nombre' => ['required', 'string', 'max:250', 'regex:/^[\\pL\\pN\\s\\-\\_\\.\\,\\(\\)\\%\\+\\/]+$/u'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'precio_compra' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'precio_venta' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'descripcion_adicional' => ['nullable', 'string', 'max:250'],
            'imagen' => ['nullable', 'string', 'max:100'],
            'imagen_file' => ['nullable', 'image', 'max:4096'],
            'imagen_actual' => ['nullable', 'string'],
        ], [
            'idsunat_unidad_medida.required' => 'Seleccione la unidad de medida.',
            'idcategoria.required' => 'Seleccione la categoria.',
            'idmarca.required' => 'Seleccione la marca.',
            'tipo.required' => 'Seleccione el tipo de producto.',
            'nombre.required' => 'Ingrese el nombre del producto.',
            'nombre.regex' => 'El nombre del producto contiene caracteres no permitidos.',
        ]);

        $validated['idsunat_c03'] = (int) $validated['idsunat_unidad_medida'];
        unset($validated['idsunat_unidad_medida']);
        unset($validated['imagen_file'], $validated['imagen_actual']);

        if ($idProducto) {
            $request->validate([
                'codigo_alterno' => [
                    'nullable',
                    'string',
                    'max:50',
                    Rule::unique('producto', 'codigo_alterno')->ignore($idProducto, 'idproducto'),
                ],
            ]);

            return $validated;
        }

        $request->validate([
            'codigo_alterno' => ['nullable', 'string', 'max:50', Rule::unique('producto', 'codigo_alterno')],
        ]);

        return $validated;
    }

    private function guardarImagen($imagen): string
    {
        $extension = $imagen->getClientOriginalExtension();
        $nombreArchivo = 'producto_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
        $directorio = public_path($this->getImagenDirectory());

        if (! file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $imagen->move($directorio, $nombreArchivo);

        return $nombreArchivo;
    }

    private function eliminarImagen(?string $nombreArchivo): void
    {
        if (blank($nombreArchivo)) {
            return;
        }

        $ruta = public_path($this->getImagenDirectory() . '/' . $nombreArchivo);

        if (file_exists($ruta)) {
            unlink($ruta);
        }
    }

    private function generarCodigoProducto(int $idProducto): string
    {
        return str_pad((string) $idProducto, 5, '0', STR_PAD_LEFT);
    }

    private function esProductoReservado(int $idProducto): bool
    {
        return $idProducto === self::PRODUCTO_RESERVADO_ID;
    }

}
