<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DetalleCompraAdminController extends Controller
{
    private const ESTADOS_COMPRA = [
        'INICIADA', 'PENDIENTE_PAGO', 'CONFIRMADA', 'CANCELADA', 'EXPIRADA',
    ];

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:150'],
            'estado_compra' => ['nullable', Rule::in(self::ESTADOS_COMPRA)],
            'producto' => ['nullable', 'integer', 'min:1'],
        ]);

        $detalles = DB::table('detalle_compra as dc')
            ->join('compra as c', 'c.id_compra', '=', 'dc.id_compra')
            ->join('producto as p', 'p.id_producto', '=', 'dc.id_producto')
            ->leftJoin('comprador as co', 'co.id_comprador', '=', 'c.id_comprador')
            ->when($filtros['estado_compra'] ?? null, fn ($query, $estado) => $query->where('c.estado', $estado))
            ->when($filtros['producto'] ?? null, fn ($query, $producto) => $query->where('dc.id_producto', $producto))
            ->when($filtros['buscar'] ?? null, function ($query, $buscar) {
                $query->where(function ($subquery) use ($buscar) {
                    if (ctype_digit($buscar)) {
                        $id = (int) $buscar;
                        $subquery->where('dc.id_detalle_compra', $id)
                            ->orWhere('dc.id_compra', $id)
                            ->orWhere('dc.id_producto', $id)
                            ->orWhere('co.id_comprador', $id);
                    }
                    $subquery->orWhere('p.nombre', 'like', "%{$buscar}%")
                        ->orWhere('co.nombre', 'like', "%{$buscar}%")
                        ->orWhere('co.correo', 'like', "%{$buscar}%");
                });
            })
            ->select([
                'dc.id_detalle_compra', 'dc.id_compra', 'dc.id_producto',
                'dc.cantidad', 'dc.precio_unitario', 'c.estado as estado_compra',
                'c.creada_en', 'c.moneda', 'p.nombre as producto_nombre',
                'co.nombre as comprador_nombre', 'co.correo as comprador_correo',
            ])
            ->orderByDesc('dc.id_detalle_compra')
            ->paginate(15)
            ->withQueryString();

        return view('admin.detalles_compra.index', compact('detalles', 'filtros'));
    }

    public function show(string $detalleCompra)
    {
        $detalle = DB::table('detalle_compra as dc')
            ->join('compra as c', 'c.id_compra', '=', 'dc.id_compra')
            ->join('producto as p', 'p.id_producto', '=', 'dc.id_producto')
            ->leftJoin('categoria_producto as cp', 'cp.id_categoria_producto', '=', 'p.id_categoria_producto')
            ->leftJoin('comprador as co', 'co.id_comprador', '=', 'c.id_comprador')
            ->where('dc.id_detalle_compra', $detalleCompra)
            ->select([
                'dc.*', 'c.estado as estado_compra', 'c.creada_en', 'c.total as total_compra',
                'c.moneda', 'p.nombre as producto_nombre', 'p.descripcion as producto_descripcion',
                'p.precio_actual as precio_actual_producto', 'p.estado as estado_producto',
                'cp.nombre as categoria_nombre', 'co.id_comprador',
                'co.nombre as comprador_nombre', 'co.correo as comprador_correo',
            ])
            ->first();

        abort_if(!$detalle, 404);

        $detalle->subtotal = (float) $detalle->cantidad * (float) $detalle->precio_unitario;

        return view('admin.detalles_compra.show', compact('detalle'));
    }
}
