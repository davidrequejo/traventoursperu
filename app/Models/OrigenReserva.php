<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrigenReserva extends Model
{
    protected $table = 'origen_reserva';

    protected $primaryKey = 'idorigen_reserva';

    protected $fillable = ['descripcion', 'estado_trash', 'user_trash', 'user_created', 'user_updated'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'updated_at' => 'datetime'];
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'idorigen_reserva', 'idorigen_reserva');
    }
}
