<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PersonaTipo extends Model
{
    protected $table = 'persona_tipo';

    protected $primaryKey = 'idpersona_tipo';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'user_trash' => 'integer',
            'user_created' => 'integer',
            'user_updated' => 'integer',
        ];
    }

    // Relación con PersonaTipoPersona (ajusta el nombre de la clase si es necesario)
    public function personasTipoPersona(): HasMany
    {
        return $this->hasMany(PersonaTipoPersona::class, 'idpersona_tipo', 'idpersona_tipo');
    }
}