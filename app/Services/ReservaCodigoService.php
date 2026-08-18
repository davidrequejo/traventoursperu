<?php

namespace App\Services;

use App\Models\Reserva;

class ReservaCodigoService
{
    public function generar(string $prefijo = 'RE'): string
    {
        $siguiente = ((int) Reserva::max('idreserva')) + 1;
        return sprintf('%s-%06d', $prefijo, $siguiente);
    }
}