<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PagoAdminController extends Controller
{
    private const ESTADOS = ['PENDIENTE', 'APROBADO', 'RECHAZADO'];
    private const METODOS = ['QR', 'TARJETA'];

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:150'],
            'estado' => ['nullable', Rule::in(self::ESTADOS)],
            'metodo' => ['nullable', Rule::in(self::METODOS)],
        ]);

        $pagos = DB::table('pago as pa')
            ->join('compra as c', 'c.id_compra', '=', 'pa.id_compra')
            ->join('comprador as co', 'co.id_comprador', '=', 'c.id_comprador')
            ->when($filtros['estado'] ?? null, fn ($query, $estado) => $query->where('pa.estado', $estado))
            ->when($filtros['metodo'] ?? null, fn ($query, $metodo) => $query->where('pa.metodo', $metodo))
            ->when($filtros['buscar'] ?? null, function ($query, $buscar) {
                $query->where(function ($subquery) use ($buscar) {
                    if (ctype_digit($buscar)) {
                        $id = (int) $buscar;
                        $subquery->where('pa.id_pago', $id)
                            ->orWhere('c.id_compra', $id);
                    }

                    $subquery->orWhere('co.nombre', 'like', "%{$buscar}%")
                        ->orWhere('co.correo', 'like', "%{$buscar}%")
                        ->orWhere('pa.referencia_externa', 'like', "%{$buscar}%");
                });
            })
            ->select([
                'pa.id_pago', 'pa.id_compra', 'pa.metodo', 'pa.importe', 'pa.estado',
                'pa.referencia_externa', 'pa.creado_en', 'pa.aprobado_en',
                'c.moneda as compra_moneda', 'c.total as compra_total', 'c.estado as compra_estado',
                'co.nombre as comprador_nombre', 'co.correo as comprador_correo',
            ])
            ->orderByDesc('pa.creado_en')
            ->orderByDesc('pa.id_pago')
            ->paginate(15)
            ->withQueryString();

        return view('admin.pagos.index', compact('pagos', 'filtros'));
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
