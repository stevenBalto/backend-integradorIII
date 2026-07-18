<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Pedido;
use App\Models\Role;
use App\Models\Sucursal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder de datos de prueba para el modulo admin "Clientes".
 *
 * Crea clientes falsos con pedidos distribuidos en los ultimos 6 meses
 * para probar la analitica de compra (gasto total, cantidad de pedidos,
 * ticket promedio, ultimo pedido).
 *
 * USO: php artisan db:seed --class=ClientesDemoSeeder
 *
 * Re-ejecutable: usa updateOrCreate para clientes y borra pedidos previos
 * de clientes demo antes de crear nuevos.
 */
class ClientesDemoSeeder extends Seeder
{
    private const INSTANCIA_ID = 1;
    private const NUM_CLIENTES = 15;
    private const EMAIL_PATTERN = 'cliente-demo-%d@rooster-test.com';

    /** Estados validos segun el modelo Pedido */
    private const ESTADOS = ['pendiente', 'en_proceso', 'listo', 'entregado', 'cancelado'];

    /** Modalidades validas */
    private const MODALIDADES = ['para_llevar', 'comer_aqui'];

    public function run(): void
    {
        $faker = fake('es_ES'); // es_CR no existe en Faker, es_ES es lo mas cercano

        // 1. Asegurar que exista al menos una sucursal
        $sucursal = $this->ensureSucursalExists($faker);

        // 2. Obtener el rol "cliente"
        $rolCliente = Role::where('nombre', 'cliente')->first();
        if (! $rolCliente) {
            $this->command?->error('No se encontro el rol "cliente". Abortando.');
            return;
        }

        // 3. Crear/actualizar clientes demo
        $clientesDemo = $this->crearClientesDemo($faker, $rolCliente->id);

        // 4. Limpiar pedidos previos de clientes demo (para re-ejecutabilidad)
        $clienteIds = $clientesDemo->pluck('id')->toArray();
        $pedidosBorrados = Pedido::withoutGlobalScopes()
            ->whereIn('cliente_id', $clienteIds)
            ->delete();

        if ($pedidosBorrados > 0) {
            $this->command?->info("Eliminados {$pedidosBorrados} pedidos previos de clientes demo.");
        }

        // 5. Crear pedidos falsos
        $totalPedidos = $this->crearPedidosFalsos($clientesDemo, $sucursal->id);

        // 6. Resumen
        $this->command?->info("--------------------------------------------------");
        $this->command?->info("ClientesDemoSeeder completado:");
        $this->command?->info("  - Clientes demo creados/actualizados: {$clientesDemo->count()}");
        $this->command?->info("  - Pedidos creados: {$totalPedidos}");
        $this->command?->info("  - Sucursal usada: {$sucursal->nombre} (ID: {$sucursal->id})");
        $this->command?->info("--------------------------------------------------");
    }

    /**
     * Asegura que exista al menos una sucursal; crea una si no hay ninguna.
     */
    private function ensureSucursalExists($faker): Sucursal
    {
        $sucursal = Sucursal::withoutGlobalScopes()->first();

        if ($sucursal) {
            return $sucursal;
        }

        // Crear sucursal semilla usando asignacion directa (instancia_id no esta en fillable)
        $sucursal = new Sucursal();
        $sucursal->nombre = 'La Fortuna (Centro)';
        $sucursal->direccion = 'Diagonal a la Iglesia Catolica, La Fortuna de San Carlos, Alajuela';
        $sucursal->telefono = '2479-' . $faker->numberBetween(1000, 9999);
        $sucursal->latitud = 10.4679;
        $sucursal->longitud = -84.6434;
        $sucursal->activa = true;
        $sucursal->instancia_id = self::INSTANCIA_ID;
        $sucursal->save();

        $this->command?->info("Sucursal semilla creada: {$sucursal->nombre}");

        return $sucursal;
    }

