<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/** Endpoints públicos protegidos por token para crear y consultar reservas de invitados. */
class ReservaPublicaController extends Controller
{
    /** Crea una compra de entradas y/o confitería, validando stock y bloqueando asientos cuando corresponda. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'min:2', 'max:100'],
            'correo' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'id_funcion' => ['sometimes', 'nullable', 'integer', 'min:1', 'required_with:asientos'],
            'asientos' => ['sometimes', 'array', 'min:1', 'max:20', 'required_with:id_funcion'],
            'asientos.*' => ['required', 'integer', 'distinct', 'min:1'],
            'productos' => ['sometimes', 'array', 'max:30'],
            'productos.*.id_producto' => ['required', 'integer', 'distinct', 'min:1'],
            'productos.*.cantidad' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $hasSeats = !empty($data['id_funcion']) && !empty($data['asientos']);
        $hasProducts = !empty($data['productos']);
        if (!$hasSeats && !$hasProducts) {
            return response()->json(['message' => 'Añade al menos un asiento de una función o un producto de confitería para crear la compra.'], 422);
        }

        $token = Str::random(64);

        try {
            $reservation = DB::transaction(function () use ($data, $token, $hasSeats) {
                $this->expireDueReservations();

                $functionSeats = collect();
                if ($hasSeats) {
                    $funcion = DB::table('funcion as f')
                        ->join('pelicula as p', 'p.id_pelicula', '=', 'f.id_pelicula')
                        ->join('sala as s', 's.id_sala', '=', 'f.id_sala')
                        ->where('f.id_funcion', $data['id_funcion'])
                        ->where('f.estado', 'PROGRAMADA')
                        ->where('f.inicio', '>', now())
                        ->where('p.estado', 'ACTIVA')
                        ->where('s.estado', 'ACTIVA')
                        ->lockForUpdate()
                        ->select(['f.id_funcion', 'f.id_sala', 'f.inicio'])
                        ->first();

                    if (!$funcion) {
                        throw new ReservationConflict('La función ya no está disponible para reservar. Actualiza la página y selecciona otra función.');
                    }

                    $seatIds = collect($data['asientos'])->map(fn ($id) => (int) $id)->unique()->values();
                    $functionSeats = DB::table('funcion_asiento as fa')
                        ->join('asiento as a', 'a.id_asiento', '=', 'fa.id_asiento')
                        ->where('fa.id_funcion', $funcion->id_funcion)
                        ->whereIn('fa.id_funcion_asiento', $seatIds)
                        ->where('a.id_sala', $funcion->id_sala)
                        ->where('a.activo', 1)
                        ->lockForUpdate()
                        ->select(['fa.id_funcion_asiento', 'fa.precio', 'a.fila', 'a.numero'])
                        ->get();

                    if ($functionSeats->count() !== $seatIds->count()) {
                        throw new ReservationConflict('Uno o más asientos no pertenecen a esta función o ya no están disponibles. Actualiza el mapa de asientos.');
                    }

                    $occupied = DB::table('entrada as e')
                        ->join('compra as c', 'c.id_compra', '=', 'e.id_compra')
                        ->whereIn('e.id_funcion_asiento', $seatIds)
                        ->where(function ($query) {
                            $query->whereIn('e.estado', ['CONFIRMADA', 'UTILIZADA'])
                                ->orWhere(function ($reserved) {
                                    $reserved->where('e.estado', 'RESERVADA')
                                        ->whereIn('c.estado', ['INICIADA', 'PENDIENTE_PAGO'])
                                        ->where(function ($expiry) {
                                            $expiry->whereNull('c.vence_reserva')->orWhere('c.vence_reserva', '>', now());
                                        });
                                });
                        })
                        ->lockForUpdate()
                        ->pluck('e.id_funcion_asiento')
                        ->map(fn ($id) => (int) $id)
                        ->all();

                    if ($occupied !== []) {
                        throw new ReservationConflict('Lo sentimos, otro cliente acaba de reservar uno o más de los asientos seleccionados. Vuelve a elegir los asientos disponibles.');
                    }
                }

                $requestedProducts = collect($data['productos'] ?? [])
                    ->map(fn ($item) => ['id_producto' => (int) $item['id_producto'], 'cantidad' => (int) $item['cantidad']])
                    ->keyBy('id_producto');
                $productRows = collect();

                if ($requestedProducts->isNotEmpty()) {
                    $productRows = DB::table('producto')
                        ->whereIn('id_producto', $requestedProducts->keys())
                        ->where('estado', 'ACTIVO')
                        ->orderBy('id_producto')
                        ->lockForUpdate()
                        ->get();

                    if ($productRows->count() !== $requestedProducts->count()) {
                        throw new ReservationConflict('Uno o más productos ya no están disponibles. Actualiza el catálogo de confitería.');
                    }

                    foreach ($productRows as $product) {
                        $quantity = $requestedProducts[(int) $product->id_producto]['cantidad'];
                        if ($product->stock !== null && (int) $product->stock < $quantity) {
                            throw new ReservationConflict("No hay stock suficiente de {$product->nombre}. Ajusta la cantidad e inténtalo de nuevo.");
                        }
                    }
                }

                $now = now();
                $expiresAt = $now->copy()->addMinutes(15);
                $idBuyer = DB::table('comprador')->insertGetId([
                    'nombre' => trim($data['nombre']),
                    'correo' => mb_strtolower(trim($data['correo'])),
                    'telefono' => isset($data['telefono']) && trim($data['telefono']) !== '' ? trim($data['telefono']) : null,
                    'tipo_comprador' => 'INVITADO',
                    'creado_en' => $now,
                ], 'id_comprador');

                $ticketTotal = $functionSeats->sum(fn ($seat) => (float) $seat->precio);
                $productTotal = 0.0;
                $stockApartado = false;
                $frozenProducts = [];

                foreach ($productRows as $product) {
                    $quantity = $requestedProducts[(int) $product->id_producto]['cantidad'];
                    $promoActive = (bool) ($product->es_promocion ?? false)
                        && $product->precio_promocional !== null
                        && (!$product->promocion_inicio || Carbon::parse($product->promocion_inicio)->lte($now))
                        && (!$product->promocion_fin || Carbon::parse($product->promocion_fin)->gte($now));
                    $unitPrice = (float) ($promoActive ? $product->precio_promocional : $product->precio_actual);
                    $productTotal += $unitPrice * $quantity;
                    $frozenProducts[] = ['id_producto' => (int) $product->id_producto, 'cantidad' => $quantity, 'precio_unitario' => number_format($unitPrice, 2, '.', '')];

                    // El stock controlado se aparta durante la reserva. Se devuelve si se cancela o expira.
                    if ($product->stock !== null) {
                        $stockApartado = true;
                        $newStock = (int) $product->stock - $quantity;
                        DB::table('producto')->where('id_producto', $product->id_producto)->update([
                            'stock' => $newStock,
                            'estado' => $newStock === 0 ? 'AGOTADO' : 'ACTIVO',
                        ]);
                    }
                }

                $total = round($ticketTotal + $productTotal, 2);
                if ($total <= 0) {
                    throw new ReservationConflict('El importe total debe ser mayor que cero para continuar con el pago.');
                }
                $idCompra = DB::table('compra')->insertGetId([
                    'id_comprador' => $idBuyer,
                    'creada_en' => $now,
                    'vence_reserva' => $expiresAt,
                    'moneda' => 'BOB',
                    'total' => number_format($total, 2, '.', ''),
                    'estado' => 'PENDIENTE_PAGO',
                    'token_acceso_hash' => hash('sha256', $token),
                    'stock_apartado' => $stockApartado,
                ], 'id_compra');

                foreach ($functionSeats as $seat) {
                    DB::table('entrada')->insert([
                        'id_compra' => $idCompra,
                        'id_funcion_asiento' => $seat->id_funcion_asiento,
                        'precio_venta' => $seat->precio,
                        'estado' => 'RESERVADA',
                        'reservada_en' => $now,
                    ]);
                }

                foreach ($frozenProducts as $item) {
                    DB::table('detalle_compra')->insert([
                        'id_compra' => $idCompra,
                        'id_producto' => $item['id_producto'],
                        'cantidad' => $item['cantidad'],
                        'precio_unitario' => $item['precio_unitario'],
                    ]);
                }

                return [
                    'id_compra' => (int) $idCompra,
                    'token' => $token,
                    'estado' => 'PENDIENTE_PAGO',
                    'creada_en' => $now->toIso8601String(),
                    'vence_reserva' => $expiresAt->toIso8601String(),
                    'segundos_restantes' => 900,
                    'moneda' => 'BOB',
                    'total' => number_format($total, 2, '.', ''),
                    'asientos' => $functionSeats->map(fn ($seat) => [
                        'id_funcion_asiento' => (int) $seat->id_funcion_asiento,
                        'fila' => (string) $seat->fila,
                        'numero' => (int) $seat->numero,
                        'precio' => (string) $seat->precio,
                    ])->values(),
                    'productos' => collect($frozenProducts)->map(function ($item) use ($productRows) {
                        $product = $productRows->firstWhere('id_producto', $item['id_producto']);
                        return $item + ['nombre' => $product->nombre ?? 'Producto'];
                    })->values(),
                ];
            }, 3);
        } catch (ReservationConflict $exception) {
            return response()->json(['message' => $exception->getMessage(), 'code' => 'RESERVA_NO_DISPONIBLE'], 409);
        }

        return response()->json(['message' => !empty($reservation['asientos']) ? 'Compra creada. El plazo de 15 minutos ya está en curso.' : 'Compra de confitería creada. El plazo de 15 minutos ya está en curso.', 'reserva' => $reservation], 201);
    }

    /** Consulta el estado de una reserva usando el token privado entregado al crearla. */
    public function show(Request $request, int $id): JsonResponse
    {
        $token = (string) $request->header('X-Reserva-Token', '');
        if ($token === '') {
            return response()->json(['message' => 'Falta el token de acceso de la reserva.'], 401);
        }

        return DB::transaction(function () use ($id, $token) {
            $this->expireDueReservations();
            $purchase = DB::table('compra as c')
                ->join('comprador as b', 'b.id_comprador', '=', 'c.id_comprador')
                ->where('c.id_compra', $id)
                ->lockForUpdate()
                ->select(['c.*', 'b.nombre as comprador_nombre', 'b.correo as comprador_correo'])
                ->first();

            if (!$purchase || !hash_equals((string) $purchase->token_acceso_hash, hash('sha256', $token))) {
                return response()->json(['message' => 'No se encontró la reserva o el token no es válido.'], 404);
            }

            $entries = DB::table('entrada as e')
                ->join('funcion_asiento as fa', 'fa.id_funcion_asiento', '=', 'e.id_funcion_asiento')
                ->join('asiento as a', 'a.id_asiento', '=', 'fa.id_asiento')
                ->where('e.id_compra', $id)
                ->get(['e.id_funcion_asiento', 'e.precio_venta', 'e.estado as estado_entrada', 'a.fila', 'a.numero']);
            $products = DB::table('detalle_compra as d')
                ->join('producto as p', 'p.id_producto', '=', 'd.id_producto')
                ->where('d.id_compra', $id)
                ->get(['d.id_producto', 'p.nombre', 'd.cantidad', 'd.precio_unitario']);
            $function = DB::table('entrada as e')
                ->join('funcion_asiento as fa', 'fa.id_funcion_asiento', '=', 'e.id_funcion_asiento')
                ->join('funcion as f', 'f.id_funcion', '=', 'fa.id_funcion')
                ->join('pelicula as p', 'p.id_pelicula', '=', 'f.id_pelicula')
                ->join('sala as s', 's.id_sala', '=', 'f.id_sala')
                ->where('e.id_compra', $id)
                ->select(['f.id_funcion', 'f.inicio', 'f.formato', 'p.id_pelicula', 'p.titulo', 'p.duracion_minutos', 'p.clasificacion', 's.nombre as sala'])
                ->first();
            $expiresAt = $purchase->vence_reserva ? Carbon::parse($purchase->vence_reserva) : null;

            return response()->json(['reserva' => [
                'id_compra' => (int) $purchase->id_compra,
                'estado' => (string) $purchase->estado,
                'creada_en' => Carbon::parse($purchase->creada_en)->toIso8601String(),
                'vence_reserva' => $expiresAt?->toIso8601String(),
                'segundos_restantes' => $expiresAt && $expiresAt->isFuture() ? now()->diffInSeconds($expiresAt) : 0,
                'moneda' => (string) $purchase->moneda,
                'total' => (string) $purchase->total,
                'comprador' => ['nombre' => $purchase->comprador_nombre, 'correo' => $purchase->comprador_correo],
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
                'asientos' => $entries->map(fn ($entry) => ['id_funcion_asiento' => (int) $entry->id_funcion_asiento, 'fila' => (string) $entry->fila, 'numero' => (int) $entry->numero, 'precio' => (string) $entry->precio_venta, 'estado' => (string) $entry->estado_entrada])->values(),
                'productos' => $products->map(fn ($product) => ['id_producto' => (int) $product->id_producto, 'nombre' => (string) $product->nombre, 'cantidad' => (int) $product->cantidad, 'precio_unitario' => (string) $product->precio_unitario])->values(),
            ]]);
        });
    }

