<?php

use App\Http\Controllers\ApiReniecSunatController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\PersonaCargoController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UbigeoDepartamentoController;
use App\Http\Controllers\UbigeoDistritoController;
use App\Http\Controllers\UbigeoProvinciaController;
use App\Http\Controllers\UsuarioPermisoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\IngresoEgresoController;
use App\Http\Controllers\IngresoEgresoCategoriaController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\LlegadaController;
use App\Http\Controllers\LlegadaPorEmpresaController;
use App\Http\Controllers\PapeleraController;
use App\Http\Controllers\SerieComprobanteController;
use App\Http\Controllers\SunatCatalogoCodigoController;
use App\Http\Controllers\SunatC01TipoComprobanteController;
use App\Http\Controllers\TrabajadorController;
use App\Http\Controllers\BancoController;
use App\Http\Controllers\CuentaBancariaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\FacturacionController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\PersonaTipoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\SalidaController;
use App\Http\Controllers\TourController;

use Illuminate\Support\Facades\Route;

Route::get('/limpiar-cache', [InicioController::class, 'limpiarCache'])->name('inicio.limpiar_cache');
Route::get('/limpiar-cache-navegador', [InicioController::class, 'limpiarCacheNavegador'])->name('inicio.limpiar_cache_navegador');

Route::middleware(['auth:sanctum',  config('jetstream.auth_session'),   'verified',])->group(function () {

  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  // ::::                                                   I N I C I O                                                                                                 ::::
  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

  Route::get('/', function () { return view('inicio'); });

  Route::get('/inicio', function () { return view('inicio'); })->name('inicio');

  Route::get('/dashboard', function () { return view('dashboard'); })->name('dashboard');

  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  // ::::                                                  CLIENTES                                                                                                 ::::
  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  Route::get('/clientes',                      [ClienteController::class, 'index'])->name('cliente.index')->middleware('permiso:clientes,ver');
  Route::get('/clientes/listar',               [ClienteController::class, 'listar'])->name('clientes.listar')->middleware('permiso:clientes,ver');
  Route::get('/clientes/buscar-por-documento', [ClienteController::class, 'buscarPorDocumento'])->name('clientes.buscar-por-documento')->middleware('permiso:clientes,ver');
  Route::post('/clientes/registrar-conyuge',    [ClienteController::class, 'registrarConyuge'])->name('clientes.registrar-conyuge')->middleware('permiso:clientes,crear');
  Route::post('/clientes/store',               [ClienteController::class, 'store'])->name('cliente.store')->middleware('permiso:clientes,crear');
  Route::get('/clientes/{cliente}/show',       [ClienteController::class, 'show'])->name('clientes.show')->middleware('permiso:clientes,ver');
  Route::put('/clientes/{cliente}/update',     [ClienteController::class, 'update'])->name('cliente.update')->middleware('permiso:clientes,editar');
  Route::delete('/clientes/{cliente}',         [ClienteController::class, 'destroy'])->name('clientes.destroy')->middleware('permiso:clientes,eliminar');
  Route::post('/clientes/{cliente}/restore',   [ClienteController::class, 'restore'])->name('clientes.restore')->middleware('permiso:clientes,editar');
  Route::get('/select2/select2distrito',       [ClienteController::class, 'getselect2distrito'])->name('clientes.getselect2distrito')->middleware('permiso:clientes|trabajadores|empresa,ver');
  Route::post('/personas/asociar-tipo',        [ClienteController::class, 'asociarTipo'])->name('personas.asociar-tipo')->middleware('permiso:clientes|trabajadores|empresa,editar');

  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  // ::::                                                   T R A B A J A D O R E S                                                                                         ::::
  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  Route::get('/trabajadores',                       [TrabajadorController::class, 'index'])->name('trabajadores.index')->middleware('permiso:trabajadores,ver');
  Route::get('/trabajadores/listar',                [TrabajadorController::class, 'listar'])->name('trabajadores.listar')->middleware('permiso:trabajadores,ver');
  Route::get('/trabajadores/buscar-por-documento',  [TrabajadorController::class, 'buscarPorDocumento'])->name('trabajadores.buscar-por-documento')->middleware('permiso:trabajadores,ver');
  Route::get('/trabajadores/cargos',                [PersonaCargoController::class, 'index'])->name('trabajadores.cargos')->middleware('permiso:trabajadores,ver');
  Route::post('/trabajadores/store',                [TrabajadorController::class, 'store'])->name('trabajadores.store')->middleware('permiso:trabajadores,crear');
  Route::get('/trabajadores/{trabajador}/show',     [TrabajadorController::class, 'show'])->name('trabajadores.show')->middleware('permiso:trabajadores,ver');
  Route::put('/trabajadores/{trabajador}/update',   [TrabajadorController::class, 'update'])->name('trabajadores.update')->middleware('permiso:trabajadores,editar');
  Route::delete('/trabajadores/{trabajador}',       [TrabajadorController::class, 'destroy'])->name('trabajadores.destroy')->middleware('permiso:trabajadores,eliminar');
  Route::post('/trabajadores/{trabajador}/restore', [TrabajadorController::class, 'restore'])->name('trabajadores.restore')->middleware('permiso:trabajadores,editar');


  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  // ::::                                                   B A N C O                                                                                                  ::::
  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
   Route::get('/catalogo', function () { return view('catalogo_general'); })->name('catalogo_general.index')->middleware('permiso:catalogo_general,ver');
  Route::get('/bancos',                  [BancoController::class, 'index'])->name('bancos.index')->middleware('permiso:catalogo_general,ver');
  Route::get('/bancos/listar',           [BancoController::class, 'listar'])->name('bancos.listar')->middleware('permiso:catalogo_general,ver');
  Route::post('/bancos/store',           [BancoController::class, 'store'])->name('bancos.store')->middleware('permiso:catalogo_general,crear');
  Route::get('/bancos/{banco}/show',     [BancoController::class, 'show'])->name('bancos.show')->middleware('permiso:catalogo_general,ver');
  Route::put('/bancos/{banco}/update',   [BancoController::class, 'update'])->name('bancos.update')->middleware('permiso:catalogo_general,editar');
  Route::delete('/bancos/{banco}',       [BancoController::class, 'destroy'])->name('bancos.destroy')->middleware('permiso:catalogo_general,eliminar');
  Route::post('/bancos/{banco}/restore', [BancoController::class, 'restore'])->name('bancos.restore')->middleware('permiso:catalogo_general,editar');

  
  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  // ::::                                                   CUENTAS BANCARIAS                                                                                                  ::::
  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

  Route::get('/cuentas-bancarias',                           [CuentaBancariaController::class, 'index'])->name('cuentas-bancarias.index')->middleware('permiso:cuentas_bancarias,ver');
  Route::get('/cuentas-bancarias/listar',                    [CuentaBancariaController::class, 'listar'])->name('cuentas-bancarias.listar')->middleware('permiso:cuentas_bancarias,ver');
  Route::post('/cuentas-bancarias/store',                    [CuentaBancariaController::class, 'store'])->name('cuentas-bancarias.store')->middleware('permiso:cuentas_bancarias,crear');
  Route::get('/cuentas-bancarias/{cuentaBancaria}/show',     [CuentaBancariaController::class, 'show'])->name('cuentas-bancarias.show')->middleware('permiso:cuentas_bancarias,ver');
  Route::put('/cuentas-bancarias/{cuentaBancaria}/update',   [CuentaBancariaController::class, 'update'])->name('cuentas-bancarias.update')->middleware('permiso:cuentas_bancarias,editar');
  Route::delete('/cuentas-bancarias/{cuentaBancaria}',       [CuentaBancariaController::class, 'destroy'])->name('cuentas-bancarias.destroy')->middleware('permiso:cuentas_bancarias,eliminar');
  Route::post('/cuentas-bancarias/{cuentaBancaria}/restore', [CuentaBancariaController::class, 'restore'])->name('cuentas-bancarias.restore')->middleware('permiso:cuentas_bancarias,editar');
  Route::get('/select2/select2banco',                        [CuentaBancariaController::class, 'getselect2banco'])->name('cuentas-bancarias.getselect2banco')->middleware('permiso:cuentas_bancarias,ver');
  Route::get('/select2/select2persona',                      [CuentaBancariaController::class, 'getselect2persona'])->name('cuentas-bancarias.getselect2persona')->middleware('permiso:cuentas_bancarias,ver');

  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  // ::::                                             I N G R E S O   Y   E G R E S O                                                                                  ::::
  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  Route::get('/ingreso-egreso', [IngresoEgresoController::class, 'index'])->name('ingreso-egreso.index')->middleware('permiso:ingreso_y_egreso,ver');
  Route::get('/ingreso-egreso/listar', [IngresoEgresoController::class, 'listar'])->name('ingreso-egreso.listar')->middleware('permiso:ingreso_y_egreso,ver');
  Route::get('/ingreso-egreso/proveedores', [ProveedorController::class, 'select'])->name('ingreso-egreso.proveedores')->middleware('permiso:ingreso_y_egreso,ver');
  Route::get('/ingreso-egreso/trabajadores', [IngresoEgresoController::class, 'trabajadoresSelect'])->name('ingreso-egreso.trabajadores')->middleware('permiso:ingreso_y_egreso,ver');
  Route::get('/ingreso-egreso/categorias', [IngresoEgresoController::class, 'categoriasSelect'])->name('ingreso-egreso.categorias')->middleware('permiso:ingreso_y_egreso,ver');
  Route::get('/proveedores/distritos', [ProveedorController::class, 'distritosSelect'])->name('proveedores.distritos')->middleware('permiso:ingreso_y_egreso,ver');
  Route::post('/ingreso-egreso/categorias', [IngresoEgresoController::class, 'storeCategoriaRapida'])->name('ingreso-egreso.categorias.store')->middleware('permiso:ingreso_y_egreso,crear');
  Route::post('/proveedores/rapido', [ProveedorController::class, 'storeRapido'])->name('proveedores.store-rapido')->middleware('permiso:ingreso_y_egreso,crear');
  Route::get('/proveedores/buscar-por-documento', [ProveedorController::class, 'buscarPorDocumento'])->name('proveedores.buscar-por-documento')->middleware('permiso:ingreso_y_egreso,ver');
  Route::post('/proveedores/asociar-tipo', [ProveedorController::class, 'asociarTipoProveedor'])->name('proveedores.asociar-tipo')->middleware('permiso:ingreso_y_egreso,crear');
  Route::get('/ingreso-egreso/tipos-comprobante', [IngresoEgresoController::class, 'tiposComprobante'])->name('ingreso-egreso.tipos-comprobante')->middleware('permiso:ingreso_y_egreso,ver');
  Route::get('/ingreso-egreso/series-comprobante', [IngresoEgresoController::class, 'seriesComprobante'])->name('ingreso-egreso.series-comprobante')->middleware('permiso:ingreso_y_egreso,ver');
  Route::post('/ingreso-egreso', [IngresoEgresoController::class, 'store'])->name('ingreso-egreso.store')->middleware('permiso:ingreso_y_egreso,crear');
  Route::get('/ingreso-egreso/{ingresoEgreso}', [IngresoEgresoController::class, 'show'])->name('ingreso-egreso.show')->middleware('permiso:ingreso_y_egreso,ver');
  Route::put('/ingreso-egreso/{ingresoEgreso}', [IngresoEgresoController::class, 'update'])->name('ingreso-egreso.update')->middleware('permiso:ingreso_y_egreso,editar');
  Route::patch('/ingreso-egreso/{ingresoEgreso}', [IngresoEgresoController::class, 'update'])->name('ingreso-egreso.update')->middleware('permiso:ingreso_y_egreso,editar');
  Route::delete('/ingreso-egreso/{ingresoEgreso}', [IngresoEgresoController::class, 'destroy'])->name('ingreso-egreso.destroy')->middleware('permiso:ingreso_y_egreso,eliminar');
  Route::post('/ingreso-egreso/{ingresoEgreso}/restore', [IngresoEgresoController::class, 'restore'])->name('ingreso-egreso.restore')->middleware('permiso:ingreso_y_egreso,editar');

  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  // ::::                                               P E R S O N A   T I P O                                                                                          ::::
  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  Route::get('/personas-tipos',                        [PersonaTipoController::class, 'index'])->name('personas_tipos.index')->middleware('permiso:catalogo_general,ver');
  Route::get('/personas-tipos/listar',                 [PersonaTipoController::class, 'listar'])->name('personas_tipos.listar')->middleware('permiso:catalogo_general,ver');
  Route::post('/personas-tipos/store',                 [PersonaTipoController::class, 'store'])->name('personas_tipos.store')->middleware('permiso:catalogo_general,crear');
  Route::get('/personas-tipos/{personaTipo}/show',     [PersonaTipoController::class, 'show'])->name('personas_tipos.show')->middleware('permiso:catalogo_general,ver');
  Route::put('/personas-tipos/{personaTipo}/update',   [PersonaTipoController::class, 'update'])->name('personas_tipos.update')->middleware('permiso:catalogo_general,editar');
  Route::delete('/personas-tipos/{personaTipo}',       [PersonaTipoController::class, 'destroy'])->name('personas_tipos.destroy')->middleware('permiso:catalogo_general,eliminar');
  Route::post('/personas-tipos/{personaTipo}/restore', [PersonaTipoController::class, 'restore'])->name('personas_tipos.restore')->middleware('permiso:catalogo_general,editar');

  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  // ::::                                               P E R S O N A   C A R G O                                                                                        ::::
  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  Route::get('/persona-cargos/listar',                 [PersonaCargoController::class, 'listar'])->name('persona-cargos.listar')->middleware('permiso:catalogo_general,ver');
  Route::post('/persona-cargos/store',                 [PersonaCargoController::class, 'store'])->name('persona-cargos.store')->middleware('permiso:catalogo_general,crear');
  Route::get('/persona-cargos/{personaCargo}/show',    [PersonaCargoController::class, 'show'])->name('persona-cargos.show')->middleware('permiso:catalogo_general,ver');
  Route::put('/persona-cargos/{personaCargo}/update',  [PersonaCargoController::class, 'update'])->name('persona-cargos.update')->middleware('permiso:catalogo_general,editar');
  Route::delete('/persona-cargos/{personaCargo}',      [PersonaCargoController::class, 'destroy'])->name('persona-cargos.destroy')->middleware('permiso:catalogo_general,eliminar');
  Route::post('/persona-cargos/{personaCargo}/restore', [PersonaCargoController::class, 'restore'])->name('persona-cargos.restore')->middleware('permiso:catalogo_general,editar');

  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  // ::::                                      I N G R E S O   E G R E S O   C A T E G O R I A                                                                         ::::
  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  Route::get('/ingreso-egreso-categorias/listar', [IngresoEgresoCategoriaController::class, 'listar'])->name('ingreso-egreso-categorias.listar')->middleware('permiso:catalogo_general,ver');
  Route::post('/ingreso-egreso-categorias/store', [IngresoEgresoCategoriaController::class, 'store'])->name('ingreso-egreso-categorias.store')->middleware('permiso:catalogo_general,crear');
  Route::get('/ingreso-egreso-categorias/{ingresoEgresoCategoria}/show', [IngresoEgresoCategoriaController::class, 'show'])->name('ingreso-egreso-categorias.show')->middleware('permiso:catalogo_general,ver');
  Route::put('/ingreso-egreso-categorias/{ingresoEgresoCategoria}/update', [IngresoEgresoCategoriaController::class, 'update'])->name('ingreso-egreso-categorias.update')->middleware('permiso:catalogo_general,editar');
  Route::delete('/ingreso-egreso-categorias/{ingresoEgresoCategoria}', [IngresoEgresoCategoriaController::class, 'destroy'])->name('ingreso-egreso-categorias.destroy')->middleware('permiso:catalogo_general,eliminar');
  Route::post('/ingreso-egreso-categorias/{ingresoEgresoCategoria}/restore', [IngresoEgresoCategoriaController::class, 'restore'])->name('ingreso-egreso-categorias.restore')->middleware('permiso:catalogo_general,editar');

  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  // ::::                                                   U B I G E O                                                                                                  ::::
  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  Route::get('/ubigeo-departamentos/listar',                    [UbigeoDepartamentoController::class, 'listar'])->name('ubigeo-departamentos.listar')->middleware('permiso:catalogo_general,ver');
  Route::post('/ubigeo-departamentos/store',                    [UbigeoDepartamentoController::class, 'store'])->name('ubigeo-departamentos.store')->middleware('permiso:catalogo_general,crear');
  Route::get('/ubigeo-departamentos/{ubigeoDepartamento}/show', [UbigeoDepartamentoController::class, 'show'])->name('ubigeo-departamentos.show')->middleware('permiso:catalogo_general,ver');
  Route::put('/ubigeo-departamentos/{ubigeoDepartamento}/update', [UbigeoDepartamentoController::class, 'update'])->name('ubigeo-departamentos.update')->middleware('permiso:catalogo_general,editar');
  Route::delete('/ubigeo-departamentos/{ubigeoDepartamento}',   [UbigeoDepartamentoController::class, 'destroy'])->name('ubigeo-departamentos.destroy')->middleware('permiso:catalogo_general,eliminar');
  Route::post('/ubigeo-departamentos/{ubigeoDepartamento}/restore', [UbigeoDepartamentoController::class, 'restore'])->name('ubigeo-departamentos.restore')->middleware('permiso:catalogo_general,editar');

  Route::get('/ubigeo-provincias/listar',                    [UbigeoProvinciaController::class, 'listar'])->name('ubigeo-provincias.listar')->middleware('permiso:catalogo_general,ver');
  Route::post('/ubigeo-provincias/store',                    [UbigeoProvinciaController::class, 'store'])->name('ubigeo-provincias.store')->middleware('permiso:catalogo_general,crear');
  Route::get('/ubigeo-provincias/{ubigeoProvincia}/show',    [UbigeoProvinciaController::class, 'show'])->name('ubigeo-provincias.show')->middleware('permiso:catalogo_general,ver');
  Route::put('/ubigeo-provincias/{ubigeoProvincia}/update',  [UbigeoProvinciaController::class, 'update'])->name('ubigeo-provincias.update')->middleware('permiso:catalogo_general,editar');
  Route::delete('/ubigeo-provincias/{ubigeoProvincia}',      [UbigeoProvinciaController::class, 'destroy'])->name('ubigeo-provincias.destroy')->middleware('permiso:catalogo_general,eliminar');
  Route::post('/ubigeo-provincias/{ubigeoProvincia}/restore', [UbigeoProvinciaController::class, 'restore'])->name('ubigeo-provincias.restore')->middleware('permiso:catalogo_general,editar');

  Route::get('/ubigeo-distritos/listar',                    [UbigeoDistritoController::class, 'listar'])->name('ubigeo-distritos.listar')->middleware('permiso:catalogo_general,ver');
  Route::post('/ubigeo-distritos/store',                    [UbigeoDistritoController::class, 'store'])->name('ubigeo-distritos.store')->middleware('permiso:catalogo_general,crear');
  Route::get('/ubigeo-distritos/{ubigeoDistrito}/show',     [UbigeoDistritoController::class, 'show'])->name('ubigeo-distritos.show')->middleware('permiso:catalogo_general,ver');
  Route::put('/ubigeo-distritos/{ubigeoDistrito}/update',   [UbigeoDistritoController::class, 'update'])->name('ubigeo-distritos.update')->middleware('permiso:catalogo_general,editar');
  Route::delete('/ubigeo-distritos/{ubigeoDistrito}',       [UbigeoDistritoController::class, 'destroy'])->name('ubigeo-distritos.destroy')->middleware('permiso:catalogo_general,eliminar');
  Route::post('/ubigeo-distritos/{ubigeoDistrito}/restore', [UbigeoDistritoController::class, 'restore'])->name('ubigeo-distritos.restore')->middleware('permiso:catalogo_general,editar');

  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  // ::::                                                   P A P E L E R A                                                                                                  ::::
  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  Route::get('/papelera', [PapeleraController::class, 'index'])->name('papelera.index')->middleware('permiso:papelera,ver');
  Route::get('/papelera/listar', [PapeleraController::class, 'listar'])->name('papelera.listar')->middleware('permiso:papelera,ver');
  Route::post('/papelera/{modulo}/{id}/restore', [PapeleraController::class, 'restaurar'])->name('papelera.restore')->middleware('permiso:papelera,editar');

  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  // ::::                                                   E M P R E S A                                                                                                    ::::
  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  Route::get('/empresa', [EmpresaController::class, 'index'])->name('empresa.index')->middleware('permiso:empresa,ver');
  Route::get('/empresa/listar', [EmpresaController::class, 'listar'])->name('empresa.listar')->middleware('permiso:empresa,ver');
  Route::get('/empresa/personas-disponibles', [EmpresaController::class, 'personasDisponibles'])->name('empresa.personas-disponibles')->middleware('permiso:empresa,ver');
  Route::post('/empresa/store', [EmpresaController::class, 'store'])->name('empresa.store')->middleware('permiso:empresa,crear');
  Route::get('/empresa/{empresa}/show', [EmpresaController::class, 'show'])->name('empresa.show')->middleware('permiso:empresa,ver');
  Route::get('/empresa/{empresa}/certificado/{tipo}', [EmpresaController::class, 'descargarCertificado'])->name('empresa.certificado.descargar')->middleware('permiso:empresa,ver');
  Route::put('/empresa/{empresa}/update', [EmpresaController::class, 'update'])->name('empresa.update')->middleware('permiso:empresa,editar');
  Route::delete('/empresa/{empresa}', [EmpresaController::class, 'destroy'])->name('empresa.destroy')->middleware('permiso:empresa,eliminar');
  Route::post('/empresa/{empresa}/restore', [EmpresaController::class, 'restore'])->name('empresa.restore')->middleware('permiso:empresa,editar');

  
  Route::get('/productos',                         [ProductoController::class, 'index'])->name('producto.index')->middleware('permiso:productos,ver');
  Route::get('/productos/listar',                  [ProductoController::class, 'listar'])->name('productos.listar')->middleware('permiso:productos,ver');
  Route::get('/productos/catalogos',               [ProductoController::class, 'catalogos'])->name('productos.catalogos')->middleware('permiso:productos,ver');
  Route::post('/productos/store',                  [ProductoController::class, 'store'])->name('productos.store')->middleware('permiso:productos,crear');
  Route::get('/productos/{producto}/show',         [ProductoController::class, 'show'])->name('productos.show')->middleware('permiso:productos,ver');
  Route::put('/productos/{producto}/update',       [ProductoController::class, 'update'])->name('productos.update')->middleware('permiso:productos,editar');
  Route::delete('/productos/{producto}',           [ProductoController::class, 'destroy'])->name('productos.destroy')->middleware('permiso:productos,eliminar');
  Route::post('/productos/{producto}/restore',     [ProductoController::class, 'restore'])->name('productos.restore')->middleware('permiso:productos,editar');

  Route::get('/hoteles', [HotelController::class, 'index'])->name('hoteles.index')->middleware('permiso:hoteles,ver');
  Route::get('/hoteles/listar', [HotelController::class, 'listar'])->name('hoteles.listar')->middleware('permiso:hoteles,ver');
  Route::get('/hoteles/catalogos', [HotelController::class, 'catalogos'])->name('hoteles.catalogos')->middleware('permiso:hoteles,ver');
  Route::get('/hoteles/distritos', [HotelController::class, 'distritos'])->name('hoteles.distritos')->middleware('permiso:hoteles,ver');
  Route::get('/hoteles/personas', [HotelController::class, 'personas'])->name('hoteles.personas')->middleware('permiso:hoteles,ver');
  Route::post('/hoteles/personas/store', [HotelController::class, 'storePersona'])->name('hoteles.personas.store')->middleware('permiso:hoteles,crear');
  Route::post('/hoteles/personas/buscar-documento', [HotelController::class, 'buscarPersonaHotel'])->name('hoteles.personas.buscar-documento')->middleware('permiso:hoteles,ver');
  Route::put('/hoteles/personas/{persona}', [HotelController::class, 'updatePersona'])->name('hoteles.personas.update')->middleware('permiso:hoteles,editar');
  Route::post('/hoteles/store', [HotelController::class, 'store'])->name('hoteles.store')->middleware('permiso:hoteles,crear');
  Route::get('/hoteles/{hotel}/show', [HotelController::class, 'show'])->name('hoteles.show')->middleware('permiso:hoteles,ver');
  Route::put('/hoteles/{hotel}/update', [HotelController::class, 'update'])->name('hoteles.update')->middleware('permiso:hoteles,editar');
  Route::delete('/hoteles/{hotel}', [HotelController::class, 'destroy'])->name('hoteles.destroy')->middleware('permiso:hoteles,eliminar');
  Route::post('/hoteles/tipos/store', [HotelController::class, 'storeTipo'])->name('hoteles.tipos.store')->middleware('permiso:hoteles,crear');
  Route::get('/hoteles/tipos/{tipo}/show', [HotelController::class, 'showTipo'])->name('hoteles.tipos.show')->middleware('permiso:hoteles,ver');
  Route::put('/hoteles/tipos/{tipo}', [HotelController::class, 'updateTipo'])->name('hoteles.tipos.update')->middleware('permiso:hoteles,editar');
  Route::delete('/hoteles/tipos/{tipo}', [HotelController::class, 'destroyTipo'])->name('hoteles.tipos.destroy')->middleware('permiso:hoteles,eliminar');
  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  // ::::                                           A E R O L I N E A S   Y   A G E N C I A S                                                                               ::::
  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  Route::get('/aerolineas', [LlegadaPorEmpresaController::class, 'aerolineas'])->name('aerolineas.index')->middleware('permiso:aerolineas,ver');
  Route::get('/aerolineas/listar', [LlegadaPorEmpresaController::class, 'listarAerolineas'])->name('aerolineas.listar')->middleware('permiso:aerolineas,ver');
  Route::post('/aerolineas/store', [LlegadaPorEmpresaController::class, 'storeAerolinea'])->name('aerolineas.store')->middleware('permiso:aerolineas,crear');
  Route::get('/aerolineas/{empresa}/show', [LlegadaPorEmpresaController::class, 'showAerolinea'])->name('aerolineas.show')->middleware('permiso:aerolineas,ver');
  Route::put('/aerolineas/{empresa}/update', [LlegadaPorEmpresaController::class, 'updateAerolinea'])->name('aerolineas.update')->middleware('permiso:aerolineas,editar');
  Route::delete('/aerolineas/{empresa}', [LlegadaPorEmpresaController::class, 'destroyAerolinea'])->name('aerolineas.destroy')->middleware('permiso:aerolineas,eliminar');
  Route::post('/aerolineas/{empresa}/restore', [LlegadaPorEmpresaController::class, 'restoreAerolinea'])->name('aerolineas.restore')->middleware('permiso:aerolineas,editar');

  Route::get('/agencias', [LlegadaPorEmpresaController::class, 'agencias'])->name('agencias.index')->middleware('permiso:agencias,ver');
  Route::get('/agencias/listar', [LlegadaPorEmpresaController::class, 'listarAgencias'])->name('agencias.listar')->middleware('permiso:agencias,ver');
  Route::post('/agencias/store', [LlegadaPorEmpresaController::class, 'storeAgencia'])->name('agencias.store')->middleware('permiso:agencias,crear');
  Route::get('/agencias/{empresa}/show', [LlegadaPorEmpresaController::class, 'showAgencia'])->name('agencias.show')->middleware('permiso:agencias,ver');
  Route::put('/agencias/{empresa}/update', [LlegadaPorEmpresaController::class, 'updateAgencia'])->name('agencias.update')->middleware('permiso:agencias,editar');
  Route::delete('/agencias/{empresa}', [LlegadaPorEmpresaController::class, 'destroyAgencia'])->name('agencias.destroy')->middleware('permiso:agencias,eliminar');
  Route::post('/agencias/{empresa}/restore', [LlegadaPorEmpresaController::class, 'restoreAgencia'])->name('agencias.restore')->middleware('permiso:agencias,editar');
  Route::get('/tours',                             [TourController::class, 'index'])->name('tours.index')->middleware('permiso:tours,ver');
  Route::get('/tours/listar',                      [TourController::class, 'listar'])->name('tours.listar')->middleware('permiso:tours,ver');
  Route::get('/tours/catalogos',                   [TourController::class, 'catalogos'])->name('tours.catalogos')->middleware('permiso:tours,ver');
  Route::get('/tours/{tour}/show',                 [TourController::class, 'show'])->name('tours.show')->middleware('permiso:tours,ver');
  Route::post('/tours/store',                      [TourController::class, 'store'])->name('tours.store')->middleware('permiso:tours,crear');
  Route::put('/tours/{tour}/update',               [TourController::class, 'update'])->name('tours.update')->middleware('permiso:tours,editar');
  Route::delete('/tours/{tour}',                   [TourController::class, 'destroy'])->name('tours.destroy')->middleware('permiso:tours,eliminar');
  Route::post('/tours/{tour}/restore',             [TourController::class, 'restore'])->name('tours.restore')->middleware('permiso:tours,editar');
  Route::post('/tours/turnos/store',                [TourController::class, 'storeTurno'])->name('tours.turnos.store')->middleware('permiso:tours,crear');
  Route::get('/tours/distritos',                   [TourController::class, 'distritos'])->name('tours.distritos')->middleware('permiso:tours,ver');

  Route::get('/categorias/listar',                 [CategoriaController::class, 'listar'])->name('categorias.listar')->middleware('permiso:catalogo_general,ver');
  Route::post('/categorias/store',                 [CategoriaController::class, 'store'])->name('categorias.store')->middleware('permiso:catalogo_general,crear');
  Route::get('/categorias/{categoria}/show',       [CategoriaController::class, 'show'])->name('categorias.show')->middleware('permiso:catalogo_general,ver');
  Route::put('/categorias/{categoria}/update',     [CategoriaController::class, 'update'])->name('categorias.update')->middleware('permiso:catalogo_general,editar');
  Route::delete('/categorias/{categoria}',         [CategoriaController::class, 'destroy'])->name('categorias.destroy')->middleware('permiso:catalogo_general,eliminar');
  Route::post('/categorias/{categoria}/restore',   [CategoriaController::class, 'restore'])->name('categorias.restore')->middleware('permiso:catalogo_general,editar');

  Route::get('/marcas/listar',                     [MarcaController::class, 'listar'])->name('marcas.listar')->middleware('permiso:catalogo_general,ver');
  Route::post('/marcas/store',                     [MarcaController::class, 'store'])->name('marcas.store')->middleware('permiso:catalogo_general,crear');
  Route::get('/marcas/{marca}/show',               [MarcaController::class, 'show'])->name('marcas.show')->middleware('permiso:catalogo_general,ver');
  Route::put('/marcas/{marca}/update',             [MarcaController::class, 'update'])->name('marcas.update')->middleware('permiso:catalogo_general,editar');
  Route::delete('/marcas/{marca}',                 [MarcaController::class, 'destroy'])->name('marcas.destroy')->middleware('permiso:catalogo_general,eliminar');
  Route::post('/marcas/{marca}/restore',           [MarcaController::class, 'restore'])->name('marcas.restore')->middleware('permiso:catalogo_general,editar');

  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  // ::::                                                  F A C T U R A C I O N                                                                                              ::::
  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  Route::get('/facturacion',                            [FacturacionController::class, 'index'])->name('facturacion.index')->middleware('permiso:facturacion,ver');
  Route::get('/facturacion/clientes',                   [FacturacionController::class, 'clientes'])->name('facturacion.clientes')->middleware('permiso:facturacion,ver');
  Route::get('/facturacion/clientes-factura',           [FacturacionController::class, 'clientesFactura'])->name('facturacion.clientes_factura')->middleware('permiso:facturacion,ver');
  Route::get('/facturacion/productos',                  [FacturacionController::class, 'productos'])->name('facturacion.productos')->middleware('permiso:facturacion,ver');
  Route::get('/facturacion/catalogos-creacion',         [FacturacionController::class, 'catalogosCreacion'])->name('facturacion.catalogos_creacion')->middleware('permiso:facturacion,ver');
  Route::get('/facturacion/filtros',                    [FacturacionController::class, 'filtros'])->name('facturacion.filtros')->middleware('permiso:facturacion,ver');
  Route::get('/facturacion/listar',                     [FacturacionController::class, 'listar'])->name('facturacion.listar')->middleware('permiso:facturacion,ver');
  Route::get('/facturacion/{documento}/impresion/{formato}', [FacturacionController::class, 'imprimirDocumento'])->name('facturacion.impresion')->middleware('permiso:facturacion,ver');
  Route::get('/facturacion/{documento}/detalle',        [FacturacionController::class, 'detalle'])->name('facturacion.detalle')->middleware('permiso:facturacion,ver');
  Route::post('/facturacion/factura',                   [FacturacionController::class, 'storeFactura'])->name('facturacion.factura.store')->middleware('permiso:facturacion,crear');
  Route::put('/facturacion/{documento}/comprobante',    [FacturacionController::class, 'actualizarComprobante'])->name('facturacion.comprobante.update')->middleware('permiso:facturacion,editar');
  Route::get('/facturacion/{documento}/sunat/{tipo}/descargar', [FacturacionController::class, 'descargarArchivoSunat'])->name('facturacion.sunat.descargar')->middleware('permiso:facturacion,ver');
  Route::post('/facturacion/{documento}/enviar-sunat',  [FacturacionController::class, 'enviarSunat'])->name('facturacion.enviar_sunat')->middleware('permiso:facturacion,editar');
  Route::post('/facturacion/{documento}/anular-sunat',  [FacturacionController::class, 'anularSunat'])->name('facturacion.anular_sunat')->middleware('permiso:facturacion,editar');
  Route::put('/facturacion/{documento}/nota-credito',   [FacturacionController::class, 'actualizarNotaCredito'])->name('facturacion.nota_credito.update')->middleware('permiso:facturacion,editar');
  Route::post('/facturacion/{documento}/desactivar-ticket', [FacturacionController::class, 'desactivarTicket'])->name('facturacion.desactivar_ticket')->middleware('permiso:facturacion,editar');

  // ::::::::::::::::::::::::::::::::::::::::::::::::::::::    R E S E R V A S     ::::::::::::::::::::::::::::::::::::::::::::::::::::::
  Route::get('/reservas', [ReservaController::class, 'index'])->name('reservas.index')->middleware('permiso:reserva,ver');
  Route::get('/reservas/listar', [ReservaController::class, 'listar'])->name('reservas.listar')->middleware('permiso:reserva,ver');
  Route::get('/reservas/codigo', [ReservaController::class, 'codigo'])->name('reservas.codigo')->middleware('permiso:reserva,crear');
  Route::get('/reservas/catalogos/clientes', [ReservaController::class, 'clientes'])->name('reservas.catalogos.clientes')->middleware('permiso:reserva,ver|crear|editar');
  Route::get('/reservas/catalogos/origenes', [ReservaController::class, 'origenes'])->name('reservas.catalogos.origenes')->middleware('permiso:reserva,ver|crear|editar');
  Route::get('/reservas/catalogos/llegada-tipos', [ReservaController::class, 'llegadaTipos'])->name('reservas.catalogos.llegada-tipos')->middleware('permiso:reserva,ver|crear|editar');
  Route::get('/reservas/catalogos/llegada-empresas', [ReservaController::class, 'llegadaEmpresas'])->name('reservas.catalogos.llegada-empresas')->middleware('permiso:reserva,ver|crear|editar');
  Route::get('/reservas/catalogos/trabajadores', [ReservaController::class, 'trabajadores'])->name('reservas.catalogos.trabajadores')->middleware('permiso:reserva,ver|crear|editar');
  Route::get('/reservas/catalogos/hoteles', [ReservaController::class, 'hoteles'])->name('reservas.catalogos.hoteles')->middleware('permiso:reserva,ver|crear|editar');
  Route::get('/reservas/catalogos/habitaciones', [ReservaController::class, 'habitaciones'])->name('reservas.catalogos.habitaciones')->middleware('permiso:reserva,ver|crear|editar');
  Route::get('/reservas/catalogos/distritos', [ReservaController::class, 'distritos'])->name('reservas.catalogos.distritos')->middleware('permiso:reserva,ver|crear|editar');
  Route::get('/reservas/catalogos/distritos/{distrito}', [ReservaController::class, 'distrito'])->name('reservas.catalogos.distritos.show')->middleware('permiso:reserva,ver|crear|editar');
  Route::get('/reservas/catalogos/bancos', [ReservaController::class, 'bancos'])->name('reservas.catalogos.bancos')->middleware('permiso:reserva,ver|crear|editar');
  Route::get('/reservas/catalogos/series-comprobante', [ReservaController::class, 'seriesComprobante'])->name('reservas.catalogos.series-comprobante')->middleware('permiso:reserva,ver|crear|editar');
  Route::get('/reservas/tours/buscar', [ReservaController::class, 'buscarToursCatalogo'])->name('reservas.tours.buscar')->middleware('permiso:reserva,ver|crear|editar');
  Route::get('/reservas/tours/{tour}', [ReservaController::class, 'tour'])->name('reservas.tours.show')->middleware('permiso:reserva,ver|crear|editar');
  Route::get('/reservas/tours/{tour}/detalle', [ReservaController::class, 'detalleTour'])->name('reservas.tours.detalle')->middleware('permiso:reserva,ver|crear|editar');
  Route::get('/reservas/habitaciones/{habitacion}', [ReservaController::class, 'habitacion'])->name('reservas.habitaciones.show')->middleware('permiso:reserva,ver|crear|editar');
  Route::get('/reservas/validar-codigo', [ReservaController::class, 'validarCodigoReserva'])->name('reservas.validar-codigo')->middleware('permiso:reserva,crear|editar');
  Route::post('/reservas/clientes/store', [ReservaController::class, 'storeClienteRapido'])->name('reservas.clientes.store')->middleware('permiso:reserva,crear|editar');
  Route::patch('/reservas/clientes/{cliente}/numero-documento', [ReservaController::class, 'updateClienteNumeroDocumento'])->name('reservas.clientes.numero-documento')->middleware('permiso:reserva,editar');
  Route::post('/reservas/pagos/store', [ReservaController::class, 'storePago'])->name('reservas.pagos.store')->middleware('permiso:reserva,crear|editar');
  Route::get('/reservas/pagos/{documento}', [ReservaController::class, 'showPago'])->name('reservas.pagos.show')->middleware('permiso:reserva,ver|editar');
  Route::put('/reservas/pagos/{documento}', [ReservaController::class, 'updatePago'])->name('reservas.pagos.update')->middleware('permiso:reserva,editar');
  Route::delete('/reservas/pagos/{documento}', [ReservaController::class, 'destroyPago'])->name('reservas.pagos.destroy')->middleware('permiso:reserva,eliminar');
  Route::post('/reservas/store', [ReservaController::class, 'store'])->name('reservas.store')->middleware('permiso:reserva,crear');
  Route::get('/reservas/{reserva}/detalle-comprobante', [ReservaController::class, 'detalleComprobante'])->name('reservas.detalle-comprobante')->middleware('permiso:reserva,ver|crear|editar');
  Route::get('/reservas/{reserva}/ficha', [ReservaController::class, 'ficha'])->name('reservas.ficha')->middleware('permiso:reserva,ver');
  Route::get('/reservas/{reserva}/detalle', [ReservaController::class, 'detalle'])->name('reservas.detalle')->middleware('permiso:reserva,ver');
  Route::get('/reservas/{reserva}/show', [ReservaController::class, 'show'])->name('reservas.show')->middleware('permiso:reserva,ver');
  Route::put('/reservas/{reserva}/update', [ReservaController::class, 'update'])->name('reservas.update')->middleware('permiso:reserva,editar');
  Route::delete('/reservas/{reserva}', [ReservaController::class, 'destroy'])->name('reservas.destroy')->middleware('permiso:reserva,eliminar');
  // ::::::::::::::::::::::::::::::::::::::::::::::::::::::    L L E G A D A S     ::::::::::::::::::::::::::::::::::::::::::::::::::::::
  Route::get('/llegadas', [LlegadaController::class, 'index'])->name('llegadas.index')->middleware('permiso:llegada,ver');
  Route::get('/llegadas/listar', [LlegadaController::class, 'listar'])->name('llegadas.listar')->middleware('permiso:llegada,ver');
  Route::patch('/llegadas/{reserva}/recojo', [LlegadaController::class, 'asignarRecojo'])->name('llegadas.recojo')->middleware('permiso:llegada,editar');
  // ::::::::::::::::::::::::::::::::::::::::::::::::::::::    S A L I D A S     ::::::::::::::::::::::::::::::::::::::::::::::::::::::
  Route::get('/salidas', [SalidaController::class, 'index'])->name('salidas.index')->middleware('permiso:salida,ver');
  Route::get('/salidas/listar', [SalidaController::class, 'listar'])->name('salidas.listar')->middleware('permiso:salida,ver');

  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  // ::::                                                   S U N A T                                                                                                      ::::
  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  Route::get('/sunat/catalogos-codigo', [SunatCatalogoCodigoController::class, 'index'])->name('sunat.catalogos-codigo.index')->middleware('permiso:catalogos_de_codigo,ver');
  Route::get('/sunat/tipos-comprobantes', [SunatC01TipoComprobanteController::class, 'index'])->name('sunat.tipos-comprobantes.index')->middleware('permiso:tipo_de_comprobantes,ver');
  Route::get('/sunat/tipos-comprobantes/listar', [SunatC01TipoComprobanteController::class, 'listar'])->name('sunat.tipos-comprobantes.listar')->middleware('permiso:tipo_de_comprobantes,ver');
  Route::get('/sunat/series-comprobantes', [SerieComprobanteController::class, 'index'])->name('sunat.series-comprobantes.index')->middleware('permiso:series_de_comprobantes,ver');
  Route::get('/sunat/series-comprobantes/listar', [SerieComprobanteController::class, 'listar'])->name('sunat.series-comprobantes.listar')->middleware('permiso:series_de_comprobantes,ver');
  Route::get('/sunat/series-comprobantes/tipos', [SerieComprobanteController::class, 'tiposComprobantes'])->name('sunat.series-comprobantes.tipos')->middleware('permiso:series_de_comprobantes,ver');
  Route::get('/sunat/series-comprobantes/validar-serie', [SerieComprobanteController::class, 'validarSerie'])->name('sunat.series-comprobantes.validar-serie')->middleware('permiso:series_de_comprobantes,crear|editar');
  Route::post('/sunat/series-comprobantes', [SerieComprobanteController::class, 'store'])->name('sunat.series-comprobantes.store')->middleware('permiso:series_de_comprobantes,crear');
  Route::get('/sunat/series-comprobantes/{serieComprobante}', [SerieComprobanteController::class, 'show'])->name('sunat.series-comprobantes.show')->middleware('permiso:series_de_comprobantes,ver');
  Route::put('/sunat/series-comprobantes/{serieComprobante}', [SerieComprobanteController::class, 'update'])->name('sunat.series-comprobantes.update')->middleware('permiso:series_de_comprobantes,editar');
  Route::delete('/sunat/series-comprobantes/{serieComprobante}', [SerieComprobanteController::class, 'destroy'])->name('sunat.series-comprobantes.destroy')->middleware('permiso:series_de_comprobantes,eliminar');
  Route::post('/sunat/series-comprobantes/{serieComprobante}/restore', [SerieComprobanteController::class, 'restore'])->name('sunat.series-comprobantes.restore')->middleware('permiso:series_de_comprobantes,editar');
  

  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  // ::::                                                   U S U A R I O S                                                                                                 ::::
  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::


  Route::post('/usuario/confirmar-actualizacion', function () {
    request()->user()->forceFill(['user_update_sistema' => '1', ])->save();

    return response()->json([
      'success' => true,
    ]);
  })->name('user.update-system-confirmed');

  Route::get('mi-perfil', [UserController::class, 'perfil'])->name('perfil.seguridad');
  Route::get('usuarios', [UserController::class, 'vista'])->name('usuarios.index')->middleware('permiso:usuarios_del_sistema,ver');
  Route::get('usuarios/listar', [UserController::class, 'listar'])->name('usuarios.listar')->middleware('permiso:usuarios_del_sistema,ver');
  Route::get('usuarios/personas-disponibles', [UserController::class, 'personasDisponibles'])->name('usuarios.personas-disponibles')->middleware('permiso:usuarios_del_sistema,crear|editar');
  Route::get('usuarios/series-disponibles', [UserController::class, 'seriesDisponibles'])->name('usuarios.series-disponibles')->middleware('permiso:usuarios_del_sistema,ver');
  Route::get('usuarios/validar-email', [UserController::class, 'validarEmail'])->name('usuarios.validar-email')->middleware('permiso:usuarios_del_sistema,crear|editar');
  Route::get('usuarios/{user}/sesiones', [UserController::class, 'sesiones'])->name('usuarios.sesiones')->middleware('permiso:usuarios_del_sistema,ver');
  Route::delete('usuarios/{user}/sesiones', [UserController::class, 'cerrarSesiones'])->name('usuarios.sesiones.cerrar-todas')->middleware('permiso:usuarios_del_sistema,editar');
  Route::delete('usuarios/{user}/sesiones/{sessionId}', [UserController::class, 'cerrarSesion'])->name('usuarios.sesiones.cerrar')->middleware('permiso:usuarios_del_sistema,editar');
  Route::delete('usuarios/permanente', [UserController::class, 'destroyPermanenteMasivo'])->name('usuarios.destroy-permanente-masivo')->middleware('permiso:usuarios_del_sistema,eliminar');
  Route::delete('usuarios/{user}/permanente', [UserController::class, 'destroyPermanente'])->name('usuarios.destroy-permanente')->middleware('permiso:usuarios_del_sistema,eliminar');
  Route::get('usuarios/{user}', [UserController::class, 'show'])->name('usuarios.show')->middleware('permiso:usuarios_del_sistema,ver');
  Route::post('usuarios', [UserController::class, 'store'])->name('usuarios.store')->middleware('permiso:usuarios_del_sistema,crear');
  Route::put('usuarios/{user}', [UserController::class, 'update'])->name('usuarios.update')->middleware('permiso:usuarios_del_sistema,editar');
  Route::patch('usuarios/{user}', [UserController::class, 'update'])->name('usuarios.update')->middleware('permiso:usuarios_del_sistema,editar');
  Route::delete('usuarios/{user}', [UserController::class, 'destroy'])->name('usuarios.destroy')->middleware('permiso:usuarios_del_sistema,eliminar');

  Route::apiResource('personas', PersonaController::class)
    ->parameters(['personas' => 'persona'])
    ->middleware('permiso:usuarios_del_sistema,ver');

  Route::get('permisos', [PermisoController::class, 'index'])->name('permisos.index')->middleware('permiso:usuarios_del_sistema,ver');
  Route::post('permisos', [PermisoController::class, 'store'])->name('permisos.store')->middleware('permiso:usuarios_del_sistema,crear');
  Route::get('permisos/{permiso}', [PermisoController::class, 'show'])->name('permisos.show')->middleware('permiso:usuarios_del_sistema,ver');
  Route::put('permisos/{permiso}', [PermisoController::class, 'update'])->name('permisos.update')->middleware('permiso:usuarios_del_sistema,editar');
  Route::patch('permisos/{permiso}', [PermisoController::class, 'update'])->name('permisos.update')->middleware('permiso:usuarios_del_sistema,editar');
  Route::delete('permisos/{permiso}', [PermisoController::class, 'destroy'])->name('permisos.destroy')->middleware('permiso:usuarios_del_sistema,eliminar');

  Route::get('usuario-permisos', [UsuarioPermisoController::class, 'index'])->name('usuario-permisos.index')->middleware('permiso:usuarios_del_sistema,ver');
  Route::post('usuario-permisos', [UsuarioPermisoController::class, 'store'])->name('usuario-permisos.store')->middleware('permiso:usuarios_del_sistema,editar');
  Route::get('usuario-permisos/{usuarioPermiso}', [UsuarioPermisoController::class, 'show'])->name('usuario-permisos.show')->middleware('permiso:usuarios_del_sistema,ver');
  Route::put('usuario-permisos/{usuarioPermiso}', [UsuarioPermisoController::class, 'update'])->name('usuario-permisos.update')->middleware('permiso:usuarios_del_sistema,editar');
  Route::patch('usuario-permisos/{usuarioPermiso}', [UsuarioPermisoController::class, 'update'])->name('usuario-permisos.update')->middleware('permiso:usuarios_del_sistema,editar');
  Route::delete('usuario-permisos/{usuarioPermiso}', [UsuarioPermisoController::class, 'destroy'])->name('usuario-permisos.destroy')->middleware('permiso:usuarios_del_sistema,eliminar');

  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  // ::::                                                   A P I   S U N A T                                                                                                  ::::
  // :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::


  Route::get('/reniec/dni', [ApiReniecSunatController::class, 'buscarReniec'])  ->name('api.reniec.dni');
  Route::get('/sunat/ruc', [ApiReniecSunatController::class, 'buscarSunat']) ->name('api.sunat.ruc');

});
