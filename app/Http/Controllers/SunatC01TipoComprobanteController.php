<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\SunatC01TipoComprobante;
use Illuminate\Http\Request;

class SunatC01TipoComprobanteController extends Controller
{
    public function index()
    {
        return view('tipo_comprobante');
    }

    public function listar(Request $request)
    {
        $query = SunatC01TipoComprobante::query()
            ->with(['seriesActivas' => fn ($query) => $query->orderByDesc('predeterminado')->orderBy('serie')])
            ->orderByDesc('estado_trash')
            ->orderBy('codigo');

        return ApiResponse::success($query->get());
    }
}
