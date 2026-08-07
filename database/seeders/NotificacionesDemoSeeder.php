<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Notificaciones de PRUEBA (no leídas) para la instancia demo (1). Sirven para ver y
 * probar el contador de la campana del header y el badge del sidebar de Notificaciones.
 * Idempotente: borra las de demo previas (título marcado con "(demo)") antes de crear.
 * Correr: php artisan db:seed --class=NotificacionesDemoSeeder
 */
final class NotificacionesDemoSeeder extends Seeder
{
    private const INSTANCIA_ID = 1;

    public function run(): void
    {
        // Sin sesión (CLI) el global scope multi-tenant no aplica → filtramos a mano.
        DB::table('notificaciones')
            ->where('instancia_id', self::INSTANCIA_ID)
            ->where('titulo', 'like', '%(demo)%')
            ->delete();

        $ahora = now();
        $demo = [
            ['tipo' => 'pedido_nuevo',  'titulo' => 'Nuevo pedido PED-1042 (demo)',      'mensaje' => 'Juan Pérez · Para llevar · ₡8 900',        'minago' => 2],
            ['tipo' => 'pedido_nuevo',  'titulo' => 'Nuevo pedido PED-1041 (demo)',      'mensaje' => 'María Rodríguez · Comer aquí · ₡12 300',   'minago' => 9],
            ['tipo' => 'resena_nueva',  'titulo' => 'Nueva reseña 5★ (demo)',            'mensaje' => '"Excelente pizza, volveré" — Carlos',      'minago' => 25],
            ['tipo' => 'stock_bajo',    'titulo' => 'Stock bajo: Mozzarella (demo)',     'mensaje' => 'Quedan 3 kg (mínimo 5 kg)',                'minago' => 48],
            ['tipo' => 'cliente_nuevo', 'titulo' => 'Nuevo cliente registrado (demo)',   'mensaje' => 'Se registró Ana Jiménez',                  'minago' => 90],
        ];

        $rows = [];
        foreach ($demo as $d) {
            $ts = $ahora->copy()->subMinutes($d['minago']);
            $rows[] = [
                'instancia_id' => self::INSTANCIA_ID,
                'tipo' => $d['tipo'],
                'pedido_id' => null,
                'titulo' => $d['titulo'],
                'mensaje' => $d['mensaje'],
                'data' => json_encode(['demo' => true]),
                'leida' => false,
                'leida_en' => null,
                'created_at' => $ts,
                'updated_at' => $ts,
            ];
        }

        DB::table('notificaciones')->insert($rows);

        $this->command?->info('NotificacionesDemoSeeder: ' . count($rows) . ' notificaciones de prueba (no leídas) en instancia ' . self::INSTANCIA_ID . '.');
    }
}
