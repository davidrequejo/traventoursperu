<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PersonaController extends Controller
{
    public function index()
    {
        return ApiResponse::success(
            Persona::with('cargo')
                ->where('estado_trash', '1')
                ->latest('idpersona')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validator = validator($request->all(), $this->rules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        $persona = Persona::create([
            ...$validator->validated(),
            'user_created' => Auth::id(),
        ]);

        return ApiResponse::success($persona->load('cargo'), 'Persona creada correctamente.');
    }

    public function show(Persona $persona)
    {
        return ApiResponse::success($persona->load(['cargo', 'users']));
    }

    public function update(Request $request, Persona $persona)
    {
        $validator = validator($request->all(), $this->rules(true));

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        $persona->update([
            ...$validator->validated(),
            'user_updated' => Auth::id(),
        ]);

        return ApiResponse::success($persona->load('cargo'), 'Persona actualizada correctamente.');
    }

    public function destroy(Request $request, Persona $persona)
    {
        $persona->update([
            'estado_trash' => '0',
            'user_trash' => Auth::id(),
        ]);

        return ApiResponse::success($persona, 'Persona eliminada correctamente.');
    }

    private function rules(bool $updating = false): array
    {
        $required = $updating ? 'sometimes' : 'required';

        return [
            'idcargo_trabajador' => ['nullable', 'integer', Rule::exists('persona_cargo', 'idpersona_cargo')],
            'tipo_persona_sunat' => [$required, 'string', 'max:10'],
            'nombre_razonsocial' => ['nullable', 'string', 'max:200'],
            'apellidos_nombrecomercial' => ['nullable', 'string', 'max:200'],
            'tipo_documento' => [$required, 'integer', Rule::exists('sunat_c06_doc_identidad', 'idsunat_c06_doc_identidad')->where('estado_trash', '1')],
            'numero_documento' => ['nullable', 'string', 'max:20'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'nacionalidad' => ['nullable', 'string', 'max:100'],
            'estado_civil' => ['nullable', 'string', 'max:100'],
            'celular' => ['nullable', 'string', 'max:15'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'direccion_referencia' => ['nullable', 'string', 'max:200'],
            'departamento' => ['nullable', 'string', 'max:45'],
            'provincia' => ['nullable', 'string', 'max:45'],
            'distrito' => ['nullable', 'string', 'max:45'],
            'cod_ubigeo' => ['nullable', 'string', 'max:10'],
            'correo' => ['nullable', 'email', 'max:100'],
            'cuenta_bancaria' => ['nullable', 'string', 'max:45'],
            'cci' => ['nullable', 'string', 'max:45'],
            'titular_cuenta' => ['nullable', 'string', 'max:45'],
            'numero_licencia' => ['nullable', 'string', 'max:15'],
            'placa_vehiculo' => ['nullable', 'string', 'max:15'],
            'foto_perfil' => ['nullable', 'string', 'max:100'],
        ];
    }
}
