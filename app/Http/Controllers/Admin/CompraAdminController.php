<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class CompraAdminController extends Controller
{
    private const ESTADOS = [
        'INICIADA',
        'PENDIENTE_PAGO',
        'CONFIRMADA',
        'CANCELADA',
        'EXPIRADA',
    ];

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:150'],
            'estado' => ['nullable', Rule::in(self::ESTADOS)],
        ]);

        $compras = Compra::query()
            ->with('comprador:id_comprador,nombre,correo')
            ->when($filtros['estado'] ?? null, fn ($query, $estado) => $query->where('estado', $estado))
            ->when($filtros['buscar'] ?? null, function ($query, $buscar) {
                $query->where(function ($subquery) use ($buscar) {
                    if (ctype_digit($buscar)) {
                        $subquery->where('id_compra', (int) $buscar);
                    }
                    $subquery->orWhereHas('comprador', function ($compradorQuery) use ($buscar) {
                        $compradorQuery->where('nombre', 'like', "%{$buscar}%")
                            ->orWhere('correo', 'like', "%{$buscar}%");
                    });
                });
            })
            ->orderByDesc('creada_en')
            ->orderByDesc('id_compra')
            ->paginate(15)
            ->withQueryString();

        return view('admin.compras.index', compact('compras', 'filtros'));
    }

    public function show(string $compraId)
    {
        $compra = Compra::query()
            ->with('comprador')
            ->findOrFail($compraId);

        $entradas = DB::table('entrada as e')
            ->join('funcion_asiento as fa', 'fa.id_funcion_asiento', '=', 'e.id_funcion_asiento')
            ->join('asiento as a', 'a.id_asiento', '=', 'fa.id_asiento')
            ->join('funcion as f', 'f.id_funcion', '=', 'fa.id_funcion')
            ->join('pelicula as p', 'p.id_pelicula', '=', 'f.id_pelicula')
            ->where('e.id_compra', $compra->id_compra)
            ->select([
                'e.id_entrada', 'e.precio_venta', 'e.estado as estado_entrada',
                'e.reservada_en', 'e.confirmada_en', 'e.utilizada_en', 'e.cerrada_en',
                'a.fila', 'a.numero', 'f.inicio', 'p.titulo',
            ])
            ->orderBy('e.id_entrada')
            ->get();

        $pagos = DB::table('pago')
            ->where('id_compra', $compra->id_compra)
            ->orderByDesc('creado_en')
            ->get();

        $productos = DB::table('detalle_compra as dc')
            ->join('producto as p', 'p.id_producto', '=', 'dc.id_producto')
            ->where('dc.id_compra', $compra->id_compra)
            ->select('dc.id_detalle_compra', 'dc.cantidad', 'dc.precio_unitario', 'p.nombre')
            ->orderBy('p.nombre')
            ->get();

        $ticket = DB::table('ticket')->where('id_compra', $compra->id_compra)->first();
        $reembolsos = DB::table('reembolso as r')
            ->join('pago as p', 'p.id_pago', '=', 'r.id_pago')
            ->where('p.id_compra', $compra->id_compra)
            ->select('r.*', 'p.id_pago')
            ->orderByDesc('r.solicitado_en')
            ->get();

        return view('admin.compras.show', compact('compra', 'entradas', 'pagos', 'productos', 'ticket', 'reembolsos'));
    }

    public function updateEstado(Request $request, string $compraId)
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in(self::ESTADOS)],
            'motivo_cancelacion' => ['nullable', 'string', 'max:255'],
        ], [
            'estado.required' => 'Selecciona el estado de destino.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'motivo_cancelacion.max' => 'El motivo no puede superar los 255 caracteres.',
        ]);

        try {
            DB::transaction(function () use ($compraId, $datos) {
                $compra = Compra::query()->whereKey($compraId)->lockForUpdate()->firstOrFail();
                $actual = $compra->estado;
                $destino = $datos['estado'];

                if ($actual === $destino) {
                    throw new RuntimeException('La compra ya se encuentra en ese estado.');
                }

                if ($destino === 'CONFIRMADA') {
                    $this->confirmarCompra($compra, $actual);
                    return;
                }

                if ($destino === 'EXPIRADA') {
                    $this->expirarCompra($compra, $actual);
                    return;
                }

                if ($destino === 'CANCELADA') {
                    $this->cancelarCompra($compra, $actual, $datos['motivo_cancelacion'] ?? null);
                    return;
                }

                throw new RuntimeException('No se permite reabrir una compra ni retroceder su estado desde la administración.');
            }, 3);
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        } catch (QueryException $exception) {
            report($exception);
            return back()->withInput()->with('error', 'No fue posible actualizar la compra por una restricción de integridad.');
        }

        return redirect()
            ->route('admin.compras.show', $compraId)
            ->with('success', 'Estado de la compra actualizado correctamente.');
    }

    private function confirmarCompra(Compra $compra, string $actual): void
    {
        if ($actual !== 'PENDIENTE_PAGO') {
            throw new RuntimeException('Solo se puede confirmar una compra pendiente de pago.');
        }

        if ($compra->vence_reserva && $compra->vence_reserva->isPast()) {
            throw new RuntimeException('La reserva ya venció; no se puede confirmar. Debe gestionarse como expirada o por el flujo de conciliación de pagos tardíos.');
        }

        $pagosAprobados = DB::table('pago')
            ->where('id_compra', $compra->id_compra)
            ->where('estado', 'APROBADO')
            ->lockForUpdate()
            ->get();

        if ($pagosAprobados->count() !== 1) {
            throw new RuntimeException('Para confirmar debe existir exactamente un pago aprobado.');
        }

        if ($this->aCentavos((string) $pagosAprobados->first()->importe) !== $this->aCentavos((string) $compra->total)) {
            throw new RuntimeException('El importe del pago aprobado debe coincidir exactamente con el total de la compra.');
        }

        $entradas = DB::table('entrada')
            ->where('id_compra', $compra->id_compra)
            ->lockForUpdate()
            ->get(['estado']);
        $entradasReservadas = $entradas->where('estado', 'RESERVADA')->count();

        if ($entradas->isNotEmpty() && $entradasReservadas !== $entradas->count()) {
            throw new RuntimeException('Las entradas no están todas reservadas; no se puede confirmar esta compra desde administración.');
        }

        $ahora = now();
        $compra->estado = 'CONFIRMADA';
        $compra->save();

        DB::table('entrada')
            ->where('id_compra', $compra->id_compra)
            ->where('estado', 'RESERVADA')
            ->update(['estado' => 'CONFIRMADA', 'confirmada_en' => $ahora]);

        if ($entradasReservadas > 0 && !DB::table('ticket')->where('id_compra', $compra->id_compra)->exists()) {
            DB::table('ticket')->insert([
                'id_compra' => $compra->id_compra,
                'codigo_validacion' => Str::upper(Str::random(32)),
                'emitido_en' => $ahora,
                'estado' => 'EMITIDO',
            ]);
        }
    }

    private function aCentavos(string $importe): int
    {
        $importe = number_format((float) $importe, 2, '.', '');
        [$entero, $decimal] = array_pad(explode('.', $importe, 2), 2, '00');
        return ((int) $entero * 100) + (int) str_pad(substr($decimal, 0, 2), 2, '0');
    }

    private function expirarCompra(Compra $compra, string $actual): void
    {
        if (!in_array($actual, ['INICIADA', 'PENDIENTE_PAGO'], true)) {
            throw new RuntimeException('Solo se pueden expirar compras iniciadas o pendientes de pago.');
        }

        if (!$compra->vence_reserva || $compra->vence_reserva->isFuture()) {
            throw new RuntimeException('La reserva todavía no ha vencido o no tiene fecha de vencimiento.');
        }

        if (DB::table('pago')->where('id_compra', $compra->id_compra)->where('estado', 'APROBADO')->exists()) {
            throw new RuntimeException('La compra tiene un pago aprobado; debe resolverse por el flujo de conciliación, no expirar automáticamente.');
        }

        if (DB::table('entrada')->where('id_compra', $compra->id_compra)->whereNotIn('estado', ['RESERVADA', 'CANCELADA', 'EXPIRADA'])->exists()) {
            throw new RuntimeException('La compra contiene entradas que no están reservadas; no se puede expirar desde administración.');
        }

        $compra->estado = 'EXPIRADA';
        $compra->save();

        DB::table('entrada')
            ->where('id_compra', $compra->id_compra)
            ->where('estado', 'RESERVADA')
            ->update(['estado' => 'EXPIRADA', 'cerrada_en' => now()]);
    }

    private function cancelarCompra(Compra $compra, string $actual, ?string $motivo): void
    {
        if (!in_array($actual, ['INICIADA', 'PENDIENTE_PAGO', 'CONFIRMADA'], true)) {
            throw new RuntimeException('Solo se pueden cancelar compras iniciadas, pendientes de pago o confirmadas.');
        }

        if (DB::table('entrada')->where('id_compra', $compra->id_compra)->where('estado', 'UTILIZADA')->exists()) {
            throw new RuntimeException('No se puede cancelar una compra que contiene entradas ya utilizadas.');
        }

        $compra->estado = 'CANCELADA';
        $compra->cancelada_en = now();
        $compra->motivo_cancelacion = $motivo;
        $compra->save();

        DB::table('entrada')
            ->where('id_compra', $compra->id_compra)
            ->whereIn('estado', ['RESERVADA', 'CONFIRMADA'])
            ->update(['estado' => 'CANCELADA', 'cerrada_en' => now()]);

        DB::table('ticket')
            ->where('id_compra', $compra->id_compra)
            ->where('estado', 'EMITIDO')
            ->update(['estado' => 'ANULADO']);
        // Los reembolsos son un proceso separado y no se ejecutan aquí.
    }
}
