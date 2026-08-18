<?php

namespace App\Http\Requests\Reserva;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClienteRapidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cli_tipo_persona_sunat' => ['required', 'string', Rule::in(['NATURAL', 'JURIDICA'])],
            'cli_tipo_documento' => [
                'required',
                'integer',
                Rule::exists('sunat_c06_doc_identidad', 'idsunat_c06_doc_identidad')->where('estado_trash', '1'),
            ],
            'cli_numero_documento' => ['required', 'string', 'max:20'],
            'cli_nombre_razonsocial' => ['required', 'string', 'max:255'],
            'cli_apellidos_nombrecomercial' => ['nullable', 'string', 'max:200'],
            'cli_nombre_persona_natural' => ['nullable', 'string', 'max:100'],
            'cli_apellido_paterno_persona_natural' => ['nullable', 'string', 'max:100'],
            'cli_apellido_materno_persona_natural' => ['nullable', 'string', 'max:100'],
            'cli_sexo' => ['nullable', 'string', Rule::in(['M', 'F'])],
            'cli_fecha_nacimiento' => ['nullable', 'date'],
            'cli_nacionalidad' => ['nullable', 'string', 'max:50'],
            'cli_estado_civil' => ['nullable', 'string', Rule::in(['SOLTERO', 'CASADO', 'DIVORCIADO', 'VIUDO'])],
            'cli_correo' => ['nullable', 'email', 'max:255'],
            'cli_celular' => ['nullable', 'string', 'max:15'],
            'cli_direccion' => ['required', 'string'],
            'cli_direccion_referencia' => ['nullable', 'string'],
            'cli_distrito' => ['nullable', 'integer', 'exists:ubigeo_distrito,idubigeo_distrito'],
            'cli_ubigeo' => ['nullable', 'string', 'max:10'],
        ];
    }
}