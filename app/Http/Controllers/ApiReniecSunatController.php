<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Services\ApiReniecSunat;
use Illuminate\Http\Request;

class ApiReniecSunatController extends Controller
{
    protected ApiReniecSunat $apiReniecSunat;

    public function __construct(ApiReniecSunat $apiReniecSunat)
    {
        $this->apiReniecSunat = $apiReniecSunat;
    }

    /**
     * Buscar DNI en RENIEC
     */
    public function buscarReniec(Request $request)
    {
        $validator = validator($request->all(), [
            'dni' => ['required', 'numeric', 'digits:8'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        $resp = $this->apiReniecSunat->datosReniec($request->dni);

        if (!$resp['status']) {
            return ApiResponse::error(
                $resp['message'],
                $resp['code'] ?? 500,
                $resp['data'] ?? null
            );
        }

        return ApiResponse::success(
            $resp['data'],
            $resp['message'] ?? 'Consulta exitosa.'
        );
    }

    /**
     * Buscar RUC en SUNAT
     */
    public function buscarSunat(Request $request)
    {
        $validator = validator($request->all(), [
            'ruc' => ['required', 'numeric', 'digits:11'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        $resp = $this->apiReniecSunat->datosSunat($request->ruc);

        if (!$resp['status']) {
            return ApiResponse::error(
                $resp['message'],
                $resp['code'] ?? 500,
                $resp['data'] ?? null
            );
        }

        return ApiResponse::success(
            $resp['data'],
            $resp['message'] ?? 'Consulta exitosa.'
        );
    }
}

