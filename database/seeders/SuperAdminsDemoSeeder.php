<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SuperAdministrador;
use Illuminate\Database\Seeder;

/**
 * Siembra superadministradores de relleno para PROBAR la tabla del panel con
 * muchas filas (scroll, alto de la card, corte de textos largos).
 * Idempotente (updateOrCreate por email). Password comun: Demo#Rooster2026
 *
 * No usar en produccion. Para limpiarlos:
 *   SuperAdministrador::where('email', 'like', '%@demo.rooster.com')->forceDelete();
 */
class SuperAdminsDemoSeeder extends Seeder
{
    private const PASSWORD = 'Demo#Rooster2026';

    public function run(): void
    {
        $nombres = [
            ['Ana Lucía Rodríguez Vargas', 'anarodriguez'],
            ['Carlos Jiménez Mora', 'cjimenez'],
            ['María José Alfaro Céspedes', 'mjalfaro'],
            ['Diego Fernández Ureña', 'dfernandez'],
            ['Valeria Sánchez Quirós', 'vsanchez'],
            ['Roberto Castillo Navarro', 'rcastillo'],
            ['Gabriela Montero Solís', 'gmontero'],
            ['Andrés Villalobos Chaves', 'avillalobos'],
            ['Sofía Ramírez Bogantes', 'sramirez'],
            ['Luis Diego Arias Fonseca', 'ldarias'],
            ['Natalia Herrera Picado', 'nherrera'],
            ['Esteban Rojas Zamora', 'erojas'],
            ['Paola Cordero Brenes', 'pcordero'],
            ['Fernando Aguilar Madrigal', 'faguilar'],
            ['Melissa Vega Corrales', 'mvega'],
            ['José Pablo Salazar Barquero', 'jpsalazar'],
            ['Karina Umaña Delgado', 'kumana'],
            ['Mauricio Blanco Segura', 'mblanco'],
        ];

        foreach ($nombres as $i => [$nombre, $usuario]) {
            SuperAdministrador::updateOrCreate(
                ['email' => "{$usuario}@demo.rooster.com"],
                [
                    'nombre' => $nombre,
                    'usuario' => $usuario,
                    'password' => self::PASSWORD,
                    // Se alterna el estado para ver ambas pastillas en la tabla.
                    'activo' => $i % 4 !== 0,
                ],
            );
        }
    }
}
