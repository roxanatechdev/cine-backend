<?php

use App\Http\Controllers\Admin\DashboardAdminController;
use App\Http\Controllers\Admin\PaisAdminController;
use App\Http\Controllers\Admin\ProductoAdminController;
use App\Http\Controllers\Admin\CategoriaProductoAdminController;
use App\Http\Controllers\Admin\GeneroAdminController;
use App\Http\Controllers\Admin\IdiomaAdminController;
use App\Http\Controllers\Admin\DetalleCompraAdminController;
use App\Http\Controllers\Admin\SalaAdminController;
use App\Http\Controllers\Admin\AsientoAdminController;
use App\Http\Controllers\Admin\FuncionAdminController;
use App\Http\Controllers\Admin\CompradorAdminController;
use App\Http\Controllers\Admin\CompraAdminController;
use App\Http\Controllers\Admin\EntradaAdminController;
use App\Http\Controllers\Admin\PagoAdminController;
use App\Http\Controllers\Admin\ReembolsoAdminController;
use App\Http\Controllers\Admin\TicketAdminController;
use App\Http\Controllers\Admin\PeliculaAdminController;
use App\Http\Controllers\Admin\PeliculaCreditoAdminController;
use App\Http\Controllers\Admin\PeliculaMultimediaAdminController;
use App\Http\Controllers\Admin\PeliculaPaisAdminController;
use App\Http\Controllers\Admin\PersonaAdminController;
use App\Http\Controllers\Api\PeliculaPublicaController;
use App\Http\Controllers\Api\FuncionPublicaController;
use App\Http\Controllers\Api\ReservaPublicaController;
use App\Http\Controllers\Api\PagoSimuladoPublicoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api/peliculas', [PeliculaPublicaController::class, 'cartelera']);
Route::get('/api/peliculas/cartelera', [PeliculaPublicaController::class, 'cartelera']);
Route::get('/api/peliculas/proximamente', [PeliculaPublicaController::class, 'proximamente']);
Route::get('/api/peliculas/destacadas', [PeliculaPublicaController::class, 'destacadas']);
Route::get('/api/peliculas/{id}', [PeliculaPublicaController::class, 'detalle'])->whereNumber('id');

// API pública de lectura para el proceso de compra de entradas.
Route::get('/api/funciones/{id}', [FuncionPublicaController::class, 'detalle'])->whereNumber('id');
Route::get('/api/funciones/{id}/asientos', [FuncionPublicaController::class, 'asientos'])->whereNumber('id');
Route::get('/api/productos', [FuncionPublicaController::class, 'productos']);

