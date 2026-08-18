<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\PermisoUsuarioSerie;
use App\Models\SerieComprobante;
use App\Models\User;
use App\Models\UsuarioPermiso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Laravel\Jetstream\Agent;

class UserController extends Controller
{
    public function vista()
    {
        return view('usuario');
    }

    public function perfil()
    {
        return view('perfil');
    }

    public function index()
    {
        return $this->listar(request());
    }

    public function listar(Request $request)
    {
        $permisosActivos = DB::table('usuario_permiso')
            ->select('idusers', DB::raw('COUNT(*) as permisos_total'))
            ->where('estado_trash', '1')
            ->groupBy('idusers');

        $baseQuery = DB::table('users as u')
            ->leftJoin('persona as p', 'p.idpersona', '=', 'u.idpersona')
            ->leftJoin('sunat_c06_doc_identidad as doc', function ($join) {
                $join->where('doc.estado_trash', '1')
                    ->whereColumn('doc.idsunat_c06_doc_identidad', 'p.tipo_documento');
            })
            ->leftJoinSub($permisosActivos, 'up', function ($join) {
                $join->on('up.idusers', '=', 'u.id');
            })
            ->where('u.estado_trash', '1');

        $recordsTotal = DB::table('users as u')
            ->where('u.estado_trash', '1')
            ->count('u.id');

        $search = trim((string) $request->input('search.value', ''));

        if ($search !== '') {
            $baseQuery->where(function ($query) use ($search) {
                $query->where('u.email', 'like', "%{$search}%")
                    ->orWhere('p.descripcion', 'like', "%{$search}%")
                    ->orWhere('p.numero_documento', 'like', "%{$search}%")
                    ->orWhere('p.codigo', 'like', "%{$search}%")
                    ->orWhere('doc.abreviatura', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = $search === ''
            ? $recordsTotal
            : (clone $baseQuery)->distinct('u.id')->count('u.id');

        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderColumns = [
            0 => 'u.id',
            1 => 'p.descripcion',
            2 => 'u.email',
            3 => 'permisos_total',
            4 => 'u.estado_trash',
            5 => 'u.updated_at',
        ];
        $orderColumn = $orderColumns[$orderColumnIndex] ?? 'u.id';

        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        $length = $length > 0 ? min($length, 100) : 10;

        $users = $baseQuery
            ->select([
                'u.id',
                'u.email',
                'u.estado_trash',
                'u.two_factor_confirmed_at',
                'u.updated_at',
                'p.descripcion as persona_descripcion',
                'p.numero_documento as persona_numero_documento',
                'p.codigo as persona_codigo',
            ])
            ->selectRaw('COALESCE(doc.abreviatura, p.tipo_documento) as persona_tipo_documento')
            ->selectRaw("
                CASE
                    WHEN p.foto_perfil IS NOT NULL AND p.foto_perfil <> '' THEN p.foto_perfil
                    WHEN p.sexo = 'F' THEN 'mujer.png'
                    ELSE 'hombre.png'
                END as persona_foto_perfil
            ")
            ->selectRaw('COALESCE(up.permisos_total, 0) as permisos_total')
            ->orderBy($orderColumn, $orderDirection)
            ->offset($start)
            ->limit($length)
            ->get();

        return response()->json([
            'status' => true,
            'code_error' => 0,
            'message' => 'OK',
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $users,
        ]);
    }

    public function personasDisponibles(Request $request)
    {
        $search = trim((string) $request->input('term', ''));
        $page = max((int) $request->input('page', 1), 1);
        $perPage = 10;

        $query = DB::table('persona as p')
            ->leftJoin('users as u', function ($join) {
                $join->on('u.idpersona', '=', 'p.idpersona');
            })
            ->whereNull('u.id')
            ->where('p.estado_trash', '1')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('p.descripcion', 'like', "%{$search}%")
                        ->orWhere('p.numero_documento', 'like', "%{$search}%")
                        ->orWhere('p.codigo', 'like', "%{$search}%");
                });
            })
            ->orderBy('p.descripcion');

        $personas = (clone $query)
            ->offset(($page - 1) * $perPage)
            ->limit($perPage + 1)
            ->get([
                'p.idpersona as id',
                'p.descripcion',
                'p.numero_documento',
                'p.codigo',
            ]);

        $hasMore = $personas->count() > $perPage;

        $personas = $personas
            ->take($perPage)
            ->map(function ($persona) {
                $detalle = collect([$persona->codigo, $persona->numero_documento])
                    ->filter()
                    ->implode(' | ');

                return [
                    'id' => $persona->id,
                    'text' => trim($persona->descripcion . ($detalle ? " ({$detalle})" : '')),
                    'descripcion' => $persona->descripcion,
                ];
            });

        return response()->json([
            'results' => $personas,
            'pagination' => [
                'more' => $hasMore,
            ],
        ]);
    }

    public function validarEmail(Request $request)
    {
        $email = trim((string) $request->input('email', ''));
        $userId = $request->integer('id');

        $existe = $email !== ''
            && User::where('email', $email)
                ->when($userId > 0, fn ($query) => $query->where('id', '<>', $userId))
                ->exists();

        return response()->json([
            'status' => true,
            'available' => ! $existe,
            'message' => $existe ? 'Este correo ya esta registrado.' : 'Correo disponible.',
        ]);
    }

    public function seriesDisponibles()
    {
        $series = SerieComprobante::query()
            ->with(['tipoComprobante:idsunat_c01_tipo_comprobante,codigo,nombre,abreviatura'])
            ->where('estado_trash', '1')
            ->orderBy('idsunat_c01_tipo_comprobante')
            ->orderBy('serie')
            ->orderBy('numero')
            ->get([
                'idserie_comprobante',
                'idsunat_c01_tipo_comprobante',
                'serie',
                'numero',
                'predeterminado',
            ]);

        return ApiResponse::success($series);
    }

    public function store(Request $request)
    {
        $this->normalizarPermisosJson($request);
        $this->normalizarSeriesJson($request);

        $validator = validator($request->all(), $this->rules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        $user = DB::transaction(function () use ($request, $validator) {
            $data = $validator->validated();
            $permisos = $data['permisos'] ?? [];
            $series = $data['series'] ?? [];
            unset($data['permisos'], $data['permisos_json'], $data['series'], $data['series_json']);

            $user = User::create([
                ...$data,
                'user_created' => Auth::id(),
            ]);

            $this->syncPermisos($user, $permisos, Auth::id());
            $this->syncSeries($user, $series);

            return $user;
        });

        $user->load([
            'persona.docIdentidad',
            'permisosUsuario.permiso',
            'seriesUsuario.serieComprobante.tipoComprobante',
        ]);

        return ApiResponse::success($user, 'Usuario creado correctamente.');
    }

    public function show(User $user)
    {
        $user->load([
            'persona.docIdentidad',
            'persona.cargo',
            'permisosUsuario' => fn ($query) => $query->where('estado_trash', '1'),
            'permisosUsuario.permiso',
            'seriesUsuario.serieComprobante.tipoComprobante',
        ]);

        return ApiResponse::success($user);
    }

    public function sesiones(Request $request, User $user)
    {
        if (config('session.driver') !== 'database') {
            return ApiResponse::fail('El control de sesiones requiere SESSION_DRIVER=database.', 400);
        }

        $currentSessionId = $request->session()->getId();
        $authUserId = (int) Auth::id();
        $sessions = $this->activeSessionsQuery($user)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($session) use ($currentSessionId, $authUserId, $user) {
                $agent = $this->createAgent($session->user_agent);
                $isCurrentDevice = (int) $user->id === $authUserId && $session->id === $currentSessionId;

                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'platform' => $agent->platform() ?: 'Desconocido',
                    'browser' => $agent->browser() ?: 'Desconocido',
                    'is_desktop' => $agent->isDesktop(),
                    'is_current_device' => $isCurrentDevice,
                    'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                    'last_activity_at' => Carbon::createFromTimestamp($session->last_activity)->format('Y-m-d H:i:s'),
                ];
            });

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'sessions' => $sessions,
        ]);
    }

    public function cerrarSesion(Request $request, User $user, string $sessionId)
    {
        if (config('session.driver') !== 'database') {
            return ApiResponse::fail('El control de sesiones requiere SESSION_DRIVER=database.', 400);
        }

        if ($this->isSelfAction($request, $user) && $sessionId === $request->session()->getId()) {
            return ApiResponse::forbidden('No puedes cerrar la sesion actual desde este panel.');
        }

        $deleted = $this->activeSessionsQuery($user)
            ->where('id', $sessionId)
            ->delete();

        return ApiResponse::success([
            'deleted' => $deleted,
        ], $deleted ? 'Sesion cerrada correctamente.' : 'La sesion ya no se encuentra activa.');
    }

    public function cerrarSesiones(Request $request, User $user)
    {
        if (config('session.driver') !== 'database') {
            return ApiResponse::fail('El control de sesiones requiere SESSION_DRIVER=database.', 400);
        }

        $query = $this->activeSessionsQuery($user);

        if ($this->isSelfAction($request, $user)) {
            $query->where('id', '!=', $request->session()->getId());
        }

        $deleted = $query->delete();

        return ApiResponse::success([
            'deleted' => $deleted,
        ], "{$deleted} sesion(es) cerrada(s) correctamente.");
    }

    public function update(Request $request, User $user)
    {
        $this->normalizarPermisosJson($request);
        $this->normalizarSeriesJson($request);

        $validator = validator($request->all(), $this->rules(true, $user->id));

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        if ($this->isSelfAction($request, $user) && $request->input('estado_trash') === '0') {
            return ApiResponse::forbidden('No puedes desactivar tu propio usuario mientras tienes la sesion iniciada.');
        }

        DB::transaction(function () use ($request, $validator, $user) {
            $data = $validator->validated();
            $permisos = $data['permisos'] ?? null;
            $series = $data['series'] ?? null;
            unset($data['permisos'], $data['permisos_json'], $data['series'], $data['series_json']);

            if (empty($data['password'])) {
                unset($data['password']);
            }

            $user->update([
                ...$data,
                'user_updated' => Auth::id(),
            ]);

            if (is_array($permisos)) {
                $this->syncPermisos($user, $permisos, Auth::id());
            }

            if (is_array($series)) {
                $this->syncSeries($user, $series);
            }
        });

        $user->load([
            'persona.docIdentidad',
            'permisosUsuario' => fn ($query) => $query->where('estado_trash', '1'),
            'permisosUsuario.permiso',
            'seriesUsuario.serieComprobante.tipoComprobante',
        ]);

        return ApiResponse::success($user, 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($this->isSelfAction($request, $user)) {
            return ApiResponse::forbidden('No puedes eliminar ni desactivar tu propio usuario mientras tienes la sesion iniciada.');
        }

        $user->update([
            'estado_trash' => '0',
            'user_trash' => Auth::id(),
        ]);

        return ApiResponse::success($user, 'Usuario eliminado correctamente.');
    }

    public function destroyPermanente(Request $request, User $user)
    {
        if ($this->isSelfAction($request, $user)) {
            return ApiResponse::forbidden('No puedes eliminar permanentemente tu propio usuario mientras tienes la sesion iniciada.');
        }

        DB::transaction(function () use ($user) {
            UsuarioPermiso::where('idusers', $user->id)->delete();
            PermisoUsuarioSerie::where('idusers', $user->id)->delete();
            $user->delete();
        });

        return ApiResponse::success(null, 'Usuario eliminado permanentemente.');
    }

    public function destroyPermanenteMasivo(Request $request)
    {
        $validator = validator($request->all(), [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', Rule::exists('users', 'id')],
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        $ids = collect($validator->validated()['ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $authUserId = (int) Auth::id();
        $idsToDelete = $ids
            ->reject(fn ($id) => $id === $authUserId)
            ->values();

        if ($idsToDelete->isEmpty()) {
            return ApiResponse::forbidden('No puedes eliminar permanentemente tu propio usuario mientras tienes la sesion iniciada.');
        }

        $deleted = DB::transaction(function () use ($idsToDelete) {
            UsuarioPermiso::whereIn('idusers', $idsToDelete)->delete();
            PermisoUsuarioSerie::whereIn('idusers', $idsToDelete)->delete();

            return User::whereIn('id', $idsToDelete)->delete();
        });

        return ApiResponse::success([
            'selected' => $ids->count(),
            'omitted' => $ids->count() - $idsToDelete->count(),
            'deleted' => $deleted,
        ], "{$deleted} usuario(s) eliminado(s) permanentemente.");
    }

    private function isSelfAction(Request $request, User $user): bool
    {
        return (int) Auth::id() === (int) $user->id;
    }

    private function activeSessionsQuery(User $user)
    {
        $sessionLifetime = (int) config('session.lifetime', 120);
        $activeSince = Carbon::now()->subMinutes($sessionLifetime)->getTimestamp();

        return DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->where('last_activity', '>=', $activeSince);
    }

    private function createAgent(?string $userAgent): Agent
    {
        return tap(new Agent(), fn ($agent) => $agent->setUserAgent($userAgent));
    }

    private function rules(bool $updating = false, ?int $userId = null): array
    {
        $required = $updating ? 'sometimes' : 'required';
        $password = $updating ? ['nullable', 'string', 'min:8', 'confirmed'] : ['required', 'string', 'min:8', 'confirmed'];

        return [
            'idpersona' => [
                $required,
                'integer',
                Rule::exists('persona', 'idpersona'),
                Rule::unique('users', 'idpersona')->ignore($userId),
            ],
            'name' => [$required, 'string', 'max:255'],
            'email' => [
                $required,
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => $password,
            'estado_trash' => ['nullable', 'string', 'max:1'],
            'user_update_sistema' => ['nullable', 'string', 'max:1'],
            'permisos_json' => ['nullable', 'json'],
            'permisos' => ['nullable', 'array'],
            'permisos.*.idpermiso' => ['required_with:permisos', 'integer', Rule::exists('permiso', 'idpermiso')],
            'permisos.*.crear' => ['nullable', 'string', 'max:1'],
            'permisos.*.editar' => ['nullable', 'string', 'max:1'],
            'permisos.*.eliminar' => ['nullable', 'string', 'max:1'],
            'series_json' => ['nullable', 'json'],
            'series' => ['nullable', 'array'],
            'series.*.idserie_comprobante' => [
                'required_with:series',
                'integer',
                Rule::exists('serie_comprobante', 'idserie_comprobante')->where('estado_trash', '1'),
            ],
        ];
    }

    private function normalizarPermisosJson(Request $request): void
    {
        if (! $request->has('permisos_json')) {
            return;
        }

        $json = trim((string) $request->input('permisos_json', '[]')) ?: '[]';
        $permisos = $json === '' ? [] : json_decode($json, true);

        if (! is_array($permisos)) {
            $permisos = [];
        }

        $request->merge([
            'permisos_json' => $json,
            'permisos' => $permisos,
        ]);
    }

    private function normalizarSeriesJson(Request $request): void
    {
        if (! $request->has('series_json')) {
            return;
        }

        $json = trim((string) $request->input('series_json', '[]')) ?: '[]';
        $series = $json === '' ? [] : json_decode($json, true);

        if (! is_array($series)) {
            $series = [];
        }

        $request->merge([
            'series_json' => $json,
            'series' => $series,
        ]);
    }

    private function syncPermisos(User $user, array $permisos, int $authUserId): void
    {
        UsuarioPermiso::where('idusers', $user->id)
            ->where('estado_trash', '1')
            ->update([
                'estado_trash' => '0',
                'user_trash' => $authUserId,
            ]);

        foreach ($permisos as $permiso) {
            UsuarioPermiso::create([
                'idusers' => $user->id,
                'idpermiso' => $permiso['idpermiso'],
                'crear' => $permiso['crear'] ?? null,
                'editar' => $permiso['editar'] ?? null,
                'eliminar' => $permiso['eliminar'] ?? null,
                'estado_trash' => '1',
                'user_created' => $authUserId,
            ]);
        }
    }

    private function syncSeries(User $user, array $series): void
    {
        $idsSeries = collect($series)
            ->pluck('idserie_comprobante')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        PermisoUsuarioSerie::where('idusers', $user->id)->delete();

        foreach ($idsSeries as $idSerie) {
            PermisoUsuarioSerie::create([
                'idusers' => $user->id,
                'idserie_comprobante' => $idSerie,
            ]);
        }
    }

}
