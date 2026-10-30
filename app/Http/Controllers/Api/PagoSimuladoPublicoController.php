<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Flujo de pagos simulado para desarrollo; no procesa dinero real. */
class PagoSimuladoPublicoController extends Controller
{
    /** Registra un intento simulado y confirma compra/entradas únicamente si se aprueba. */
    public function store(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'metodo' => ['required', 'in:TARJETA,QR'],
            'resultado_simulado' => ['sometimes', 'in:APROBADO,RECHAZADO,PENDIENTE'],
        ]);
        $token = (string) $request->header('X-Reserva-Token', '');
        if ($token === '') {
            return response()->json(['message' => 'Falta el token de acceso de la reserva.'], 401);
        }

        return DB::transaction(function () use ($id, $token, $data) {
            $purchase = DB::table('compra')->where('id_compra', $id)->lockForUpdate()->first();
            if (!$purchase || !hash_equals((string) $purchase->token_acceso_hash, hash('sha256', $token))) {
                return response()->json(['message' => 'No se encontró la reserva o el token no es válido.'], 404);
            }

            // Repetir la solicitud tras una aprobación no crea un segundo pago ni otro ticket.
            if ($purchase->estado === 'CONFIRMADA') {
                $approvedPayment = DB::table('pago')->where('id_compra', $id)->where('estado', 'APROBADO')->orderByDesc('id_pago')->first();
                if (!$approvedPayment) {
                    return response()->json(['message' => 'La compra figura confirmada, pero no se encontró un pago aprobado. Contacta al administrador.'], 409);
                }
                return response()->json([
                    'message' => 'La compra ya estaba confirmada.',
                    'compra' => ['id_compra' => (int) $id, 'estado' => 'CONFIRMADA', 'total' => (string) $purchase->total, 'moneda' => (string) $purchase->moneda],
                    'pago' => $this->paymentPayload($approvedPayment),
                    'ticket' => $this->ticketPayload($id),
                ]);
            }

            if (!in_array($purchase->estado, ['INICIADA', 'PENDIENTE_PAGO'], true)) {
                return response()->json(['message' => 'La reserva ya no está activa y no admite pagos.', 'estado' => $purchase->estado], 409);
            }

            if ($purchase->vence_reserva && Carbon::parse($purchase->vence_reserva)->lte(now())) {
                $this->expirePurchase($id);
                return response()->json(['message' => 'El tiempo de reserva terminó. Los asientos fueron liberados y no se puede aprobar este pago.', 'estado' => 'EXPIRADA'], 409);
            }

            $result = $data['resultado_simulado'] ?? 'APROBADO';
            $reference = 'SIM-' . $id . '-' . Str::upper(Str::random(16));
            $paymentId = DB::table('pago')->insertGetId([
                'id_compra' => $id,
                'metodo' => $data['metodo'],
                'importe' => $purchase->total,
                'estado' => $result,
                'referencia_externa' => $reference,
                'creado_en' => now(),
                'aprobado_en' => $result === 'APROBADO' ? now() : null,
            ], 'id_pago');

            if ($result !== 'APROBADO') {
                return response()->json([
                    'message' => $result === 'RECHAZADO'
                        ? 'El pago simulado fue rechazado. La reserva sigue activa mientras no venza.'
                        : 'El pago simulado quedó pendiente. La compra aún no ha sido confirmada.',
                    'compra' => ['id_compra' => (int) $id, 'estado' => (string) $purchase->estado, 'total' => (string) $purchase->total, 'moneda' => (string) $purchase->moneda, 'vence_reserva' => $purchase->vence_reserva ? Carbon::parse($purchase->vence_reserva)->toIso8601String() : null],
                    'pago' => $this->paymentPayload(DB::table('pago')->where('id_pago', $paymentId)->first()),
                    'ticket' => null,
                ]);
            }

            DB::table('compra')->where('id_compra', $id)->update(['estado' => 'CONFIRMADA', 'stock_apartado' => false]);
            DB::table('entrada')->where('id_compra', $id)->where('estado', 'RESERVADA')->update(['estado' => 'CONFIRMADA', 'confirmada_en' => now()]);

            // Las compras con entradas emiten ticket; las compras solo de confitería no.
            $hasEntries = DB::table('entrada')->where('id_compra', $id)->exists();
            if ($hasEntries && !DB::table('ticket')->where('id_compra', $id)->exists()) {
                DB::table('ticket')->insert([
                    'id_compra' => $id,
                    'codigo_validacion' => $this->uniqueTicketCode(),
                    'emitido_en' => now(),
                    'estado' => 'EMITIDO',
                ]);
            }

            $approvedPayment = DB::table('pago')->where('id_pago', $paymentId)->first();
            return response()->json([
                'message' => $hasEntries
                    ? 'Pago aprobado. La compra fue confirmada y el ticket fue emitido.'
                    : 'Pago aprobado. La compra de confitería fue confirmada; no corresponde emitir una entrada.',
                'compra' => ['id_compra' => (int) $id, 'estado' => 'CONFIRMADA', 'total' => (string) $purchase->total, 'moneda' => (string) $purchase->moneda],
                'pago' => $this->paymentPayload($approvedPayment),
                'ticket' => $this->ticketPayload($id),
            ]);
        }, 3);
    }

    /** Devuelve el ticket solo si existe una compra confirmada con un pago aprobado. */
    public function ticket(Request $request, int $id): JsonResponse
    {
        $token = (string) $request->header('X-Reserva-Token', '');
        if ($token === '') {
            return response()->json(['message' => 'Falta el token de acceso de la reserva.'], 401);
        }

        $purchase = DB::table('compra')->where('id_compra', $id)->first();
        if (!$purchase || !hash_equals((string) $purchase->token_acceso_hash, hash('sha256', $token))) {
            return response()->json(['message' => 'No se encontró la compra o el token no es válido.'], 404);
        }
        $approvedPayment = DB::table('pago')->where('id_compra', $id)->where('estado', 'APROBADO')->orderByDesc('id_pago')->first();
        $ticket = DB::table('ticket')->where('id_compra', $id)->first();
        if ($purchase->estado !== 'CONFIRMADA' || !$approvedPayment || !$ticket || $ticket->estado !== 'EMITIDO') {
            return response()->json(['message' => 'Todavía no existe un ticket emitido. Se requiere un pago aprobado.', 'estado_compra' => (string) $purchase->estado, 'estado_pago' => $approvedPayment->estado ?? null], 409);
        }

        return response()->json([
            'compra' => ['id_compra' => (int) $id, 'estado' => (string) $purchase->estado, 'total' => (string) $purchase->total, 'moneda' => (string) $purchase->moneda],
            'pago' => $this->paymentPayload($approvedPayment),
            'ticket' => $this->ticketPayload($id),
        ]);
    }

    private function paymentPayload(object $payment): array
    {
        return [
            'id_pago' => (int) $payment->id_pago,
            'metodo' => (string) $payment->metodo,
            'importe' => (string) $payment->importe,
            'estado' => (string) $payment->estado,
            'referencia' => (string) $payment->referencia_externa,
            'creado_en' => Carbon::parse($payment->creado_en)->toIso8601String(),
            'aprobado_en' => $payment->aprobado_en ? Carbon::parse($payment->aprobado_en)->toIso8601String() : null,
        ];
    }

    private function ticketPayload(int $id): ?array
    {
        $ticket = DB::table('ticket')->where('id_compra', $id)->first();
        if (!$ticket) return null;

        $function = DB::table('entrada as e')
            ->join('funcion_asiento as fa', 'fa.id_funcion_asiento', '=', 'e.id_funcion_asiento')
            ->join('funcion as f', 'f.id_funcion', '=', 'fa.id_funcion')
            ->join('pelicula as p', 'p.id_pelicula', '=', 'f.id_pelicula')
            ->join('sala as s', 's.id_sala', '=', 'f.id_sala')
            ->where('e.id_compra', $id)
            ->select(['f.id_funcion', 'f.inicio', 'f.formato', 'p.id_pelicula', 'p.titulo', 'p.duracion_minutos', 'p.clasificacion', 's.nombre as sala'])
            ->first();
        $seats = DB::table('entrada as e')
            ->join('funcion_asiento as fa', 'fa.id_funcion_asiento', '=', 'e.id_funcion_asiento')
            ->join('asiento as a', 'a.id_asiento', '=', 'fa.id_asiento')
            ->where('e.id_compra', $id)
            ->select(['a.fila', 'a.numero', 'e.precio_venta', 'e.estado'])
            ->orderBy('a.fila')->orderBy('a.numero')->get()
            ->map(fn ($seat) => ['fila' => (string) $seat->fila, 'numero' => (int) $seat->numero, 'precio' => (string) $seat->precio_venta, 'estado' => (string) $seat->estado])->values();
        $products = DB::table('detalle_compra as d')->join('producto as p', 'p.id_producto', '=', 'd.id_producto')
            ->where('d.id_compra', $id)->select(['p.nombre', 'd.cantidad', 'd.precio_unitario'])->get()
            ->map(fn ($item) => ['nombre' => (string) $item->nombre, 'cantidad' => (int) $item->cantidad, 'precio_unitario' => (string) $item->precio_unitario])->values();
        $buyer = DB::table('compra as c')->join('comprador as b', 'b.id_comprador', '=', 'c.id_comprador')
            ->where('c.id_compra', $id)->select(['b.nombre', 'b.correo'])->first();

        return [
            'id_ticket' => (int) $ticket->id_ticket,
            'codigo_validacion' => (string) $ticket->codigo_validacion,
            'estado' => (string) $ticket->estado,
            'emitido_en' => Carbon::parse($ticket->emitido_en)->toIso8601String(),
            'funcion' => $function ? [
                'id_funcion' => (int) $function->id_funcion,
                'id_pelicula' => (int) $function->id_pelicula,
                'pelicula' => (string) $function->titulo,
                'inicio' => Carbon::parse($function->inicio)->toIso8601String(),
                'formato' => (string) $function->formato,
                'duracion_minutos' => $function->duracion_minutos !== null ? (int) $function->duracion_minutos : null,
                'clasificacion' => $function->clasificacion,
                'sala' => (string) $function->sala,
            ] : null,
            'comprador' => $buyer ? ['nombre' => (string) $buyer->nombre, 'correo' => (string) $buyer->correo] : null,
            'asientos' => $seats,
            'productos' => $products,
        ];
    }

    private function uniqueTicketCode(): string
    {
        do {
            $code = 'CP-' . now()->format('ymd') . '-' . Str::upper(Str::random(8));
        } while (DB::table('ticket')->where('codigo_validacion', $code)->exists());
        return $code;
    }

    private function expirePurchase(int $id): void
    {
        $changed = DB::table('compra')->where('id_compra', $id)->whereIn('estado', ['INICIADA', 'PENDIENTE_PAGO'])->update(['estado' => 'EXPIRADA']);
        if (!$changed) return;
        DB::table('entrada')->where('id_compra', $id)->where('estado', 'RESERVADA')->update(['estado' => 'EXPIRADA', 'cerrada_en' => now()]);
        $purchase = DB::table('compra')->where('id_compra', $id)->lockForUpdate()->first();
        if (!$purchase || !(bool) $purchase->stock_apartado) return;
        DB::table('compra')->where('id_compra', $id)->update(['stock_apartado' => false]);
        foreach (DB::table('detalle_compra')->where('id_compra', $id)->get(['id_producto', 'cantidad']) as $item) {
            $product = DB::table('producto')->where('id_producto', $item->id_producto)->lockForUpdate()->first();
            if (!$product || $product->stock === null) continue;
            $stock = (int) $product->stock + (int) $item->cantidad;
            DB::table('producto')->where('id_producto', $item->id_producto)->update(['stock' => $stock, 'estado' => $product->estado === 'INACTIVO' ? 'INACTIVO' : 'ACTIVO']);
        }
    }
}
