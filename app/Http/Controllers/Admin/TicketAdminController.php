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
        return view('admin.tickets.index');
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
