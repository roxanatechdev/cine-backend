<?php

use App\Http\Controllers\Admin\PaisAdminController;
use App\Http\Controllers\Admin\PeliculaAdminController;
use App\Http\Controllers\Admin\PeliculaCreditoAdminController;
use App\Http\Controllers\Admin\PeliculaMultimediaAdminController;
use App\Http\Controllers\Admin\PeliculaPaisAdminController;
use App\Http\Controllers\Admin\PersonaAdminController;
use App\Http\Controllers\PeliculaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api/peliculas', [PeliculaController::class, 'index']);

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
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
        // Países de producción de una película
        Route::prefix('peliculas/{pelicula}/paises')
            ->name('peliculas.paises.')
            ->controller(PeliculaPaisAdminController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');

                Route::delete(
                    '/{pais}',
                    'destroy'
                )->where('pais', '[A-Za-z]{2}')
                    ->name('destroy');
            });
    });
