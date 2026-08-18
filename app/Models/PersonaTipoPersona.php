<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonaTipoPersona extends Model
{
    use HasFactory;

    protected $table = 'persona_tipo_persona';
    protected $primaryKey = 'idpersona_tipo_persona';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'idpersona',
        'idpersona_tipo',
        'user_created',
        'user_updated',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relación con Persona
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'idpersona', 'idpersona');
    }

    // Relación con PersonaTipo
    public function personaTipo()
    {
        return $this->belongsTo(PersonaTipo::class, 'idpersona_tipo', 'idpersona_tipo');
    }

    // Relación con el usuario que creó el registro
    public function userCreated()
    {
        return $this->belongsTo(User::class, 'user_created', 'id');
    }

    // Relación con el usuario que actualizó el registro
    public function userUpdated()
    {
        return $this->belongsTo(User::class, 'user_updated', 'id');
    }
}