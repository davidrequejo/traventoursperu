<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Empresa;
use App\Models\Persona;
use App\Models\SunatC06DocIdentidad;
use Greenter\XMLSecLibs\Certificate\X509Certificate;
use Greenter\XMLSecLibs\Certificate\X509ContentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Symfony\Component\Process\Process;

class EmpresaController extends Controller
{
    private function getLogoDirectory(): string
    {
        return 'assets/modulo/empresa/logo';
    }

    private function getCertificadoDirectory(): string
    {
        return 'assets/modulo/facturacion/certificado';
    }

    private function ensureLogoDirectoryExists(): string
    {
        $directorio = public_path($this->getLogoDirectory());

        if (! File::isDirectory($directorio)) {
            File::makeDirectory($directorio, 0755, true);
        }

        return $directorio;
    }

    private function ensureCertificadoDirectoryExists(): string
    {
        $directorio = public_path($this->getCertificadoDirectory());

        if (! File::isDirectory($directorio)) {
            File::makeDirectory($directorio, 0755, true);
        }

        return $directorio;
    }

    public function index()
    {
        $tipoDocumentoRucId = SunatC06DocIdentidad::query()
            ->where('code_sunat', '6')
            ->value('idsunat_c06_doc_identidad') ?? 6;

        return view('empresa', [
            'empresaRegistrada' => Empresa::query()->exists(),
            'tipoDocumentoRucId' => $tipoDocumentoRucId,
        ]);
    }

