<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RDocumentoArchivo extends Model
{
    protected $table = 'rdocumento_archivo';

    protected $primaryKey = 'idrdocumento_archivo';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'idrdocumento',
        'nombre_original',
        'nombre_guardado',
        'nombre_visible',
        'extension',
        'tipo_mime',
        'peso_bytes',
        'observacion_documento',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    protected function casts(): array
    {
        return [
            'idrdocumento' => 'integer',
            'peso_bytes' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'user_trash' => 'integer',
            'user_created' => 'integer',
            'user_updated' => 'integer',
        ];
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(RDocumento::class, 'idrdocumento', 'idrdocumento');
    }
}