    /**
     * Crea o actualiza 15 clientes demo con emails distinguibles.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function crearClientesDemo($faker, int $rolClienteId): \Illuminate\Support\Collection
    {
        $clientes = collect();

        // Nombres costarricenses realistas (mezcla de nombres comunes en CR)
        $nombresCR = [
            'Jose', 'Maria', 'Carlos', 'Ana', 'Luis', 'Laura', 'Juan', 'Sofia',
            'Diego', 'Valeria', 'Andres', 'Camila', 'Daniel', 'Isabella', 'Kevin',
        ];
        $apellidosCR = [
            'Rodriguez', 'Gonzalez', 'Hernandez', 'Vargas', 'Jimenez', 'Mora',
            'Araya', 'Rojas', 'Solis', 'Castro', 'Ramirez', 'Chaves', 'Arias',
            'Sanchez', 'Quesada',
        ];

        for ($i = 1; $i <= self::NUM_CLIENTES; $i++) {
            $email = sprintf(self::EMAIL_PATTERN, $i);
            $nombre = $nombresCR[($i - 1) % count($nombresCR)];
            $apellido = $apellidosCR[($i - 1) % count($apellidosCR)];
            $nombreCompleto = "{$nombre} {$apellido}";

            // Usar updateOrCreate para idempotencia
            $user = User::withoutGlobalScopes()->updateOrCreate(
                ['email' => $email],
                [
                    'role_id' => $rolClienteId,
                    'instancia_id' => self::INSTANCIA_ID,
                    'nombre' => $nombreCompleto,
                    'usuario' => "demo{$i}",
                    'password' => 'demo1234', // se hashea automaticamente
                    'telefono' => '8' . $faker->numberBetween(100, 999) . '-' . $faker->numberBetween(1000, 9999),
                    'activo' => $i <= 12, // 12 activos, 3 inactivos
                ]
            );

            // puntos_balance no esta en fillable, asignar post-create
            $user->puntos_balance = $faker->numberBetween(0, 500);
            $user->save();

            $clientes->push($user);
        }

        return $clientes;
    }

    /**
     * Crea pedidos falsos con distribucion realista.
     *
     * Algunos clientes tendran muchos pedidos (top compradores),
     * otros tendran pocos o ninguno.
     */
    private function crearPedidosFalsos(\Illuminate\Support\Collection $clientes, int $sucursalId): int
    {
        $faker = fake('es_ES');
        $totalPedidos = 0;
        $ahora = Carbon::now();
        $hace6Meses = $ahora->copy()->subMonths(6);

        // Distribucion de pedidos: algunos clientes son "top compradores"
        // Indices 0-2: muchos pedidos (15-20)
        // Indices 3-6: pedidos moderados (5-10)
        // Indices 7-11: pocos pedidos (1-4)
        // Indices 12-14: sin pedidos (0)

        foreach ($clientes as $index => $cliente) {
            $numPedidos = match (true) {
                $index < 3 => $faker->numberBetween(15, 20),  // Top compradores
                $index < 7 => $faker->numberBetween(5, 10),   // Moderados
                $index < 12 => $faker->numberBetween(1, 4),   // Pocos
                default => 0,                                  // Sin pedidos
            };

            for ($p = 0; $p < $numPedidos; $p++) {
                // Generar fecha aleatoria en los ultimos 6 meses
                $fechaPedido = Carbon::createFromTimestamp(
                    $faker->numberBetween($hace6Meses->timestamp, $ahora->timestamp)
                );

                // Montos realistas para pizzeria costarricense (en colones)
                $subtotal = $faker->numberBetween(3000, 25000);
                $descuento = $faker->boolean(15) ? $faker->numberBetween(100, min(2000, (int) ($subtotal * 0.15))) : 0;
                $total = $subtotal - $descuento;
                $puntosGanados = (int) round($total / 100);

                // Estado: mayoria entregados, algunos cancelados, pocos en proceso
                $estado = $this->generarEstadoRealista($faker);

                // Modalidad aleatoria
                $modalidad = $faker->randomElement(self::MODALIDADES);

                // Crear pedido usando forceFill (fillable esta vacio en Pedido)
                $pedido = new Pedido();
                $pedido->forceFill([
                    'cliente_id' => $cliente->id,
                    'sucursal_id' => $sucursalId,
                    'instancia_id' => self::INSTANCIA_ID,
                    'modalidad' => $modalidad,
                    'estado' => $estado,
                    'subtotal' => $subtotal,
                    'descuento' => $descuento,
                    'total' => $total,
                    'puntos_ganados' => $puntosGanados,
                    'notas' => $faker->boolean(20) ? $faker->sentence() : null,
                    'created_at' => $fechaPedido,
                    'updated_at' => $fechaPedido,
                ]);
                $pedido->save();

                $totalPedidos++;
            }
        }

        return $totalPedidos;
    }

    /**
     * Genera un estado de pedido con distribucion realista.
     */
    private function generarEstadoRealista($faker): string
    {
        $rand = $faker->numberBetween(1, 100);

        return match (true) {
            $rand <= 70 => 'entregado',   // 70% entregados
            $rand <= 80 => 'cancelado',   // 10% cancelados
            $rand <= 90 => 'listo',       // 10% listos
            $rand <= 95 => 'en_proceso',  // 5% en proceso
            default => 'pendiente',        // 5% pendientes
        };
    }
}
