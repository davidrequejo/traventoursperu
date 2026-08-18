<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TourTurno extends Model
{
    protected $table = 'tours_turno';

    protected $primaryKey = 'idtours_turno';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
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

    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class, 'idtours_turno', 'idtours_turno');
    }
}