// Reservas públicas de invitado: el token de acceso solo se entrega al crear la reserva.
Route::post('/api/compras', [ReservaPublicaController::class, 'store']);
Route::get('/api/compras/{id}/reserva', [ReservaPublicaController::class, 'show'])->whereNumber('id');
Route::delete('/api/compras/{id}/reserva', [ReservaPublicaController::class, 'cancel'])->whereNumber('id');
// Pagos simulados: sin cobros reales; solo el backend puede confirmar la compra y emitir tickets.
Route::post('/api/compras/{id}/pago', [PagoSimuladoPublicoController::class, 'store'])->whereNumber('id');
Route::get('/api/compras/{id}/ticket', [PagoSimuladoPublicoController::class, 'ticket'])->whereNumber('id');

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardAdminController::class, 'index'])->name('dashboard');
        Route::resource('categorias-producto', CategoriaProductoAdminController::class)
            ->parameters(['categorias-producto' => 'categoria_producto'])
            ->only(['index', 'create', 'store', 'edit', 'update']);

        Route::resource('generos', GeneroAdminController::class)
            ->only(['index', 'create', 'store', 'edit', 'update']);

        Route::resource('idiomas', IdiomaAdminController::class)
            ->only(['index', 'create', 'store', 'edit', 'update']);

        Route::resource('productos', ProductoAdminController::class)
            ->only(['index', 'create', 'store', 'edit', 'update']);

        // Detalle de compra: consulta del historial de productos y precios congelados.
        Route::get('detalles-compra', [DetalleCompraAdminController::class, 'index'])
            ->name('detalles-compra.index');
        Route::get('detalles-compra/{detalleCompra}', [DetalleCompraAdminController::class, 'show'])
            ->whereNumber('detalleCompra')
            ->name('detalles-compra.show');

        // Exportación CSV del catálogo (respeta los filtros activos).
        Route::get('peliculas/exportar', [PeliculaAdminController::class, 'export'])
            ->name('peliculas.export');

        Route::resource('peliculas', PeliculaAdminController::class)
            ->only([
                'index',
                'create',
                'store',
                'edit',
                'update',
            ]);
        // Recursos multimedia de una película
        Route::prefix('peliculas/{pelicula}/multimedia')
            ->name('peliculas.multimedia.')
            ->controller(PeliculaMultimediaAdminController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{multimedia}/edit', 'edit')->name('edit');
                Route::put('/{multimedia}', 'update')->name('update');
            });
        Route::patch(
            'peliculas/{pelicula}/multimedia/{multimedia}/desactivar',
            [PeliculaMultimediaAdminController::class, 'desactivar']
        )->name('peliculas.multimedia.desactivar');
        Route::resource('personas', PersonaAdminController::class)
            ->only([
                'index',
                'create',
                'store',
                'edit',
                'update',
            ]);
        // Créditos de una película
        Route::prefix('peliculas/{pelicula}/creditos')
            ->name('peliculas.creditos.')
            ->controller(PeliculaCreditoAdminController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::get('/personas/buscar', 'buscarPersonas')->name('personas.buscar');
                Route::post('/personas/crear-rapido', 'crearPersonaRapida')->name('personas.crear-rapido');
                Route::post('/', 'store')->name('store');

                Route::get(
                    '/{persona}/{rol}/edit',
                    'edit'
                )->whereNumber('persona')
                    ->name('edit');

                Route::put(
                    '/{persona}/{rol}',
                    'update'
                )->whereNumber('persona')
                    ->name('update');

                Route::delete(
                    '/{persona}/{rol}',
                    'destroy'
                )->whereNumber('persona')
                    ->name('destroy');
            });
        Route::resource('paises', PaisAdminController::class)
            ->only([
                'index',
                'create',
                'store',
                'edit',
                'update',
            ]);
        // Categorías del catálogo de confitería
        Route::resource('categorias-producto', CategoriaProductoAdminController::class)
            ->parameters(['categorias-producto' => 'categoriaProducto'])
            ->only(['index', 'create', 'store', 'edit', 'update']);

        Route::patch(
            'categorias-producto/{categoriaProducto}/estado',
            [CategoriaProductoAdminController::class, 'cambiarEstado']
        )->name('categorias-producto.estado');
        // Catálogo de productos de confitería
        Route::resource('productos', ProductoAdminController::class)
            ->parameters(['productos' => 'producto'])
            ->only(['index', 'create', 'store', 'edit', 'update']);
        Route::patch('productos/{producto}/estado', [ProductoAdminController::class, 'cambiarEstado'])
            ->name('productos.estado');
        Route::resource('salas', SalaAdminController::class)
            ->only([
                'index',
                'create',
                'store',
                'edit',
                'update',
                'destroy',
            ]);
        Route::get('salas/{sala}/distribucion', [SalaAdminController::class, 'distribucion'])
            ->whereNumber('sala')
            ->name('salas.distribucion');
        Route::resource('asientos', AsientoAdminController::class)
            ->only([
                'index',
                'create',
                'store',
                'edit',
                'update',
                'destroy',
            ]);
        Route::resource('funciones', FuncionAdminController::class)
            ->only([
                'index',
                'create',
                'store',
                'edit',
                'update',
                'destroy',
            ]);
        Route::resource('compradores', CompradorAdminController::class)
            ->parameters(['compradores' => 'comprador'])
            ->only([
                'index',
                'create',
                'store',
                'edit',
                'update',
                'destroy',
            ]);
        // Entradas: consulta de historial; los estados se actualizan desde los flujos transaccionales.
        Route::get('entradas', [EntradaAdminController::class, 'index'])
            ->name('entradas.index');
        Route::get('entradas/{entrada}', [EntradaAdminController::class, 'show'])
            ->whereNumber('entrada')
            ->name('entradas.show');

        // Pagos: consulta de intentos, referencias y reembolsos asociados.
        Route::get('pagos', [PagoAdminController::class, 'index'])
            ->name('pagos.index');
        Route::get('pagos/{pago}', [PagoAdminController::class, 'show'])
            ->whereNumber('pago')
            ->name('pagos.show');

        // Tickets: consulta de validación y trazabilidad; no se editan desde administración.
        Route::get('tickets', [TicketAdminController::class, 'index'])
            ->name('tickets.index');
        Route::get('tickets/{ticket}', [TicketAdminController::class, 'show'])
            ->whereNumber('ticket')
            ->name('tickets.show');

        // Reembolsos: solicitudes y transiciones de estado controladas.
        Route::get('reembolsos', [ReembolsoAdminController::class, 'index'])
            ->name('reembolsos.index');
        Route::get('pagos/{pago}/reembolsos/create', [ReembolsoAdminController::class, 'create'])
            ->whereNumber('pago')
            ->name('reembolsos.create');
        Route::post('pagos/{pago}/reembolsos', [ReembolsoAdminController::class, 'store'])
            ->whereNumber('pago')
            ->name('reembolsos.store');
        Route::get('reembolsos/{reembolso}', [ReembolsoAdminController::class, 'show'])
            ->whereNumber('reembolso')
            ->name('reembolsos.show');
        Route::patch('reembolsos/{reembolso}/estado', [ReembolsoAdminController::class, 'updateEstado'])
            ->whereNumber('reembolso')
            ->name('reembolsos.estado');

        // Compras: consulta de historial y transiciones de estado controladas.
        Route::get('compras', [CompraAdminController::class, 'index'])
            ->name('compras.index');
        Route::get('compras/{compra}', [CompraAdminController::class, 'show'])
            ->whereNumber('compra')
            ->name('compras.show');
        Route::patch('compras/{compra}/estado', [CompraAdminController::class, 'updateEstado'])
            ->whereNumber('compra')
            ->name('compras.estado');
        // Países de producción de una película
        Route::prefix('peliculas/{pelicula}/paises')
            ->name('peliculas.paises.')
            ->controller(PeliculaPaisAdminController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::get('/buscar', 'buscarPaises')->name('buscar');
                Route::post('/crear-rapido', 'crearRapido')->name('crear-rapido');
                Route::post('/', 'store')->name('store');

                Route::delete(
                    '/{pais}',
                    'destroy'
                )->where('pais', '[A-Za-z]{2}')
                    ->name('destroy');
            });
    });