    public function listar(Request $request)
    {
        $query = Empresa::query()
            ->with(['persona.docIdentidad', 'docIdentidad']);

        if (! $request->boolean('incluir_trash')) {
            $query->where('estado_trash', '1');
        }

        // Compatibilidad con llamadas simples (fuera de DataTables server-side).
        if (! $request->has('draw')) {
            if ($request->filled('buscar')) {
                $buscar = trim((string) $request->input('buscar'));
                $query->where(function ($subQuery) use ($buscar) {
                    $subQuery
                        ->where('nombre_razon_social', 'like', "%{$buscar}%")
                        ->orWhere('nombre_comercial', 'like', "%{$buscar}%")
                        ->orWhere('numero_documento', 'like', "%{$buscar}%")
                        ->orWhere('correo', 'like', "%{$buscar}%");
                });
            }

            return ApiResponse::success($query->latest('idempresa')->get());
        }

        $recordsTotal = (clone $query)->count();
        $search = trim((string) $request->input('search.value', ''));

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('nombre_razon_social', 'like', "%{$search}%")
                    ->orWhere('nombre_comercial', 'like', "%{$search}%")
                    ->orWhere('numero_documento', 'like', "%{$search}%")
                    ->orWhere('correo', 'like', "%{$search}%")
                    ->orWhere('telefono1', 'like', "%{$search}%")
                    ->orWhere('telefono2', 'like', "%{$search}%")
                    ->orWhere('domicilio_fiscal', 'like', "%{$search}%")
                    ->orWhere('distrito', 'like', "%{$search}%")
                    ->orWhere('provincia', 'like', "%{$search}%")
                    ->orWhere('departamento', 'like', "%{$search}%")
                    ->orWhereHas('docIdentidad', function ($docQuery) use ($search) {
                        $docQuery->where('abreviatura', 'like', "%{$search}%");
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();
        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderColumns = [
            0 => 'idempresa',
            2 => 'nombre_razon_social',
            3 => 'numero_documento',
            4 => 'correo',
            5 => 'distrito',
            6 => 'estado_trash',
            7 => 'updated_at',
        ];
        $orderBy = $orderColumns[$orderColumnIndex] ?? 'idempresa';
        $query->orderBy($orderBy, $orderDir);
        if ($orderBy !== 'idempresa') {
            $query->orderBy('idempresa', 'desc');
        }

        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);

        if ($length === -1) {
            $data = $query->get();
        } else {
            $length = max(1, min($length, 200));
            $data = $query->skip($start)->take($length)->get();
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function show(Empresa $empresa)
    {
        $empresa->load(['persona.docIdentidad', 'docIdentidad']);
        $empresa->fe_certificado_pem_url = $empresa->fe_certificado_archivo_pem
            ? route('empresa.certificado.descargar', ['empresa' => $empresa->idempresa, 'tipo' => 'pem'])
            : null;
        $empresa->fe_certificado_cer_url = $empresa->fe_certificado_archivo_cer
            ? route('empresa.certificado.descargar', ['empresa' => $empresa->idempresa, 'tipo' => 'cer'])
            : null;

        return ApiResponse::success($empresa);
    }

    public function store(Request $request)
    {
        if (Empresa::query()->exists()) {
            return ApiResponse::fail('Ya existe una empresa registrada. Solo se permite una empresa para la facturacion del sistema.', 422);
        }

        $validated = $this->validarEmpresa($request);

        return DB::transaction(function () use ($request, $validated) {
            $persona = $this->resolverPersona($request, $validated);
            $validated['idpersona'] = $persona->idpersona;

            if ($request->hasFile('logo_file')) {
                $validated['logo'] = $this->guardarLogo($request->file('logo_file'));
            }

            if ($request->hasFile('fe_certificado_file')) {
                $this->prepararCertificadoElectronico($request, $validated);
            }

            $this->cifrarCredencialesFe($validated);

            $validated['estado_trash'] = '1';
            $validated['user_created'] = Auth::id();
            $validated['user_updated'] = Auth::id();
            $validated['user_trash'] = null;

            $empresa = Empresa::create($validated);

            return ApiResponse::success($empresa->load(['persona.docIdentidad', 'docIdentidad']), 'Empresa registrada correctamente.');
        });
    }

    public function update(Request $request, Empresa $empresa)
    {
        $validated = $this->validarEmpresa($request, $empresa->idempresa);

        return DB::transaction(function () use ($request, $empresa, $validated) {
            $persona = $this->resolverPersona($request, $validated, $empresa->persona);
            $validated['idpersona'] = $persona->idpersona;

            if ($request->hasFile('logo_file')) {
                $this->eliminarLogo($empresa->logo);
                $validated['logo'] = $this->guardarLogo($request->file('logo_file'));
            } elseif ($request->has('logo_actual') && blank($request->input('logo_actual'))) {
                $this->eliminarLogo($empresa->logo);
                $validated['logo'] = null;
            }

            if ($request->hasFile('fe_certificado_file')) {
                $this->prepararCertificadoElectronico($request, $validated, $empresa);
            } else {
                unset($validated['fe_certificado_archivo_base']);
            }

            $this->cifrarCredencialesFe($validated, $empresa);

            $validated['user_updated'] = Auth::id();
            $empresa->update($validated);

            return ApiResponse::success($empresa->load(['persona.docIdentidad', 'docIdentidad']), 'Empresa actualizada correctamente.');
        });
    }

    public function destroy(Request $request, Empresa $empresa)
    {
        if ((string) $empresa->estado_trash === '0') {
            return ApiResponse::fail('La empresa ya se encuentra eliminada.', 400);
        }

        $empresa->update([
            'estado_trash' => '0',
            'user_trash' => Auth::id(),
        ]);

        return ApiResponse::success($empresa, 'Empresa enviada a papelera correctamente.');
    }

    public function restore(Request $request, int $empresa)
    {
        $empresa = Empresa::where('idempresa', $empresa)
            ->where('estado_trash', '0')
            ->firstOrFail();

        if (Empresa::query()->where('estado_trash', '1')->where('idempresa', '<>', $empresa->idempresa)->exists()) {
            return ApiResponse::fail('No se puede restaurar porque ya existe una empresa activa.', 422);
        }

        $empresa->update([
            'estado_trash' => '1',
            'user_trash' => null,
            'user_updated' => Auth::id(),
        ]);

        return ApiResponse::success($empresa, 'Empresa restaurada correctamente.');
    }

    public function descargarCertificado(Empresa $empresa, string $tipo)
    {
        abort_unless(in_array($tipo, ['pem', 'cer'], true), 404);

        $archivo = $tipo === 'pem'
            ? $empresa->fe_certificado_archivo_pem
            : $empresa->fe_certificado_archivo_cer;

        abort_if(blank($archivo), 404);

        $ruta = public_path($this->getCertificadoDirectory() . '/' . $archivo);
        abort_unless(file_exists($ruta), 404);

        return response()->download($ruta, $archivo);
    }

    public function personasDisponibles(Request $request)
    {
        $search = trim((string) $request->input('term', ''));
        $page = max((int) $request->input('page', 1), 1);
        $perPage = 10;

        $query = Persona::query()
            ->where('estado_trash', '1')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('descripcion', 'like', "%{$search}%")
                        ->orWhere('numero_documento', 'like', "%{$search}%")
                        ->orWhere('nombre_comercial', 'like', "%{$search}%");
                });
            })
            ->orderBy('descripcion');

