<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PagoAdminController extends Controller
{
    public function index()
    {
        return view('admin.pagos.index');
    }

    public function show(string $pagoId)
    {
        $pago = DB::table('pago as pa')
            ->join('compra as c', 'c.id_compra', '=', 'pa.id_compra')
            ->join('comprador as co', 'co.id_comprador', '=', 'c.id_comprador')
            ->where('pa.id_pago', $pagoId)
            ->select([
                'pa.*', 'c.estado as compra_estado', 'c.total as compra_total',
                'c.moneda as compra_moneda', 'c.creada_en as compra_creada_en',
                'c.vence_reserva', 'co.nombre as comprador_nombre',
                'co.correo as comprador_correo', 'co.telefono as comprador_telefono',
            ])
            ->first();

        abort_if(!$pago, 404);

        $reembolsos = DB::table('reembolso')
            ->where('id_pago', $pago->id_pago)
            ->orderByDesc('solicitado_en')
            ->orderByDesc('id_reembolso')
            ->get();

        $otrosPagos = DB::table('pago')
            ->where('id_compra', $pago->id_compra)
            ->where('id_pago', '<>', $pago->id_pago)
            ->orderByDesc('creado_en')
            ->get();

        return view('admin.pagos.show', compact('pago', 'reembolsos', 'otrosPagos'));
    }
}
