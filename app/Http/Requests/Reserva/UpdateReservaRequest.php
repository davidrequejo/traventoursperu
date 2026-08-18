<?php

namespace App\Http\Requests\Reserva;

class UpdateReservaRequest extends StoreReservaRequest
{
    protected function reservaIdIgnorado(): ?int
    {
        $reserva = $this->route('reserva');
        return $reserva ? (int) $reserva->idreserva : null;
    }
}