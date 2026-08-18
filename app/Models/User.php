<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    private ?array $permisosMapaCache = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'idpersona',
        'name',
        'email',
        'email_verified_at',
        'password',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
        'user_update_sistema',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
        'display_name',
        'display_role',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'idpersona', 'idpersona');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->persona?->nombre_persona_natural
            ?: $this->persona?->descripcion
            ?: ($this->attributes['name'] ?? 'Usuario');
    }

    public function getDisplayRoleAttribute(): string
    {
        return $this->persona?->cargo?->nombre ?: ($this->attributes['email'] ?? 'Usuario autenticado');
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        $photo = trim((string) ($this->persona?->foto_perfil ?? ''));

        if ($photo !== '') {
            if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://') || str_starts_with($photo, '/')) {
                return $photo;
            }

            if (str_contains($photo, '/')) {
                return asset($photo);
            }

            return asset('assets/modulo/persona/perfil/' . $photo);
        }

        $defaultPhoto = $this->persona?->sexo === 'F' ? 'mujer.png' : 'hombre.png';

        return asset('assets/modulo/persona/perfil/' . $defaultPhoto);
    }

    public function permisosUsuario(): HasMany
    {
        return $this->hasMany(UsuarioPermiso::class, 'idusers', 'id');
    }

    public function seriesUsuario(): HasMany
    {
        return $this->hasMany(PermisoUsuarioSerie::class, 'idusers', 'id');
    }

    public function puede(string|array $nombreClave, string $accion = 'ver'): bool
    {
        $accion = strtolower(trim($accion));

        if (! in_array($accion, ['ver', 'crear', 'editar', 'eliminar'], true)) {
            return false;
        }

        $nombresClave = is_array($nombreClave) ? $nombreClave : [$nombreClave];
        $nombresClave = array_filter(array_map(fn ($clave) => strtolower(trim((string) $clave)), $nombresClave));
        $permisos = $this->permisosMapa();

        foreach ($nombresClave as $clave) {
            if (($permisos[$clave][$accion] ?? false) === true) {
                return true;
            }
        }

        return false;
    }

    public function permisosParaFrontend(array $modulos = []): array
    {
        $permisos = $this->permisosMapa();

        if ($modulos === []) {
            return $permisos;
        }

        return collect($modulos)
            ->mapWithKeys(function (string $modulo) use ($permisos) {
                $clave = strtolower(trim($modulo));

                return [$clave => $permisos[$clave] ?? [
                    'ver' => false,
                    'crear' => false,
                    'editar' => false,
                    'eliminar' => false,
                ]];
            })
            ->all();
    }

    public function limpiarCachePermisos(): void
    {
        $this->permisosMapaCache = null;
    }

    private function permisosMapa(): array
    {
        if ($this->permisosMapaCache !== null) {
            return $this->permisosMapaCache;
        }

        $this->permisosMapaCache = [];

        $this->permisosUsuario()
            ->with('permiso')
            ->where('estado_trash', '1')
            ->whereHas('permiso', fn ($query) => $query->where('estado_trash', '1'))
            ->get()
            ->each(function (UsuarioPermiso $usuarioPermiso) {
                $clave = strtolower(trim((string) $usuarioPermiso->permiso?->nombre_clave));

                if ($clave === '') {
                    return;
                }

                $actual = $this->permisosMapaCache[$clave] ?? [
                    'ver' => false,
                    'crear' => false,
                    'editar' => false,
                    'eliminar' => false,
                ];

                $this->permisosMapaCache[$clave] = [
                    'ver' => true,
                    'crear' => $actual['crear'] || $usuarioPermiso->crear === '1',
                    'editar' => $actual['editar'] || $usuarioPermiso->editar === '1',
                    'eliminar' => $actual['eliminar'] || $usuarioPermiso->eliminar === '1',
                ];
            });

        return $this->permisosMapaCache;
    }
}
