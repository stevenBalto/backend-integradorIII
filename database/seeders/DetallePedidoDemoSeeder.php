<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Siembra lineas de detalle_pedido para los pedidos existentes.
 *
 * Crea un catalogo minimo (categorias + productos) si no existe, y luego
 * genera lineas de detalle para cada pedido demo existente, de forma que
 * top_productos y ventas_por_categoria de analiticas tengan datos.
 *
 * Re-ejecutable: limpia detalle_pedido antes de sembrar.
 * USO: php artisan db:seed --class=DetallePedidoDemoSeeder
 */
class DetallePedidoDemoSeeder extends Seeder
{
    private const INSTANCIA_ID = 1;

    public function run(): void
    {
        DB::transaction(function (): void {
            // 1. Asegurar que existan categorias y productos
            $categorias = $this->asegurarCategorias();
            $productos = $this->asegurarProductos($categorias);

            // 2. Limpiar detalle_pedido existente
            $pedidoIds = Pedido::withoutGlobalScopes()
                ->where('instancia_id', self::INSTANCIA_ID)
                ->pluck('id');

            DB::table('detalle_pedido')->whereIn('pedido_id', $pedidoIds)->delete();

            // 3. Crear lineas de detalle para cada pedido
            $lineasCreadas = $this->sembrarDetalles($pedidoIds, $productos);

            $this->command?->info("DetallePedidoDemoSeeder completado.");
            $this->command?->info("  - Pedidos procesados: {$pedidoIds->count()}");
            $this->command?->info("  - Lineas de detalle creadas: {$lineasCreadas}");
        });
    }

    /**
     * Asegura que existan 4 categorias basicas.
     * @return array<string, Categoria>
     */
    private function asegurarCategorias(): array
    {
        $defs = [
            ['nombre' => 'Pizza', 'descripcion' => 'Pizzas del menu', 'orden' => 1],
            ['nombre' => 'Grill', 'descripcion' => 'Cortes de carne a la parrilla', 'orden' => 2],
            ['nombre' => 'Pastas', 'descripcion' => 'Pastas de la casa', 'orden' => 3],
            ['nombre' => 'Bebidas', 'descripcion' => 'Bebidas frias', 'orden' => 4],
        ];

        $resultado = [];
        foreach ($defs as $def) {
            $cat = Categoria::withoutGlobalScopes()
                ->where('instancia_id', self::INSTANCIA_ID)
                ->where('nombre', $def['nombre'])
                ->first();

            if (! $cat) {
                $cat = new Categoria();
                $cat->nombre = $def['nombre'];
                $cat->descripcion = $def['descripcion'];
                $cat->orden = $def['orden'];
                $cat->activa = true;
                $cat->instancia_id = self::INSTANCIA_ID;
                $cat->save();
            }

            $resultado[$def['nombre']] = $cat;
        }

        return $resultado;
    }

    /**
     * Asegura que existan productos basicos en cada categoria.
     * @return array<string, Producto>
     */
    private function asegurarProductos(array $categorias): array
    {
        $defs = [
            // Pizzas (precio base = precio mediana tipico)
            ['categoria' => 'Pizza', 'nombre' => 'Pizza Hawaiana', 'precio' => 9500],
            ['categoria' => 'Pizza', 'nombre' => 'Pizza Pepperoni', 'precio' => 10000],
            ['categoria' => 'Pizza', 'nombre' => 'Pizza Margarita', 'precio' => 8800],

            // Grill
            ['categoria' => 'Grill', 'nombre' => 'Churrasco', 'precio' => 7500],
            ['categoria' => 'Grill', 'nombre' => 'Costillas BBQ', 'precio' => 8200],
            ['categoria' => 'Grill', 'nombre' => 'Lomito a la parrilla', 'precio' => 9800],

            // Pastas
            ['categoria' => 'Pastas', 'nombre' => 'Fettuccine Alfredo', 'precio' => 6500],
            ['categoria' => 'Pastas', 'nombre' => 'Espagueti a la Bolonesa', 'precio' => 6000],

            // Bebidas
            ['categoria' => 'Bebidas', 'nombre' => 'Coca-Cola 600ml', 'precio' => 1500],
            ['categoria' => 'Bebidas', 'nombre' => 'Agua embotellada', 'precio' => 1000],
            ['categoria' => 'Bebidas', 'nombre' => 'Limonada natural', 'precio' => 1800],
        ];

        $resultado = [];
        foreach ($defs as $def) {
            $prod = Producto::withoutGlobalScope('instancia')
                ->where('instancia_id', self::INSTANCIA_ID)
                ->where('nombre', $def['nombre'])
                ->first();

            if (! $prod) {
                $prod = new Producto();
                $prod->instancia_id = self::INSTANCIA_ID;
                $prod->categoria_id = $categorias[$def['categoria']]->id;
                $prod->nombre = $def['nombre'];
                $prod->precio_base = $def['precio'];
                $prod->disponible = true;
                $prod->destacado = false;
                $prod->save();
            }

            $resultado[$def['nombre']] = $prod;
        }

        return $resultado;
    }

    /**
     * Crea lineas de detalle_pedido para cada pedido, usando productos aleatorios.
     */
    private function sembrarDetalles($pedidoIds, array $productos): int
    {
        $productosArr = array_values($productos);
        $numProductos = count($productosArr);
        $lineasCreadas = 0;

        foreach ($pedidoIds as $pedidoId) {
            // Cada pedido tendra entre 1 y 4 lineas
            $numLineas = random_int(1, 4);
            $subtotalAcumulado = 0.0;

            // Obtener el total del pedido para ajustar precios si es posible
            $pedido = Pedido::withoutGlobalScopes()->find($pedidoId);
            $totalPedido = (float) ($pedido->total ?? 10000);

            for ($i = 0; $i < $numLineas; $i++) {
                $producto = $productosArr[random_int(0, $numProductos - 1)];
                $cantidad = random_int(1, 3);
                $precioUnitario = (float) $producto->precio_base;
                $subtotal = $cantidad * $precioUnitario;
                $subtotalAcumulado += $subtotal;

                // La tabla real de esta BD solo tiene: id, pedido_id, producto_id,
                // cantidad, precio_unitario, subtotal, notas.
                DB::table('detalle_pedido')->insert([
                    'pedido_id' => $pedidoId,
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'subtotal' => $subtotal,
                    'notas' => null,
                ]);

                $lineasCreadas++;
            }

            // Opcional: ajustar el total del pedido al subtotal real si la diferencia
            // es muy grande (solo para consistencia visual, no es critico para demo).
            // Por simplicidad lo dejamos como esta: el total original puede no cuadrar
            // exactamente pero es aceptable para datos de demo.
        }

        return $lineasCreadas;
    }
}
