<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EntradaAdminController extends Controller
{
    private const ESTADOS = [
        'RESERVADA',
        'CONFIRMADA',
        'UTILIZADA',
        'CANCELADA',
        'EXPIRADA',
    ];

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:150'],
            'estado' => ['nullable', Rule::in(self::ESTADOS)],
        ]);

        $entradas = DB::table('entrada as e')
            ->join('compra as c', 'c.id_compra', '=', 'e.id_compra')
            ->join('comprador as co', 'co.id_comprador', '=', 'c.id_comprador')
            ->join('funcion_asiento as fa', 'fa.id_funcion_asiento', '=', 'e.id_funcion_asiento')
            ->join('asiento as a', 'a.id_asiento', '=', 'fa.id_asiento')
            ->join('funcion as f', 'f.id_funcion', '=', 'fa.id_funcion')
            ->join('pelicula as p', 'p.id_pelicula', '=', 'f.id_pelicula')
            ->when($filtros['estado'] ?? null, fn ($query, $estado) => $query->where('e.estado', $estado))
            ->when($filtros['buscar'] ?? null, function ($query, $buscar) {
                $query->where(function ($subquery) use ($buscar) {
                    if (ctype_digit($buscar)) {
                        $id = (int) $buscar;
                        $subquery->where('e.id_entrada', $id)
                            ->orWhere('c.id_compra', $id);
                    }

                    $subquery->orWhere('co.nombre', 'like', "%{$buscar}%")
                        ->orWhere('co.correo', 'like', "%{$buscar}%")
                        ->orWhere('p.titulo', 'like', "%{$buscar}%")
                        ->orWhere('a.fila', 'like', "%{$buscar}%");
                });
            })
            ->select([
                'e.id_entrada', 'e.id_compra', 'e.precio_venta', 'e.estado', 'e.reservada_en',
                'co.nombre as comprador_nombre', 'co.correo as comprador_correo',
                'p.titulo as pelicula_titulo', 'f.inicio as funcion_inicio',
                'a.fila as asiento_fila', 'a.numero as asiento_numero',
            ])
            ->orderByDesc('e.reservada_en')
            ->orderByDesc('e.id_entrada')
            ->paginate(15)
            ->withQueryString();

        return view('admin.entradas.index', compact('entradas', 'filtros'));
    }

    public function show(string $entradaId)
    {
        $entrada = DB::table('entrada as e')
            ->join('compra as c', 'c.id_compra', '=', 'e.id_compra')
            ->join('comprador as co', 'co.id_comprador', '=', 'c.id_comprador')
            ->join('funcion_asiento as fa', 'fa.id_funcion_asiento', '=', 'e.id_funcion_asiento')
            ->join('asiento as a', 'a.id_asiento', '=', 'fa.id_asiento')
            ->join('funcion as f', 'f.id_funcion', '=', 'fa.id_funcion')
            ->join('pelicula as p', 'p.id_pelicula', '=', 'f.id_pelicula')
            ->join('sala as s', 's.id_sala', '=', 'f.id_sala')
            ->where('e.id_entrada', $entradaId)
            ->select([
                'e.*',
                'c.estado as compra_estado', 'c.moneda as compra_moneda', 'c.total as compra_total',
                'co.nombre as comprador_nombre', 'co.correo as comprador_correo', 'co.telefono as comprador_telefono',
                'p.titulo as pelicula_titulo', 'f.inicio as funcion_inicio', 'f.formato as funcion_formato',
                'f.estado as funcion_estado', 's.nombre as sala_nombre',
                'a.fila as asiento_fila', 'a.numero as asiento_numero', 'a.tipo as asiento_tipo',
            ])
            ->first();

        abort_if(!$entrada, 404);

        $ticket = DB::table('ticket')->where('id_compra', $entrada->id_compra)->first();

        return view('admin.entradas.show', compact('entrada', 'ticket'));
    }
}
