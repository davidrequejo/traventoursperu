<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiReniecSunat
{
    protected string $token;
    protected string $baseUrl;
    protected bool $usaFactiliza;
    protected bool $ignoreProxy;

    public function __construct()
    {
        $this->token = (string) config('services.apisperu.token');
        $this->baseUrl = rtrim((string) config('services.apisperu.url'), '/');
        $this->usaFactiliza = str_contains($this->baseUrl, 'factiliza.com');
        $this->ignoreProxy = (bool) config('services.apisperu.ignore_proxy', true);
    }

    public function datosReniec(string $dni): array
    {
        return $this->consultarApi('dni', $dni);
    }

    public function datosSunat(string $ruc): array
    {
        return $this->consultarApi('ruc', $ruc);
    }

    private function consultarApi(string $tipo, string $numero): array
    {
        try {
            if (empty($this->token)) {
                return [
                    'status' => false,
                    'code' => 500,
                    'message' => 'No se encontro el token de la API.',
                    'data' => null,
                ];
            }

            if (empty($this->baseUrl)) {
                return [
                    'status' => false,
                    'code' => 500,
                    'message' => 'No se encontro la URL base de la API.',
                    'data' => null,
                ];
            }

            $request = Http::acceptJson()
                ->timeout(15)
                ->connectTimeout(10);

            if ($this->ignoreProxy) {
                $request = $request->withOptions([
                    'proxy' => '',
                    'curl' => [
                        CURLOPT_PROXY => '',
                    ],
                ]);
            }

            if ($this->usaFactiliza) {
                $response = $request
                    ->withToken($this->token)
                    ->get($this->urlFactiliza($tipo, $numero));
            } else {
                $response = $request->get("{$this->baseUrl}/{$tipo}/{$numero}", [
                    'token' => $this->token,
                ]);
            }

            if ($response->successful()) {
                return [
                    'status' => true,
                    'code' => $response->status(),
                    'message' => 'Consulta realizada correctamente.',
                    'data' => $response->json(),
                ];
            }

            return [
                'status' => false,
                'code' => $response->status(),
                'message' => $this->mapearErrorHttp($response->status(), $tipo),
                'data' => $response->json(),
            ];
        } catch (ConnectionException $e) {
            Log::error("Error de conexion API {$tipo}", [
                'numero' => $numero,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'code' => 503,
                'message' => 'No fue posible conectar con el servicio externo. Verifica internet, DNS o disponibilidad del proveedor.',
                'data' => null,
                'error_detail' => $e->getMessage(),
            ];
        } catch (RequestException $e) {
            Log::error("Error HTTP API {$tipo}", [
                'numero' => $numero,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'code' => 500,
                'message' => 'La API externa respondio con un error inesperado.',
                'data' => null,
                'error_detail' => $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            Log::error("Error general API {$tipo}", [
                'numero' => $numero,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return [
                'status' => false,
                'code' => 500,
                'message' => 'Ocurrio un error interno al procesar la consulta.',
                'data' => null,
                'error_detail' => $e->getMessage(),
            ];
        }
    }

    private function mapearErrorHttp(int $status, string $tipo): string
    {
        return match ($status) {
            400 => "La consulta de {$tipo} es invalida.",
            401 => 'Token no autorizado para consumir la API.',
            403 => 'Acceso denegado por el proveedor de la API.',
            404 => "No se encontraron datos para la consulta de {$tipo}.",
            422 => "Los datos enviados para {$tipo} no son validos.",
            429 => 'Se excedio el limite de consultas de la API.',
            500 => 'El proveedor externo presento un error interno.',
            502 => 'El proveedor externo devolvio una respuesta invalida.',
            503 => 'El servicio externo no esta disponible en este momento.',
            504 => 'El servicio externo tardo demasiado en responder.',
            default => "Ocurrio un error inesperado al consultar {$tipo}.",
        };
    }

    private function urlFactiliza(string $tipo, string $numero): string
    {
        $endpoint = $tipo === 'ruc' ? 'ruc/info' : 'dni/info';

        if (preg_match('#/v\d+/#', $this->baseUrl)) {
            $base = preg_replace('#/(dni|ruc)/info$#', '', $this->baseUrl);

            return "{$base}/{$endpoint}/{$numero}";
        }

        return "{$this->baseUrl}/{$endpoint}/{$numero}";
    }
}
