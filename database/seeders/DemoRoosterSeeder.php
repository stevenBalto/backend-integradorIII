<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\DTOs\Pedido\CrearPedidoDTO;
use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\Cupon;
use App\Models\Extra;
use App\Models\Oferta;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoTamano;
use App\Models\Role;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\PedidoService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Prepara datos de demo realistas para instancia_id=1 (Rooster Pizza & Grill)
 * antes de una presentacion al dueno del producto.
 *
 * Limpia pedidos y usuarios de prueba de sesiones anteriores (respetando una
 * lista de cuentas protegidas que nunca se tocan) y siembra catalogo + pedidos
 * con historial mediante el flujo real (PedidoService), para que codigo,
 * puntos e historial queden exactamente igual que si vinieran de la app.
 *
 * Re-ejecutable: vuelve a limpiar antes de sembrar.
 * USO: php artisan db:seed --class=DemoRoosterSeeder
 */
class DemoRoosterSeeder extends Seeder
{
    private const INSTANCIA_ID = 1;

    /** Cuentas reales que jamas se tocan aunque coincidan con un patron de limpieza. */
    private const EMAILS_PROTEGIDOS = [
        'crisp314528@gmail.com',
        'admin@rooster.com',
    ];

    private PedidoService $pedidoService;

    public function run(): void
    {
        $this->pedidoService = app(PedidoService::class);

        DB::transaction(function (): void {
            $this->limpiar();
            $sucursal = $this->sembrarSucursales();
            $categorias = $this->sembrarCategorias();
            $productos = $this->sembrarProductos($categorias);
            $this->sembrarExtras($categorias);
            $this->sembrarOfertas($productos);
            $this->sembrarCupones();
            $this->sembrarAdminSede($sucursal);
            $this->sembrarClientes();
            $this->sembrarUsuarioInvitado();
            // Los pedidos NO se siembran: el usuario los crea a mano desde la app.
            // limpiar() deja la tabla de pedidos de la instancia vacia en cada corrida.
        });

        $this->command?->info('DemoRoosterSeeder completado.');
    }

    // ── Limpieza ──────────────────────────────────────────────────────────

