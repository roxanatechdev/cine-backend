<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReembolsoAdminController extends Controller
{
    private const ESTADOS = ['SOLICITADO', 'PROCESANDO', 'COMPLETADO', 'RECHAZADO'];

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:150'],
            'estado' => ['nullable', Rule::in(self::ESTADOS)],
        ]);

        $reembolsos = DB::table('reembolso as r')
            ->join('pago as p', 'p.id_pago', '=', 'r.id_pago')
            ->join('compra as c', 'c.id_compra', '=', 'p.id_compra')
            ->join('comprador as co', 'co.id_comprador', '=', 'c.id_comprador')
            ->when($filtros['estado'] ?? null, fn ($query, $estado) => $query->where('r.estado', $estado))
            ->when($filtros['buscar'] ?? null, function ($query, $buscar) {
                $query->where(function ($subquery) use ($buscar) {
                    if (ctype_digit($buscar)) {
                        $id = (int) $buscar;
                        $subquery->where('r.id_reembolso', $id)
                            ->orWhere('p.id_pago', $id)
                            ->orWhere('c.id_compra', $id);
                    }

                    $subquery->orWhere('co.nombre', 'like', "%{$buscar}%")
                        ->orWhere('co.correo', 'like', "%{$buscar}%")
                        ->orWhere('r.referencia_externa', 'like', "%{$buscar}%");
                });
            })
            ->select([
                'r.id_reembolso', 'r.id_pago', 'r.importe', 'r.motivo', 'r.estado',
                'r.solicitado_en', 'r.completado_en', 'r.referencia_externa',
                'p.id_compra', 'p.estado as pago_estado', 'p.importe as pago_importe',
                'c.moneda', 'co.nombre as comprador_nombre', 'co.correo as comprador_correo',
            ])
            ->orderByDesc('r.solicitado_en')
            ->orderByDesc('r.id_reembolso')
            ->paginate(15)
            ->withQueryString();

        return view('admin.reembolsos.index', compact('reembolsos', 'filtros'));
    }

    public function create(string $pagoId)
    {
        $pago = $this->pagoDetalle($pagoId);
        abort_if(!$pago, 404);
        abort_unless($pago->estado === 'APROBADO', 403, 'Solo se puede solicitar un reembolso para un pago aprobado.');

        $disponible = $this->importeDisponible((int) $pago->id_pago, (string) $pago->importe);

        return view('admin.reembolsos.create', compact('pago', 'disponible'));
    }

    public function store(Request $request, string $pagoId)
    {
        $datos = $request->validate([
            'importe' => ['required', 'numeric', 'min:0.01', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        $id = DB::transaction(function () use ($pagoId, $datos) {
                $pago = DB::table('pago')->where('id_pago', $pagoId)->lockForUpdate()->first();
                if (!$pago) {
                    abort(404);
                }

                if ($pago->estado !== 'APROBADO') {
                    throw ValidationException::withMessages(['pago' => 'Solo se puede solicitar un reembolso para un pago APROBADO.']);
                }

                $solicitadoCentavos = (int) round((float) $datos['importe'] * 100);
                $pagadoCentavos = (int) round((float) $pago->importe * 100);
                $comprometidoCentavos = (int) round((float) DB::table('reembolso')
                    ->where('id_pago', $pago->id_pago)
                    ->whereIn('estado', ['SOLICITADO', 'PROCESANDO', 'COMPLETADO'])
                    ->sum('importe') * 100);

                if ($solicitadoCentavos <= 0 || $solicitadoCentavos + $comprometidoCentavos > $pagadoCentavos) {
                    throw ValidationException::withMessages(['importe' => 'El importe supera el saldo reembolsable del pago.']);
                }

                return DB::table('reembolso')->insertGetId([
                    'id_pago' => $pago->id_pago,
                    'importe' => number_format($solicitadoCentavos / 100, 2, '.', ''),
                    'motivo' => $datos['motivo'] ?? null,
                    'estado' => 'SOLICITADO',
                    'solicitado_en' => now(),
                    'completado_en' => null,
                    'referencia_externa' => null,
                ], 'id_reembolso');
        });

        return redirect()->route('admin.reembolsos.show', $id)
            ->with('success', 'Solicitud de reembolso registrada. Aún no representa una devolución de dinero ejecutada.');
    }

    public function show(string $reembolsoId)
    {
        $reembolso = $this->reembolsoDetalle($reembolsoId);
        abort_if(!$reembolso, 404);

        $disponible = $this->importeDisponible((int) $reembolso->id_pago, (string) $reembolso->pago_importe);
        $transiciones = match ($reembolso->estado) {
            'SOLICITADO' => ['PROCESANDO', 'RECHAZADO'],
            'PROCESANDO' => ['COMPLETADO', 'RECHAZADO'],
            default => [],
        };

        return view('admin.reembolsos.show', compact('reembolso', 'disponible', 'transiciones'));
    }

    public function updateEstado(Request $request, string $reembolsoId)
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in(self::ESTADOS)],
            'referencia_externa' => ['nullable', 'string', 'max:150', Rule::unique('reembolso', 'referencia_externa')->ignore($reembolsoId, 'id_reembolso')],
        ]);

        DB::transaction(function () use ($datos, $reembolsoId) {
            $reembolso = DB::table('reembolso')->where('id_reembolso', $reembolsoId)->lockForUpdate()->first();
            if (!$reembolso) {
                abort(404);
            }

            $permitidas = match ($reembolso->estado) {
                'SOLICITADO' => ['PROCESANDO', 'RECHAZADO'],
                'PROCESANDO' => ['COMPLETADO', 'RECHAZADO'],
                default => [],
            };

            if (!in_array($datos['estado'], $permitidas, true)) {
                throw ValidationException::withMessages(['estado' => 'La transición solicitada no está permitida para el estado actual.']);
            }

            $actualizacion = ['estado' => $datos['estado']];
            if ($datos['estado'] === 'COMPLETADO') {
                $referencia = trim((string) ($datos['referencia_externa'] ?? ''));
                if ($referencia === '') {
                    throw ValidationException::withMessages(['referencia_externa' => 'La referencia externa es obligatoria al marcar el reembolso como COMPLETADO.']);
                }

                $pago = DB::table('pago')->where('id_pago', $reembolso->id_pago)->lockForUpdate()->first();
                if (!$pago || $pago->estado !== 'APROBADO') {
                    throw ValidationException::withMessages(['estado' => 'El pago asociado debe seguir APROBADO para completar el reembolso.']);
                }

                $actualizacion['referencia_externa'] = $referencia;
                $actualizacion['completado_en'] = now();
            } elseif ($datos['estado'] === 'RECHAZADO') {
                $actualizacion['completado_en'] = null;
            }

            DB::table('reembolso')->where('id_reembolso', $reembolsoId)->update($actualizacion);
        });

        return redirect()->route('admin.reembolsos.show', $reembolsoId)
            ->with('success', 'Estado del reembolso actualizado. Verifica la operación con el proveedor de pagos antes de registrar COMPLETADO.');
    }

    private function pagoDetalle(string $pagoId): ?object
    {
        return DB::table('pago as p')
            ->join('compra as c', 'c.id_compra', '=', 'p.id_compra')
            ->join('comprador as co', 'co.id_comprador', '=', 'c.id_comprador')
            ->where('p.id_pago', $pagoId)
            ->select(['p.*', 'c.moneda', 'c.estado as compra_estado', 'co.nombre as comprador_nombre', 'co.correo as comprador_correo'])
            ->first();
    }

    private function reembolsoDetalle(string $reembolsoId): ?object
    {
        return DB::table('reembolso as r')
            ->join('pago as p', 'p.id_pago', '=', 'r.id_pago')
            ->join('compra as c', 'c.id_compra', '=', 'p.id_compra')
            ->join('comprador as co', 'co.id_comprador', '=', 'c.id_comprador')
            ->where('r.id_reembolso', $reembolsoId)
            ->select([
                'r.*', 'p.id_compra', 'p.metodo', 'p.estado as pago_estado',
                'p.importe as pago_importe', 'p.referencia_externa as pago_referencia',
                'c.moneda', 'c.estado as compra_estado', 'co.nombre as comprador_nombre',
                'co.correo as comprador_correo',
            ])
            ->first();
    }

    private function importeDisponible(int $pagoId, string $importePago): string
    {
        $comprometido = DB::table('reembolso')
            ->where('id_pago', $pagoId)
            ->whereIn('estado', ['SOLICITADO', 'PROCESANDO', 'COMPLETADO'])
            ->sum('importe');

        $centavos = max(0, (int) round((float) $importePago * 100) - (int) round((float) $comprometido * 100));

        return number_format($centavos / 100, 2, '.', '');
    }
}