        $personas = (clone $query)
            ->offset(($page - 1) * $perPage)
            ->limit($perPage + 1)
            ->get(['idpersona', 'descripcion', 'numero_documento', 'tipo_documento', 'nombre_comercial']);

        $hasMore = $personas->count() > $perPage;

        return response()->json([
            'results' => $personas
                ->take($perPage)
                ->map(fn ($persona) => [
                    'id' => $persona->idpersona,
                    'text' => trim($persona->descripcion . ($persona->numero_documento ? " ({$persona->numero_documento})" : '')),
                    'descripcion' => $persona->descripcion,
                    'numero_documento' => $persona->numero_documento,
                    'tipo_documento' => $persona->tipo_documento,
                    'nombre_comercial' => $persona->nombre_comercial,
                ]),
            'pagination' => ['more' => $hasMore],
        ]);
    }

    private function validarEmpresa(Request $request, ?int $idEmpresa = null): array
    {
        $request->merge([
            'igv' => $request->input('venta') === '' ? null : $request->input('venta'),
        ]);

        $rules = [
            'idpersona' => ['nullable', 'integer', Rule::exists('persona', 'idpersona')],
            'nombre_razon_social' => ['required', 'string', 'max:200'],
            'nombre_comercial' => ['nullable', 'string', 'max:200'],
            'domicilio_fiscal' => ['nullable', 'string', 'max:200'],
            'tipo_documento' => ['required', 'integer', Rule::exists('sunat_c06_doc_identidad', 'idsunat_c06_doc_identidad')],
            'numero_documento' => ['required', 'string', 'max:15', Rule::unique('empresa', 'numero_documento')->ignore($idEmpresa, 'idempresa')],
            'telefono1' => ['nullable', 'string', 'max:15'],
            'telefono2' => ['nullable', 'string', 'max:15'],
            'correo' => ['nullable', 'email', 'max:50'],
            'web' => ['nullable', 'string', 'max:100'],
            'web_consulta_cp' => ['nullable', 'string', 'max:100'],
            'logo_c_r' => ['nullable', 'string', 'max:1'],
            'ubigueo' => ['nullable', 'string', 'max:5'],
            'codubigueo' => ['nullable', 'string', 'max:10'],
            'distrito' => ['nullable', 'string', 'max:30'],
            'provincia' => ['nullable', 'string', 'max:30'],
            'departamento' => ['nullable', 'string', 'max:30'],
            'codigo_pais' => ['nullable', 'string', 'max:5'],
            'pie_impresion' => ['nullable', 'string', 'max:300'],
            'igv' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fe_activo' => ['nullable', Rule::in(['0', '1'])],
            'fe_ambiente' => ['nullable', Rule::in(['beta', 'production'])],
            'fe_sol_usuario' => ['nullable', 'string', 'max:80'],
            'fe_sol_clave' => ['nullable', 'string', 'max:255'],
            'fe_certificado_password' => ['nullable', 'string', 'max:255'],
            'fe_certificado_tipo' => ['nullable', Rule::in(['pem', 'p12', 'pfx'])],
            'fe_codigo_local' => ['nullable', 'string', 'max:10'],
            'venta' => ['nullable'],
            'logo_file' => ['nullable', 'image', 'max:2048'],
            'fe_certificado_file' => ['nullable', 'file', 'max:4096'],
            'logo_actual' => ['nullable', 'string'],
        ];

        $validated = $request->validate($rules);
        unset($validated['logo_file'], $validated['fe_certificado_file'], $validated['logo_actual'], $validated['venta']);

        $validated['fe_activo'] = (string) ($validated['fe_activo'] ?? '0');
        $validated['fe_ambiente'] = $validated['fe_ambiente'] ?? 'beta';
        $validated['fe_codigo_local'] = $validated['fe_codigo_local'] ?? '0000';

        $usaDemoGreenter = $validated['fe_ambiente'] === 'beta'
            && (bool) config('sunat.greenter_demo.enabled', true);

        if ($validated['fe_activo'] === '1' && ! $usaDemoGreenter) {
            $empresaActual = $idEmpresa ? Empresa::find($idEmpresa) : null;
            $errores = [];

            if (blank($validated['fe_sol_usuario'] ?? null)) {
                $errores['fe_sol_usuario'][] = 'Ingrese el usuario SOL.';
            }

            if (blank($request->input('fe_sol_clave')) && blank($empresaActual?->fe_sol_clave)) {
                $errores['fe_sol_clave'][] = 'Ingrese la clave SOL.';
            }

            if (blank($request->input('fe_certificado_password')) && blank($empresaActual?->fe_certificado_password) && in_array($validated['fe_certificado_tipo'] ?? '', ['p12', 'pfx'], true)) {
                $errores['fe_certificado_password'][] = 'Ingrese el password del certificado.';
            }

            if (! $request->hasFile('fe_certificado_file') && blank($empresaActual?->fe_certificado_archivo_base)) {
                $errores['fe_certificado_file'][] = 'Suba el certificado digital.';
            }

            if ($errores) {
                throw \Illuminate\Validation\ValidationException::withMessages($errores);
            }
        }

        return $validated;
    }

    private function resolverPersona(Request $request, array $validated, ?Persona $personaActual = null): Persona
    {
        if (! empty($validated['idpersona'])) {
            // Si el usuario vincula una persona, se asume representante legal.
            // No se debe sobrescribir su informacion con datos fiscales de la empresa.
            return Persona::findOrFail($validated['idpersona']);
        }

        $payload = [
            'tipo_persona_sunat' => 'JURIDICA',
            'tipo_documento' => $validated['tipo_documento'],
            'numero_documento' => $validated['numero_documento'],
            'descripcion' => $validated['nombre_razon_social'],
            'nombre_comercial' => $validated['nombre_comercial'] ?? null,
            'direccion' => $validated['domicilio_fiscal'] ?? null,
            'correo' => $validated['correo'] ?? null,
            'celular' => $validated['telefono1'] ?? null,
            'cod_ubigeo' => $validated['codubigueo'] ?? null,
            'estado_trash' => '1',
            'user_updated' => Auth::id(),
        ];

        $persona = Persona::query()
            ->where('numero_documento', $validated['numero_documento'])
            ->where('tipo_persona_sunat', 'JURIDICA')
            ->first();

        if (! $persona && $personaActual && $personaActual->tipo_persona_sunat === 'JURIDICA') {
            $persona = $personaActual;
        }

        if ($persona) {
            $persona->update($payload);
            return $persona;
        }

        return Persona::create([
            ...$payload,
            'user_created' => Auth::id(),
        ]);
    }

    private function guardarLogo($logo): string
    {
        $extension = $logo->getClientOriginalExtension();
        $nombreArchivo = 'empresa_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
        $directorio = $this->ensureLogoDirectoryExists();

        $logo->move($directorio, $nombreArchivo);

        return $nombreArchivo;
    }

    private function eliminarLogo(?string $nombreArchivo): void
    {
        if (blank($nombreArchivo)) {
            return;
        }

        $ruta = public_path($this->getLogoDirectory() . '/' . $nombreArchivo);

        if (file_exists($ruta)) {
            unlink($ruta);
        }
    }

    private function guardarCertificado($certificado): string
    {
        $extension = strtolower($certificado->getClientOriginalExtension());
        $nombreArchivo = 'certificado_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
        $directorio = $this->ensureCertificadoDirectoryExists();

        $certificado->move($directorio, $nombreArchivo);

        return $nombreArchivo;
    }

    private function prepararCertificadoElectronico(Request $request, array &$validated, ?Empresa $empresa = null): void
    {
        $certificado = $request->file('fe_certificado_file');
        $extension = strtolower($certificado->getClientOriginalExtension());

        if (! in_array($extension, ['pem', 'p12', 'pfx'], true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'fe_certificado_file' => 'El certificado debe ser un archivo .pem, .p12 o .pfx.',
            ]);
        }

        $contenido = File::get($certificado->getRealPath());
        $hash = hash('sha256', $contenido);

        if ($empresa && filled($empresa->fe_certificado_hash) && hash_equals((string) $empresa->fe_certificado_hash, $hash)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'fe_certificado_file' => 'Este certificado ya esta cargado en la empresa.',
            ]);
        }

        $baseNombre = 'certificado_' . date('Ymd_His') . '_' . uniqid();
        $directorio = $this->ensureCertificadoDirectoryExists();
        $validated['fe_certificado_tipo'] = $extension;
        $validated['fe_certificado_hash'] = $hash;

        if (in_array($extension, ['p12', 'pfx'], true)) {
            $password = (string) $request->input('fe_certificado_password', '');
            $certificados = $this->extraerCertificadosPkcs12($certificado->getRealPath(), $contenido, $password);

            if (! $certificados) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'fe_certificado_file' => 'No se pudo abrir el P12/PFX. Revise el archivo y el password.',
                ]);
            }

            $archivoOriginal = "{$baseNombre}.{$extension}";
            $archivoPem = "{$baseNombre}.pem";
            $archivoCer = "{$baseNombre}.cer";

            File::put($directorio . DIRECTORY_SEPARATOR . $archivoOriginal, $contenido);
            File::put($directorio . DIRECTORY_SEPARATOR . $archivoPem, trim($certificados['pem']) . PHP_EOL);
            File::put($directorio . DIRECTORY_SEPARATOR . $archivoCer, trim($certificados['cer']) . PHP_EOL);

            $validated['fe_certificado_archivo_base'] = $archivoOriginal;
            $validated['fe_certificado_archivo_pem'] = $archivoPem;
            $validated['fe_certificado_archivo_cer'] = $archivoCer;
        } else {
            $archivoPem = "{$baseNombre}.pem";
            File::put($directorio . DIRECTORY_SEPARATOR . $archivoPem, $contenido);

            $validated['fe_certificado_archivo_base'] = $archivoPem;
            $validated['fe_certificado_archivo_pem'] = $archivoPem;
            $validated['fe_certificado_archivo_cer'] = null;
        }

        if ($empresa) {
            $this->eliminarCertificado($empresa->fe_certificado_archivo_base);
            $this->eliminarCertificado($empresa->fe_certificado_archivo_pem);
            $this->eliminarCertificado($empresa->fe_certificado_archivo_cer);
        }
    }

    private function eliminarCertificado(?string $nombreArchivo): void
    {
        if (blank($nombreArchivo)) {
            return;
        }

        $ruta = public_path($this->getCertificadoDirectory() . '/' . $nombreArchivo);

        if (file_exists($ruta)) {
            unlink($ruta);
        }
    }

    private function cifrarCredencialesFe(array &$validated, ?Empresa $empresa = null): void
    {
        foreach (['fe_sol_clave', 'fe_certificado_password'] as $campo) {
            if (array_key_exists($campo, $validated) && filled($validated[$campo])) {
                $validated[$campo] = Crypt::encryptString((string) $validated[$campo]);
                continue;
            }

            if ($empresa) {
                unset($validated[$campo]);
            }
        }
    }

    private function extraerErroresOpenSsl(): string
    {
        $errores = [];
        while ($error = openssl_error_string()) {
            $errores[] = $error;
        }

        return implode(' | ', $errores);
    }

    private function extraerCertificadosPkcs12(string $rutaP12, string $contenido, string $password): ?array
    {
        $passwords = array_values(array_unique([$password, trim($password)]));
        $ultimoError = '';

        foreach ($passwords as $passwordIntento) {
            try {
                $certificate = new X509Certificate($contenido, $passwordIntento);

                return [
                    'pem' => trim((string) $certificate->export(X509ContentType::PEM)),
                    'cer' => trim((string) $certificate->export(X509ContentType::CER)),
                ];
            } catch (\Throwable $e) {
                $ultimoError = $e->getMessage();
                $ultimoError .= ' ' . $this->extraerErroresOpenSsl();
            }
        }

        if (! str_contains($ultimoError, 'unsupported')) {
            return null;
        }

        return $this->extraerCertificadosPkcs12Legacy($rutaP12, trim($password));
    }

    private function extraerCertificadosPkcs12Legacy(string $rutaP12, string $password): ?array
    {
        $openssl = $this->resolverOpenSslExecutable();
        $modules = $this->resolverOpenSslModulesPath($openssl);

        if (! $openssl || ! $modules) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'fe_certificado_file' => 'El P12 usa cifrado legacy. No se encontro openssl.exe con provider legacy para convertirlo.',
            ]);
        }

        $directorioTemporal = storage_path('app/tmp_p12_' . uniqid());
        File::ensureDirectoryExists($directorioTemporal);

        $pemPath = $directorioTemporal . DIRECTORY_SEPARATOR . 'certificado.pem';
        $cerPath = $directorioTemporal . DIRECTORY_SEPARATOR . 'certificado.cer';
        $env = [
            'OPENSSL_MODULES' => $modules,
            'RAICES_P12_PASSWORD' => $password,
        ];

        try {
            $pem = new Process([
                $openssl,
                'pkcs12',
                '-legacy',
                '-in',
                $rutaP12,
                '-out',
                $pemPath,
                '-nodes',
                '-passin',
                'env:RAICES_P12_PASSWORD',
            ], null, $env);
            $pem->run();

            $cer = new Process([
                $openssl,
                'pkcs12',
                '-legacy',
                '-in',
                $rutaP12,
                '-clcerts',
                '-nokeys',
                '-out',
                $cerPath,
                '-passin',
                'env:RAICES_P12_PASSWORD',
            ], null, $env);
            $cer->run();

            if (! $pem->isSuccessful() || ! $cer->isSuccessful() || ! File::exists($pemPath) || ! File::exists($cerPath)) {
                return null;
            }

            return [
                'pem' => File::get($pemPath),
                'cer' => File::get($cerPath),
            ];
        } finally {
            File::deleteDirectory($directorioTemporal);
        }
    }

    private function resolverOpenSslExecutable(): ?string
    {
        $candidatos = [
            'C:\\laragon\\bin\\git\\mingw64\\bin\\openssl.exe',
            'C:\\Program Files\\Git\\mingw64\\bin\\openssl.exe',
            'C:\\laragon\\bin\\apache\\httpd-2.4.62-240904-win64-VS17\\bin\\openssl.exe',
        ];

        foreach ($candidatos as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function resolverOpenSslModulesPath(?string $openssl): ?string
    {
        $candidatos = [
            'C:\\laragon\\bin\\git\\mingw64\\lib\\ossl-modules',
            'C:\\Program Files\\Git\\mingw64\\lib\\ossl-modules',
            dirname((string) $openssl) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'ossl-modules',
        ];

        foreach ($candidatos as $path) {
            $realPath = realpath($path) ?: $path;
            if (file_exists($realPath . DIRECTORY_SEPARATOR . 'legacy.dll')) {
                return $realPath;
            }
        }

        return null;
    }
}