    /** Cancela una reserva de invitado y devuelve el stock apartado de confitería. */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $token = (string) $request->header('X-Reserva-Token', '');
        if ($token === '') {
            return response()->json(['message' => 'Falta el token de acceso de la reserva.'], 401);
        }

        return DB::transaction(function () use ($id, $token) {
            $purchase = DB::table('compra')->where('id_compra', $id)->lockForUpdate()->first();
            if (!$purchase || !hash_equals((string) $purchase->token_acceso_hash, hash('sha256', $token))) {
                return response()->json(['message' => 'No se encontró la reserva o el token no es válido.'], 404);
            }
            if (!in_array($purchase->estado, ['INICIADA', 'PENDIENTE_PAGO'], true)) {
                return response()->json(['message' => 'Esta reserva ya no se puede cancelar desde el flujo público.', 'estado' => $purchase->estado], 409);
            }
            if (DB::table('pago')->where('id_compra', $id)->where('estado', 'APROBADO')->exists()) {
                return response()->json(['message' => 'La reserva tiene un pago aprobado y no se puede cancelar desde este flujo.', 'estado' => $purchase->estado], 409);
            }
            if ($purchase->vence_reserva && Carbon::parse($purchase->vence_reserva)->isPast()) {
                $this->expirePurchase((int) $purchase->id_compra);
                return response()->json(['message' => 'La reserva ya había vencido.', 'estado' => 'EXPIRADA'], 409);
            }

            DB::table('compra')->where('id_compra', $id)->update([
                'estado' => 'CANCELADA', 'cancelada_en' => now(), 'motivo_cancelacion' => 'Cancelada por el cliente',
            ]);
            DB::table('entrada')->where('id_compra', $id)->where('estado', 'RESERVADA')->update(['estado' => 'CANCELADA', 'cerrada_en' => now()]);
            $this->restoreProductStock((int) $id);

            return response()->json(['message' => 'Reserva cancelada.', 'estado' => 'CANCELADA']);
        });
    }

    /** Marca reservas vencidas y libera entradas y stock; se ejecuta de forma diferida y segura. */
    private function expireDueReservations(): void
    {
        $due = DB::table('compra')
            ->whereIn('estado', ['INICIADA', 'PENDIENTE_PAGO'])
            ->whereNotNull('vence_reserva')
            ->where('vence_reserva', '<=', now())
            ->orderBy('id_compra')
            ->lockForUpdate()
            ->pluck('id_compra');

        foreach ($due as $id) {
            if (DB::table('pago')->where('id_compra', $id)->where('estado', 'APROBADO')->exists()) {
                continue;
            }
            $this->expirePurchase((int) $id);
        }
    }

    private function expirePurchase(int $id): void
    {
        $changed = DB::table('compra')->where('id_compra', $id)->whereIn('estado', ['INICIADA', 'PENDIENTE_PAGO'])->update(['estado' => 'EXPIRADA']);
        if (!$changed) return;
        DB::table('entrada')->where('id_compra', $id)->where('estado', 'RESERVADA')->update(['estado' => 'EXPIRADA', 'cerrada_en' => now()]);
        $this->restoreProductStock($id);
    }

    private function restoreProductStock(int $id): void
    {
        $purchase = DB::table('compra')->where('id_compra', $id)->lockForUpdate()->first();
        if (!$purchase || !(bool) $purchase->stock_apartado) return;

        // La bandera se apaga primero para que la liberación sea idempotente.
        DB::table('compra')->where('id_compra', $id)->update(['stock_apartado' => false]);
        $items = DB::table('detalle_compra')->where('id_compra', $id)->get(['id_producto', 'cantidad']);
        foreach ($items as $item) {
            $product = DB::table('producto')->where('id_producto', $item->id_producto)->lockForUpdate()->first();
            if (!$product || $product->stock === null) continue;
            $newStock = (int) $product->stock + (int) $item->cantidad;
            DB::table('producto')->where('id_producto', $item->id_producto)->update([
                'stock' => $newStock,
                'estado' => $product->estado === 'INACTIVO' ? 'INACTIVO' : 'ACTIVO',
            ]);
        }
    }
}

/** Conflicto de disponibilidad esperado: se devuelve como HTTP 409 y no como error interno. */
class ReservationConflict extends RuntimeException
{
}
