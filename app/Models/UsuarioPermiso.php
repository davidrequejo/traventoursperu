<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsuarioPermiso extends Model
{
    protected $table = 'usuario_permiso';

    protected $primaryKey = 'idusuario_permiso';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'idusers',
        'idpermiso',
        'crear',
        'editar',
        'eliminar',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idusers', 'id');
    }

    public function permiso(): BelongsTo
    {
        return $this->belongsTo(Permiso::class, 'idpermiso', 'idpermiso');
    }
}
