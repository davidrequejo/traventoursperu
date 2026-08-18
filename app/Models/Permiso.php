<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permiso extends Model
{
    protected $table = 'permiso';

    protected $primaryKey = 'idpermiso';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'descripcion',
        'codigo',
        'nivel',
        'icono',
        'tipo',
        'nombre_clave',
        'estado_super_admin',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    public function usuariosPermisos(): HasMany
    {
        return $this->hasMany(UsuarioPermiso::class, 'idpermiso', 'idpermiso');
    }
}
