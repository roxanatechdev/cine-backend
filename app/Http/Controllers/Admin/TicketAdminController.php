<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class TicketAdminController extends Controller
{
    private const ESTADOS = ['EMITIDO', 'UTILIZADO', 'ANULADO'];

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:150'],
            'estado' => ['nullable', Rule::in(self::ESTADOS)],
        ]);

        $tickets = DB::table('ticket as t')
            ->join('compra as c', 'c.id_compra', '=', 't.id_compra')
            ->join('comprador as co', 'co.id_comprador', '=', 'c.id_comprador')
            ->when($filtros['estado'] ?? null, fn ($query, $estado) => $query->where('t.estado', $estado))
            ->when($filtros['buscar'] ?? null, function ($query, $buscar) {
                $query->where(function ($subquery) use ($buscar) {
                    if (ctype_digit($buscar)) {
                        $id = (int) $buscar;
                        $subquery->where('t.id_ticket', $id)
                            ->orWhere('c.id_compra', $id);
                    }

                    $subquery->orWhere('t.codigo_validacion', 'like', "%{$buscar}%")
                        ->orWhere('co.nombre', 'like', "%{$buscar}%")
                        ->orWhere('co.correo', 'like', "%{$buscar}%");
                });
            })
            ->select([
                't.id_ticket', 't.id_compra', 't.codigo_validacion', 't.emitido_en',
                't.estado', 't.utilizado_en', 'c.estado as compra_estado',
                'c.total as compra_total', 'c.moneda', 'co.nombre as comprador_nombre',
                'co.correo as comprador_correo',
            ])
            ->orderByDesc('t.emitido_en')
            ->orderByDesc('t.id_ticket')
            ->paginate(15)
            ->withQueryString();

        return view('admin.tickets.index', compact('tickets', 'filtros'));
    }

    public function show(string $ticketId)
    {
        $ticket = DB::table('ticket as t')
            ->join('compra as c', 'c.id_compra', '=', 't.id_compra')
            ->join('comprador as co', 'co.id_comprador', '=', 'c.id_comprador')
            ->where('t.id_ticket', $ticketId)
            ->select([
                't.*', 'c.estado as compra_estado', 'c.total as compra_total',
                'c.moneda', 'c.creada_en as compra_creada_en', 'c.vence_reserva',
                'c.cancelada_en', 'co.id_comprador', 'co.nombre as comprador_nombre',
                'co.correo as comprador_correo', 'co.telefono as comprador_telefono',
            ])
            ->first();

        abort_if(!$ticket, 404);

        $entradas = DB::table('entrada as e')
            ->leftJoin('funcion_asiento as fa', 'fa.id_funcion_asiento', '=', 'e.id_funcion_asiento')
            ->leftJoin('funcion as f', 'f.id_funcion', '=', 'fa.id_funcion')
            ->leftJoin('pelicula as pe', 'pe.id_pelicula', '=', 'f.id_pelicula')
            ->leftJoin('asiento as a', 'a.id_asiento', '=', 'fa.id_asiento')
            ->where('e.id_compra', $ticket->id_compra)
            ->select([
                'e.id_entrada', 'e.precio_venta', 'e.estado as entrada_estado',
                'f.id_funcion', 'f.inicio', 'pe.titulo', 'a.fila', 'a.numero',
            ])
            ->orderBy('e.id_entrada')
            ->get();

        $pagos = DB::table('pago')
            ->where('id_compra', $ticket->id_compra)
            ->orderByDesc('creado_en')
            ->get();

        return view('admin.tickets.show', compact('ticket', 'entradas', 'pagos'));
    }
}
