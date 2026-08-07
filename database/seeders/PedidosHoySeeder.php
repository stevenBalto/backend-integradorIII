<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 15 pedidos de PRUEBA fechados HOY, de todo tipo (estados, modalidades, pago,
 * registrado/invitado) para ver el dashboard "Últimos pedidos del día" y la lista de
 * Pedidos con datos. Idempotente: borra los de demo previos (código HOY-%) antes.
 * Correr: php artisan db:seed --class=PedidosHoySeeder
 */
final class PedidosHoySeeder extends Seeder
{
    private const INSTANCIA_ID = 1;
    private const SUCURSAL_ID = 1;
    private const INVITADO_ID = 37;

    public function run(): void
    {
        // Limpieza de demo previos (código HOY-*) con sus dependencias.
        $viejos = DB::table('pedidos')
            ->where('instancia_id', self::INSTANCIA_ID)
            ->where('codigo', 'like', 'HOY-%')
            ->pluck('id');
        if ($viejos->isNotEmpty()) {
            $det = DB::table('detalle_pedido')->whereIn('pedido_id', $viejos)->pluck('id');
            DB::table('detalle_pedido_extras')->whereIn('detalle_pedido_id', $det)->delete();
            DB::table('detalle_pedido')->whereIn('pedido_id', $viejos)->delete();
            DB::table('pedido_historial_estado')->whereIn('pedido_id', $viejos)->delete();
            DB::table('pedidos')->whereIn('id', $viejos)->delete();
        }

        $prod = [39, 48, 49, 1, 50, 51];        // productos existentes (instancia 1)
        $reg = [35, 8, 9, 10];                   // clientes registrados
        $nombresReg = ['Christian Paniagua', 'Laura Vargas', 'Diego Mora', 'Sofía Alfaro'];
        $nombresInv = ['Invitado — Kevin', 'Invitado — Mesa 4', 'Invitado — Andrea', 'Invitado — Retiro'];

        // [estado, modalidad, pagado, invitado, [ [producto, cantidad, precio], ... ]]
        $defs = [
            ['pendiente',  'llevar',     false, false, [[39, 1, 6500]]],
            ['pendiente',  'comer_aqui', false, true,  [[48, 2, 3200]]],
            ['en_proceso', 'comer_aqui', false, false, [[49, 1, 5400], [50, 1, 1500]]],
            ['en_proceso', 'llevar',     false, false, [[1, 1, 4200]]],
            ['listo',      'comer_aqui', false, false, [[50, 3, 1500]]],
            ['listo',      'llevar',     false, true,  [[39, 1, 6500], [51, 1, 2500]]],
            ['entregado',  'comer_aqui', true,  false, [[48, 1, 3200], [49, 1, 5400]]],
            ['entregado',  'llevar',     true,  false, [[1, 2, 4200]]],
            ['entregado',  'comer_aqui', true,  true,  [[50, 2, 1500]]],
            ['cancelado',  'llevar',     false, false, [[39, 1, 6500]]],
            ['pendiente',  'comer_aqui', true,  false, [[49, 1, 5400]]],
            ['en_proceso', 'llevar',     false, true,  [[48, 1, 3200], [50, 1, 1500]]],
            ['listo',      'comer_aqui', true,  false, [[1, 1, 4200], [51, 2, 2500]]],
            ['entregado',  'llevar',     true,  false, [[39, 1, 6500]]],
            ['cancelado',  'comer_aqui', false, true,  [[48, 1, 3200]]],
        ];

        foreach ($defs as $i => $d) {
            [$estado, $modalidad, $pagado, $invitado, $items] = $d;

            $subtotal = 0.0;
            foreach ($items as $it) {
                $subtotal += $it[1] * $it[2];
            }
            $total = $subtotal; // sin descuento en la demo

            // Fecha: repartida en el día de HOY, siempre dentro del día y en el pasado.
            $ts = Carbon::now()->subMinutes($i * 12);
            if ($ts->lt(Carbon::today())) {
                $ts = Carbon::today()->copy()->addMinutes($i);
            }

            $pedidoId = DB::table('pedidos')->insertGetId([
                'instancia_id' => self::INSTANCIA_ID,
                'sucursal_id' => self::SUCURSAL_ID,
                'cliente_id' => $invitado ? self::INVITADO_ID : $reg[$i % count($reg)],
                'cupon_id' => null,
                'modalidad' => $modalidad,
                'estado' => $estado,
                'subtotal' => $subtotal,
                'descuento' => 0,
                'total' => $total,
                'puntos_ganados' => $pagado ? (int) floor($total * 0.05) : 0,
                'notas' => null,
                'nombre_cliente' => $invitado
                    ? $nombresInv[$i % count($nombresInv)]
                    : $nombresReg[$i % count($nombresReg)],
                'codigo' => sprintf('HOY-%02d', $i + 1),
                'pagado' => $pagado,
                'pagado_en' => $pagado ? $ts : null,
                'resena_descartada' => false,
                'created_at' => $ts,
                'updated_at' => $ts,
            ]);

            $filas = [];
            foreach ($items as $it) {
                $filas[] = [
                    'pedido_id' => $pedidoId,
                    'producto_id' => $it[0],
                    'cantidad' => $it[1],
                    'precio_unitario' => $it[2],
                    'subtotal' => $it[1] * $it[2],
                    'notas' => null,
                    'producto_tamano_id' => null,
                    'tamano_nombre' => null,
                ];
            }
            DB::table('detalle_pedido')->insert($filas);
        }

        $this->command?->info('PedidosHoySeeder: ' . count($defs) . ' pedidos de HOY (variados) en instancia ' . self::INSTANCIA_ID . '.');
    }
}