    private function limpiar(): void
    {
        $pedidoIds = Pedido::withoutGlobalScopes()
            ->where('instancia_id', self::INSTANCIA_ID)
            ->pluck('id');

        $detalleIds = DB::table('detalle_pedido')->whereIn('pedido_id', $pedidoIds)->pluck('id');
        DB::table('detalle_pedido_extras')->whereIn('detalle_pedido_id', $detalleIds)->delete();
        DB::table('detalle_pedido')->whereIn('pedido_id', $pedidoIds)->delete();
        DB::table('pedido_historial_estado')->whereIn('pedido_id', $pedidoIds)->delete();
        DB::table('puntos_movimientos')->whereIn('pedido_id', $pedidoIds)->delete();
        Pedido::withoutGlobalScopes()->whereIn('id', $pedidoIds)->delete();

        // Los pedidos de prueba ya no existen: los puntos que dieron tampoco deberian.
        // withoutGlobalScope('instancia') quita SOLO el scope multi-tenant (no hay sesion en
        // un seeder de consola, asi que da igual) y deja intacto el scope de SoftDeletes —
        // withoutGlobalScopes() a secas quitaria AMBOS y traeria de vuelta usuarios ya
        // borrados (soft-delete) en las consultas de abajo.
        User::withoutGlobalScope('instancia')->where('instancia_id', self::INSTANCIA_ID)->update(['puntos_balance' => 0]);

        // Cuentas de prueba sueltas de sesiones anteriores (verif*, test*, etc.) — nunca las protegidas.
        User::withoutGlobalScope('instancia')
            ->where('instancia_id', self::INSTANCIA_ID)
            ->whereNotIn('email', self::EMAILS_PROTEGIDOS)
            ->where(function ($q): void {
                $q->where('email', 'like', 'verif%@rooster-test.com')
                    ->orWhere('email', 'like', '%nuevo-test-plan%')
                    ->orWhere('email', 'like', 'cliente_%@correo.com')
                    ->orWhere('email', 'like', 'cliente-verif-plan%');
            })
            ->delete();

        // Recorta la tanda de ClientesDemoSeeder (15) a un numero razonable: deja demo-1..6.
        User::withoutGlobalScope('instancia')
            ->where('instancia_id', self::INSTANCIA_ID)
            ->where('email', 'like', 'cliente-demo-%@rooster-test.com')
            ->get()
            ->each(function (User $u): void {
                if (preg_match('/cliente-demo-(\d+)@/', $u->email, $m) && (int) $m[1] > 6) {
                    $u->delete();
                }
            });

        // Productos de prueba ya invisibles (soft-deleted) de sesiones anteriores, MAS los
        // productos de categorias que ya no existen (Alitas y Boneless / Postres, reemplazadas
        // por Grill / Pastas): limpieza definitiva. Seguro porque los pedidos que pudieran
        // referenciarlos ya se borraron arriba. Aqui SI hace falta withTrashed(): incluye
        // productos ya soft-deleted de sesiones anteriores y hay que encontrarlos para poder
        // aplicarles el forceDelete.
        Producto::withoutGlobalScope('instancia')
            ->withTrashed()
            ->where('instancia_id', self::INSTANCIA_ID)
            ->whereIn('nombre', [
                'Pizza Verificacion Plan', 'Pizza Test Desc', 'PRUEBA', 'Volcan de chocolate',
                'Alitas BBQ', 'Boneless con papas', 'Volcán de chocolate', 'Tres leches',
            ])
            ->forceDelete();

        // Categorias retiradas (el usuario redefinio el menu a solo Pizza/Grill/Pastas/Bebidas):
        // sus productos ya se borraron arriba, asi que borrar la categoria no viola la FK.
        Categoria::withoutGlobalScope('instancia')
            ->where('instancia_id', self::INSTANCIA_ID)
            ->whereIn('nombre', ['Alitas y Boneless', 'Postres'])
            ->delete();

        // "Pizzas" (plural) se renombra a "Pizza": mismos productos, no se recrean.
        Categoria::withoutGlobalScope('instancia')
            ->where('instancia_id', self::INSTANCIA_ID)
            ->where('nombre', 'Pizzas')
            ->update(['nombre' => 'Pizza']);
    }

    // ── Catalogo ──────────────────────────────────────────────────────────

    /** Garantiza La Fortuna (unica sucursal del negocio) y elimina cualquier resto de Liberia. */
    private function sembrarSucursales(): Sucursal
    {
        $laFortuna = Sucursal::withoutGlobalScopes()
            ->where('instancia_id', self::INSTANCIA_ID)
            ->where('nombre', 'like', '%La Fortuna%')
            ->first() ?? new Sucursal();

        // Contacto/ubicacion siempre actualizados (los consume la pantalla "Restaurantes"
        // de Mi cuenta: direccion, telefono y link a Google Maps con lat/long).
        $laFortuna->nombre = 'Rooster Pizza & Grill - La Fortuna';
        $laFortuna->direccion = 'Diagonal a la Iglesia Católica, La Fortuna de San Carlos, Alajuela';
        $laFortuna->telefono = '2479-1122';
        $laFortuna->latitud = 10.4712;
        $laFortuna->longitud = -84.6455;
        $laFortuna->activa = true;
        $laFortuna->instancia_id = self::INSTANCIA_ID;
        $laFortuna->save();

        // El negocio opera UNA sola sucursal (La Fortuna, que da nombre a la instancia).
        // Se elimina cualquier sucursal Liberia sembrada en sesiones anteriores: los pedidos
        // ya se borraron en limpiar() y users.sucursal_id es ON DELETE SET NULL, asi que el
        // borrado no viola ninguna FK (el admin de sede se reasigna a La Fortuna abajo).
        Sucursal::withoutGlobalScopes()
            ->where('instancia_id', self::INSTANCIA_ID)
            ->where('nombre', 'like', '%Liberia%')
            ->delete();

        return $laFortuna;
    }

