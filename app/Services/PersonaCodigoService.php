<?php

namespace App\Services;

use App\Models\Persona;

class PersonaCodigoService
{
    public static function siguienteCodigo(): string
    {
        $maxId = Persona::query()
            ->lockForUpdate()
            ->max('idpersona') ?? 0;

        return str_pad((string) ($maxId + 1), 6, '0', STR_PAD_LEFT);
    }
}
