<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\Funcion;
use App\Models\Pago;
use App\Models\Pelicula;
use Illuminate\Support\Carbon;

class DashboardAdminController extends Controller
{
    public function index()
    {
        $ahora = Carbon::now();

        $metricas = [
            'peliculas_activas' => Pelicula::query()->where('estado', 'ACTIVA')->count(),
            'funciones_proximas' => Funcion::query()
                ->where('inicio', '>', $ahora)
                ->whereIn('estado', ['PROGRAMADA', 'EN_CURSO'])
                ->count(),
            'compras_confirmadas' => Compra::query()->where('estado', 'CONFIRMADA')->count(),
            'pagos_aprobados' => Pago::query()->where('estado', 'APROBADO')->count(),
        ];

        $funciones = Funcion::query()
            ->with([
                'pelicula' => function ($query) {
                    $query->select([
                        'id_pelicula',
                        'titulo',
                        'duracion_minutos',
                    ])->with([
                        'multimedia' => function ($query) {
                            $query->where('activo', true)
                                ->whereIn('tipo', ['POSTER', 'IMAGEN'])
                                ->orderByRaw("
                                    CASE
                                        WHEN tipo = 'POSTER' AND es_principal = 1 THEN 0
                                        WHEN tipo = 'IMAGEN' AND es_principal = 1 THEN 1
                                        WHEN tipo = 'POSTER' THEN 2
                                        ELSE 3
                                    END
                                ")
                                ->orderBy('orden')
                                ->orderBy('id_multimedia');
                        },
                    ]);
                },
                'sala:id_sala,nombre',
            ])
            ->where('inicio', '>', $ahora)
            ->whereIn('estado', ['PROGRAMADA', 'EN_CURSO'])
            ->orderBy('inicio')
            ->limit(6)
            ->get();

        $comprasRecientes = Compra::query()
            ->with('comprador:id_comprador,nombre,correo')
            ->orderByDesc('creada_en')
            ->limit(6)
            ->get();

        return view('admin.dashboard', compact('metricas', 'funciones', 'comprasRecientes'));
    }
}