    /** @return array<string, Categoria> */
    private function sembrarCategorias(): array
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
                $cat->instancia_id = self::INSTANCIA_ID;
            }

            // orden/descripcion se fuerzan siempre (no solo al crear): "Bebidas" ya existia
            // de antes con su propio orden, y sin esto quedaba pisando el de "Pastas".
            $cat->descripcion = $def['descripcion'];
            $cat->orden = $def['orden'];
            $cat->activa = true;
            $cat->save();

            $resultado[$def['nombre']] = $cat;
        }

        return $resultado;
    }

    /**
     * @param array{nombre: string, precio: float, descripcion?: ?string}[] $tamanos
     */
    private function crearOActualizarProducto(
        Categoria $categoria,
        string $nombre,
        ?string $descripcion,
        float $precioBase,
        bool $destacado,
        array $tamanos,
        ?string $imagenUrl = null,
        bool $popular = false,
        bool $nuevo = false,
    ): Producto {
        $producto = Producto::withoutGlobalScope('instancia')
            ->where('instancia_id', self::INSTANCIA_ID)
            ->where('nombre', $nombre)
            ->first();

        if (! $producto) {
            $producto = new Producto();
            $producto->instancia_id = self::INSTANCIA_ID;
        }

        $producto->categoria_id = $categoria->id;
        $producto->nombre = $nombre;
        $producto->descripcion = $descripcion;
        $producto->precio_base = $precioBase;
        $producto->disponible = true;
        $producto->destacado = $destacado;
        // popular/nuevo se fuerzan siempre (defecto false) para que cada corrida
        // deje las secciones "Populares" y "Lo nuevo" del Home en un estado conocido.
        $producto->popular = $popular;
        $producto->nuevo = $nuevo;
        if ($imagenUrl !== null) {
            $producto->imagen_url = $imagenUrl;
        }
        $producto->save();

        // Reemplaza cualquier tamano viejo (incluye texto de prueba de sesiones anteriores)
        // por un set limpio y consistente.
        ProductoTamano::where('producto_id', $producto->id)->delete();
        foreach (array_values($tamanos) as $i => $t) {
            $pt = new ProductoTamano();
            $pt->producto_id = $producto->id;
            $pt->nombre = $t['nombre'];
            $pt->precio = $t['precio'];
            $pt->descripcion = $t['descripcion'] ?? null;
            $pt->orden = $i;
            $pt->activo = true;
            $pt->save();
        }

        return $producto;
    }

    /** @return array<string, Producto> */
    private function sembrarProductos(array $categorias): array
    {
        $pizza = $categorias['Pizza'];
        $grill = $categorias['Grill'];
        $pastas = $categorias['Pastas'];
        $bebidas = $categorias['Bebidas'];

        $productos = [];

        $productos['Pizza Hawaiana'] = $this->crearOActualizarProducto(
            $pizza,
            'Pizza Hawaiana',
            'Deliciosa pizza con piña y jamón',
            9500,
            true,
            [
                ['nombre' => 'Personal', 'precio' => 6500, 'descripcion' => '6 porciones'],
                ['nombre' => 'Mediana', 'precio' => 9500, 'descripcion' => '8 porciones'],
                ['nombre' => 'Grande', 'precio' => 13500, 'descripcion' => '12 porciones'],
            ],
            'https://upload.wikimedia.org/wikipedia/commons/d/d1/Hawaiian_pizza_1.jpg',
        );

        $productos['Pizza Pepperoni'] = $this->crearOActualizarProducto(
            $pizza,
            'Pizza Pepperoni',
            'Pizza clásica con pepperoni y mozzarella',
            10000,
            false,
            [
                ['nombre' => 'Personal', 'precio' => 6800, 'descripcion' => '6 porciones'],
                ['nombre' => 'Mediana', 'precio' => 10000, 'descripcion' => '8 porciones'],
                ['nombre' => 'Grande', 'precio' => 14000, 'descripcion' => '12 porciones'],
            ],
            'https://upload.wikimedia.org/wikipedia/commons/8/8a/Pepperoni_pizza-_boella_co._2024-02-17.jpg',
            popular: true,
        );

        $productos['Pizza Margarita'] = $this->crearOActualizarProducto(
            $pizza,
            'Pizza Margarita',
            'Tomate, mozzarella fresca y albahaca',
            8800,
            true,
            [
                ['nombre' => 'Personal', 'precio' => 6000, 'descripcion' => '6 porciones'],
                ['nombre' => 'Mediana', 'precio' => 8800, 'descripcion' => '8 porciones'],
                ['nombre' => 'Grande', 'precio' => 12500, 'descripcion' => '12 porciones'],
            ],
            'https://upload.wikimedia.org/wikipedia/commons/d/de/Margherita_pizza_on_plate.jpg',
        );

        $productos['Churrasco'] = $this->crearOActualizarProducto(
            $grill,
            'Churrasco',
            'Corte de res a la parrilla, marinado y servido en tiras',
            7500,
            true,
            [],
            'https://upload.wikimedia.org/wikipedia/commons/d/de/Asado_-_Flickr_-_Marieloreto.jpg',
        );

        $productos['Costillas BBQ'] = $this->crearOActualizarProducto(
            $grill,
            'Costillas BBQ',
            'Costillas de cerdo a la parrilla bañadas en salsa BBQ',
            8200,
            false,
            [],
            'https://upload.wikimedia.org/wikipedia/commons/8/8a/Spareribs_bbq.jpg',
            popular: true,
        );

        $productos['Lomito a la parrilla'] = $this->crearOActualizarProducto(
            $grill,
            'Lomito a la parrilla',
            'Lomito de res a la parrilla, termino medio',
            9800,
            false,
            [],
            'https://upload.wikimedia.org/wikipedia/commons/6/6f/Filet_mignon.jpg',
            nuevo: true,
        );

        $productos['Fettuccine Alfredo'] = $this->crearOActualizarProducto(
            $pastas,
            'Fettuccine Alfredo',
            'Fettuccine en salsa alfredo con pollo',
            6500,
            false,
            [],
            'https://upload.wikimedia.org/wikipedia/commons/a/aa/Lori%27s_Diner_chicken_fettucine_alfredo.JPG',
            nuevo: true,
        );

        $productos['Espagueti a la Boloñesa'] = $this->crearOActualizarProducto(
            $pastas,
            'Espagueti a la Boloñesa',
            'Espagueti en salsa de carne molida y tomate',
            6000,
            false,
            [],
            'https://upload.wikimedia.org/wikipedia/commons/2/20/Spaghetti_Bolognese_mit_Parmesan_oder_Grana_Padano.jpg',
            popular: true,
        );

        $productos['Coca-Cola 600ml'] = $this->crearOActualizarProducto(
            $bebidas,
            'Coca-Cola 600ml',
            null,
            1500,
            false,
            [],
            'https://upload.wikimedia.org/wikipedia/commons/7/70/Coca-Cola_bottle_%286699404437%29.jpg',
            popular: true,
        );

        $productos['Agua embotellada'] = $this->crearOActualizarProducto(
            $bebidas,
            'Agua embotellada',
            null,
            1000,
            false,
            [],
            'https://upload.wikimedia.org/wikipedia/commons/d/d3/Bottled_water_%286972595593%29.jpg',
        );

        $productos['Limonada natural'] = $this->crearOActualizarProducto(
            $bebidas,
            'Limonada natural',
            'Limonada fresca de la casa',
            1800,
            false,
            [],
            'https://upload.wikimedia.org/wikipedia/commons/8/87/Lemonade_tricolor.jpg',
            nuevo: true,
        );

        return $productos;
    }

    private function sembrarExtras(array $categorias): void
    {
        $pizza = $categorias['Pizza'];

        Extra::withoutGlobalScopes()->updateOrCreate(
            ['instancia_id' => self::INSTANCIA_ID, 'nombre' => 'Extra Queso'],
            ['categoria_id' => $pizza->id, 'precio' => 1500, 'disponible' => true, 'es_general' => false],
        );

        Extra::withoutGlobalScopes()->updateOrCreate(
            ['instancia_id' => self::INSTANCIA_ID, 'nombre' => 'Extra Pepperoni'],
            ['categoria_id' => $pizza->id, 'precio' => 1200, 'disponible' => true, 'es_general' => false],
        );

        Extra::withoutGlobalScopes()->updateOrCreate(
            ['instancia_id' => self::INSTANCIA_ID, 'nombre' => 'Papas fritas'],
            ['categoria_id' => null, 'precio' => 1500, 'disponible' => true, 'es_general' => true],
        );
    }

    // ── Promos (ofertas + cupones, GLOBALes: no llevan instancia_id) ───────

    /**
     * Ofertas vigentes para la vitrina del Home. Son globales (Papa John's style):
     * no se aíslan por instancia. La "hero" queda destacada en el Home vía configuraciones.
     *
     * @param array<string, Producto> $productos
     */
    private function sembrarOfertas(array $productos): void
    {
        $inicio = Carbon::now()->subDays(15)->toDateString();
        $fin = Carbon::now()->addDays(45)->toDateString();

        $martes = Oferta::updateOrCreate(
            ['nombre' => 'Martes de Pizza'],
            [
                'descripcion' => 'Todos los martes, 30% de descuento en pizzas medianas y grandes.',
                'tipo_descuento' => 'porcentaje',
                'valor' => 30,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'activa' => true,
            ],
        );
        $martes->productos()->sync(array_values(array_filter([
            $productos['Pizza Hawaiana']->id ?? null,
            $productos['Pizza Pepperoni']->id ?? null,
            $productos['Pizza Margarita']->id ?? null,
        ])));

        $parrillero = Oferta::updateOrCreate(
            ['nombre' => 'Combo Parrillero'],
            [
                'descripcion' => '₡2.500 de descuento llevando dos cortes del Grill.',
                'tipo_descuento' => 'precio_fijo',
                'valor' => 2500,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'activa' => true,
            ],
        );
        $parrillero->productos()->sync(array_values(array_filter([
            $productos['Churrasco']->id ?? null,
            $productos['Costillas BBQ']->id ?? null,
            $productos['Lomito a la parrilla']->id ?? null,
        ])));

        Oferta::updateOrCreate(
            ['nombre' => 'Fin de Semana Familiar'],
            [
                'descripcion' => '15% en tu pedido para comer en el restaurante, sábados y domingos.',
                'tipo_descuento' => 'porcentaje',
                'valor' => 15,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'activa' => true,
            ],
        );

        // Elimina ofertas basura de sesiones anteriores (ej. "OFERTA" de prueba): deja
        // solo las tres reales. Se desasocian primero del pivote oferta_producto.
        Oferta::whereNotIn('nombre', ['Martes de Pizza', 'Combo Parrillero', 'Fin de Semana Familiar'])
            ->get()
            ->each(function (Oferta $o): void {
                $o->productos()->detach();
                $o->delete();
            });

        // Oferta "hero" del Home (aparece primero, con estrella). Vive en `configuraciones`,
        // que es aislada por instancia: se escribe con instancia_id explicito y sin el global
        // scope (el seeder de consola no tiene sesion que lo alimente).
        $hero = Configuracion::withoutGlobalScopes()
            ->where('instancia_id', self::INSTANCIA_ID)
            ->where('clave', 'home_oferta_hero_id')
            ->first() ?? new Configuracion();
        $hero->clave = 'home_oferta_hero_id';
        $hero->valor = (string) $martes->id;
        $hero->descripcion = 'Oferta destacada (hero) en el Home del cliente.';
        $hero->instancia_id = self::INSTANCIA_ID;
        $hero->save();
    }

    /** Cupones canjeables vigentes. Globales (no se aíslan por instancia). */
    private function sembrarCupones(): void
    {
        $inicio = Carbon::now()->subDays(15)->toDateString();
        $fin = Carbon::now()->addDays(45)->toDateString();

        Cupon::updateOrCreate(
            ['codigo' => 'ROOSTER10'],
            [
                'tipo' => 'porcentaje',
                'valor' => 10,
                'monto_minimo' => 8000,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'usos_max' => 100,
                'usos_actuales' => 0,
                'activo' => true,
            ],
        );

        Cupon::updateOrCreate(
            ['codigo' => 'PRIMERPEDIDO'],
            [
                'tipo' => 'monto_fijo',
                'valor' => 1500,
                'monto_minimo' => 6000,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'usos_max' => 200,
                'usos_actuales' => 0,
                'activo' => true,
            ],
        );

        Cupon::updateOrCreate(
            ['codigo' => 'FINDE2000'],
            [
                'tipo' => 'monto_fijo',
                'valor' => 2000,
                'monto_minimo' => 12000,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'usos_max' => 50,
                'usos_actuales' => 0,
                'activo' => true,
            ],
        );

        // Elimina cupones basura de sesiones anteriores: deja solo los tres reales.
        Cupon::whereNotIn('codigo', ['ROOSTER10', 'PRIMERPEDIDO', 'FINDE2000'])->delete();
    }

    // ── Usuarios ──────────────────────────────────────────────────────────

    private function sembrarAdminSede(Sucursal $sucursal): User
    {
        $rolAdminSede = Role::where('nombre', 'admin_sede')->firstOrFail();

        return User::withoutGlobalScope('instancia')->updateOrCreate(
            ['email' => 'sede@rooster.com'],
            [
                'role_id' => $rolAdminSede->id,
                'instancia_id' => self::INSTANCIA_ID,
                'sucursal_id' => $sucursal->id,
                'nombre' => 'Ana Solano',
                'usuario' => 'ana.solano',
                'password' => 'Rooster2026',
                'telefono' => '8888-1234',
                'activo' => true,
                'password_temporal' => false,
                'cambio_password_obligatorio' => false,
            ],
        );
    }

    /** @return Collection<int, User> Cliente dedicado primero, luego el resto de clientes demo. */
    private function sembrarClientes(): Collection
    {
        $rolCliente = Role::where('nombre', 'cliente')->firstOrFail();

        $dedicado = User::withoutGlobalScope('instancia')->updateOrCreate(
            ['email' => 'cliente@rooster.com'],
            [
                'role_id' => $rolCliente->id,
                'instancia_id' => self::INSTANCIA_ID,
                'nombre' => 'Mariana Chinchilla',
                'usuario' => 'mariana.chinchilla',
                'password' => 'Rooster2026',
                'telefono' => '8888-5678',
                'activo' => true,
                'password_temporal' => false,
                'cambio_password_obligatorio' => false,
            ],
        );

        $resto = User::withoutGlobalScope('instancia')
            ->where('instancia_id', self::INSTANCIA_ID)
            ->where('email', 'like', 'cliente-demo-%@rooster-test.com')
            ->orderBy('email')
            ->get();

        return collect([$dedicado])->merge($resto)->values();
    }

    /**
     * Usuario centinela "Invitado" (uno por instancia). Los pedidos de visitantes sin
     * sesion se guardan a su nombre (cliente_id) con el nombre real en nombre_cliente.
     * Queda inactivo (activo=false) y con password aleatoria: nunca debe poder loguear.
     */
    private function sembrarUsuarioInvitado(): void
    {
        $rolCliente = Role::where('nombre', 'cliente')->firstOrFail();

        User::withoutGlobalScope('instancia')->updateOrCreate(
            ['email' => User::EMAIL_INVITADO],
            [
                'role_id' => $rolCliente->id,
                'instancia_id' => self::INSTANCIA_ID,
                'nombre' => 'Invitado',
                'usuario' => 'invitado',
                'password' => 'no-login-' . bin2hex(random_bytes(12)),
                'telefono' => null,
                'activo' => false,
                'password_temporal' => false,
                'cambio_password_obligatorio' => false,
            ],
        );
    }

    // ── Pedidos (via flujo real de PedidoService) ──────────────────────────

    private function tamanoId(Producto $producto, string $nombre): ?int
    {
        return ProductoTamano::where('producto_id', $producto->id)
            ->where('nombre', $nombre)
            ->where('activo', true)
            ->value('id');
    }

    /**
     * @param array{producto: Producto, tamano?: string, cantidad?: int, extra_ids?: int[]}[] $lineas
     */
    private function crearPedidoDemo(
        int $clienteId,
        int $sucursalId,
        string $modalidad,
        string $nombreCliente,
        ?string $notas,
        array $lineas,
        Carbon $creadoEn,
        string $estadoFinal,
        bool $pagado,
        int $adminActorId,
    ): void {
        $items = [];
        foreach ($lineas as $l) {
            $tamanoId = isset($l['tamano']) ? $this->tamanoId($l['producto'], $l['tamano']) : null;
            $items[] = [
                'producto_id' => $l['producto']->id,
                'cantidad' => $l['cantidad'] ?? 1,
                'producto_tamano_id' => $tamanoId,
                'extra_ids' => $l['extra_ids'] ?? [],
                'notas' => null,
            ];
        }

        $dto = CrearPedidoDTO::fromArray([
            'sucursal_id' => $sucursalId,
            'modalidad' => $modalidad,
            'nombre_cliente' => $nombreCliente,
            'notas' => $notas,
            'items' => $items,
        ]);

        // PerteneceAInstancia asigna instancia_id a partir del usuario autenticado (Auth::user()):
        // en un seeder de consola no hay sesion, hay que loguear al cliente para que el trait
        // pueda asignar la instancia igual que en una petición real.
        Auth::loginUsingId($clienteId);

        Carbon::setTestNow($creadoEn);
        $pedido = $this->pedidoService->crear($clienteId, $dto);
        Carbon::setTestNow();

        // Avanza la maquina de estados hasta el estado final, cada paso unos minutos despues.
        $secuencia = match ($estadoFinal) {
            'en_proceso' => ['en_proceso'],
            'listo' => ['en_proceso', 'listo'],
            'entregado' => ['en_proceso', 'listo', 'entregado'],
            'cancelado' => ['cancelado'],
            default => [],
        };

        $momento = $creadoEn->copy();
        foreach ($secuencia as $estado) {
            $momento = $momento->copy()->addMinutes(random_int(8, 25));
            Carbon::setTestNow($momento);
            $this->pedidoService->cambiarEstado($pedido->id, $estado, null, $adminActorId);
            Carbon::setTestNow();
        }

        if ($estadoFinal === 'entregado' && $pagado) {
            $momento = $momento->copy()->addMinutes(random_int(2, 10));
            Carbon::setTestNow($momento);
            $this->pedidoService->registrarPago($pedido->id);
            Carbon::setTestNow();
        }

        Auth::logout();
    }

    /**
     * @param Collection<int, User> $clientes
     * @param array<string, Producto> $p
     */
    private function sembrarPedidos(Collection $clientes, Sucursal $sucursal, array $p): void
    {
        $admin = User::where('email', 'admin@rooster.com')->value('id');
        $ahora = Carbon::now();

        // Indices: 0 = Mariana (dedicada), 1..6 = demo-1..6
        $c = $clientes->values();
        // Sucursal unica: todos los pedidos son de La Fortuna (se conservan dos alias
        // para no reescribir las 12 llamadas de abajo).
        $lf = $sucursal->id;
        $lib = $sucursal->id;

        $this->crearPedidoDemo($c[0]->id, $lf, 'comer_aqui', 'Fernando Chinchilla', null, [
            ['producto' => $p['Pizza Pepperoni'], 'tamano' => 'Grande', 'cantidad' => 2],
            ['producto' => $p['Coca-Cola 600ml'], 'cantidad' => 1],
        ], $ahora->copy()->subDays(8)->setTime(19, 10), 'entregado', true, $admin);

        $this->crearPedidoDemo($c[1]->id, $lib, 'para_llevar', $c[1]->nombre, null, [
            ['producto' => $p['Pizza Hawaiana'], 'tamano' => 'Mediana', 'extra_ids' => [$this->extraId('Extra Queso')]],
        ], $ahora->copy()->subDays(7)->setTime(20, 5), 'entregado', true, $admin);

        $this->crearPedidoDemo($c[2]->id, $lf, 'comer_aqui', $c[2]->nombre, 'Sin cebolla por favor', [
            ['producto' => $p['Costillas BBQ'], 'cantidad' => 1],
            ['producto' => $p['Limonada natural'], 'cantidad' => 1],
        ], $ahora->copy()->subDays(6)->setTime(13, 30), 'entregado', true, $admin);

        $this->crearPedidoDemo($c[3]->id, $lib, 'para_llevar', $c[3]->nombre, null, [
            ['producto' => $p['Churrasco'], 'cantidad' => 2],
        ], $ahora->copy()->subDays(6)->setTime(18, 45), 'cancelado', false, $admin);

        $this->crearPedidoDemo($c[4]->id, $lf, 'comer_aqui', $c[4]->nombre, null, [
            ['producto' => $p['Pizza Margarita'], 'tamano' => 'Grande', 'extra_ids' => [$this->extraId('Extra Pepperoni')]],
        ], $ahora->copy()->subDays(5)->setTime(19, 50), 'entregado', true, $admin);

        $this->crearPedidoDemo($c[0]->id, $lib, 'para_llevar', $c[0]->nombre, null, [
            ['producto' => $p['Fettuccine Alfredo'], 'cantidad' => 1],
            ['producto' => $p['Espagueti a la Boloñesa'], 'cantidad' => 1],
        ], $ahora->copy()->subDays(5)->setTime(21, 0), 'entregado', false, $admin);

        $this->crearPedidoDemo($c[5]->id, $lf, 'para_llevar', $c[5]->nombre, null, [
            ['producto' => $p['Pizza Pepperoni'], 'tamano' => 'Mediana'],
            ['producto' => $p['Coca-Cola 600ml'], 'cantidad' => 1],
            ['producto' => $p['Agua embotellada'], 'cantidad' => 1],
        ], $ahora->copy()->subDays(4)->setTime(12, 20), 'entregado', true, $admin);

        $this->crearPedidoDemo($c[6]->id, $lib, 'comer_aqui', $c[6]->nombre, 'Cumpleaños, si pueden poner una velita', [
            ['producto' => $p['Pizza Hawaiana'], 'tamano' => 'Grande', 'extra_ids' => [$this->extraId('Extra Queso'), $this->extraId('Papas fritas')]],
        ], $ahora->copy()->subDays(3)->setTime(19, 15), 'entregado', true, $admin);

        $this->crearPedidoDemo($c[1]->id, $lf, 'para_llevar', $c[1]->nombre, null, [
            ['producto' => $p['Lomito a la parrilla'], 'cantidad' => 1],
        ], $ahora->copy()->subDays(2)->setTime(20, 30), 'listo', false, $admin);

        $this->crearPedidoDemo($c[2]->id, $lib, 'comer_aqui', $c[2]->nombre, null, [
            ['producto' => $p['Pizza Margarita'], 'tamano' => 'Mediana'],
        ], $ahora->copy()->subDay()->setTime(13, 5), 'en_proceso', false, $admin);

        $this->crearPedidoDemo($c[0]->id, $lf, 'para_llevar', $c[0]->nombre, null, [
            ['producto' => $p['Pizza Hawaiana'], 'tamano' => 'Personal'],
            ['producto' => $p['Coca-Cola 600ml'], 'cantidad' => 1],
        ], $ahora->copy()->subHours(2), 'pendiente', false, $admin);

        $this->crearPedidoDemo($c[3]->id, $lib, 'comer_aqui', $c[3]->nombre, null, [
            ['producto' => $p['Churrasco'], 'cantidad' => 1],
            ['producto' => $p['Limonada natural'], 'cantidad' => 1],
        ], $ahora->copy()->subMinutes(30), 'pendiente', false, $admin);
    }

    private function extraId(string $nombre): int
    {
        return Extra::withoutGlobalScopes()
            ->where('instancia_id', self::INSTANCIA_ID)
            ->where('nombre', $nombre)
            ->value('id');
    }
}
