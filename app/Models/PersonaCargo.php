<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PersonaCargo extends Model
{
    protected $table = 'persona_cargo';

    protected $primaryKey = 'idpersona_cargo';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'nombre',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class, 'idcargo_trabajador', 'idpersona_cargo');
    }
}
