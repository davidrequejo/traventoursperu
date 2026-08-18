<?php

namespace App\Http\Requests\Reserva;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePagoReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idreserva_amortizar' => ['required', 'integer', Rule::exists('reserva', 'idreserva')->where('estado_trash', '1')],
            'p_idpersona_cliente' => ['required', 'integer', Rule::exists('persona', 'idpersona')->where('estado_trash', '1')],
            'f_serie_comprobante' => ['required', 'integer', Rule::exists('serie_comprobante', 'idserie_comprobante')->where('estado_trash', '1')],
            'f_idsunat_c01' => ['required', 'integer', 'exists:sunat_c01_tipo_comprobante,idsunat_c01_tipo_comprobante'],
            'f_tipo_comprobante' => ['required', 'string', 'in:01,03,12'],
            'f_metodo_pago_1' => ['required', 'integer', Rule::exists('cuenta_bancaria', 'idcuenta_bancaria')->where('estado_trash', '1')],
            'monto_amortizar' => ['required', 'numeric', 'gt:0'],
            'observacion_amortizar' => ['nullable', 'string', 'max:500'],
            'detalle_comprobante_amortizar' => ['nullable', 'string'],
        ];
    }
}