<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Carbon;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Genera los archivos de exportacion (Excel) del resumen de analiticas.
 * Recibe el mismo array que produce AnaliticasService::resumen().
 */
final class AnaliticasExportService
{
    /**
     * Genera un .xlsx temporal con una hoja por seccion del resumen.
     *
     * @param array $data Array devuelto por AnaliticasService::resumen().
     */
    public function generarExcel(array $data, string $etiquetaPeriodo, string $sucursalNombre): string
    {
        $path = tempnam(sys_get_temp_dir(), 'analiticas_') . '.xlsx';

        $writer = new Writer();
        $writer->openToFile($path);

        $tituloStyle = (new Style())->withFontBold(true)->withFontSize(13);
        $headerStyle = (new Style())->withFontBold(true)->withBackgroundColor('E13642')->withFontColor('FFFFFF');

        // Hoja 1: Resumen
        $writer->getCurrentSheet()->setName('Resumen');
        $writer->addRow(Row::fromValuesWithStyle(["Reportes y analíticas — {$etiquetaPeriodo}"], $tituloStyle));
        $writer->addRow(Row::fromValues(["Sucursal: {$sucursalNombre}"]));
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValuesWithStyle(['Indicador', 'Valor'], $headerStyle));
        $writer->addRow(Row::fromValues(['Ventas', $data['ventas_mes']]));
        $writer->addRow(Row::fromValues(['Pedidos', $data['pedidos_mes']]));
        $writer->addRow(Row::fromValues(['Ticket promedio', $data['ticket_promedio']]));

        $comp = $data['comparacion_periodo_anterior'];
        $writer->addRow(Row::fromValues(['Ventas periodo anterior', $comp['ventas_mes_anterior']]));
        $writer->addRow(Row::fromValues(['Pedidos periodo anterior', $comp['pedidos_mes_anterior']]));
        $writer->addRow(Row::fromValues(['Variación de ventas (%)', $comp['ventas_pct'] ?? 'Sin datos previos']));
        $writer->addRow(Row::fromValues(['Variación de pedidos (%)', $comp['pedidos_pct'] ?? 'Sin datos previos']));

        // Hoja 2: Ventas por dia
        $writer->addNewSheetAndMakeItCurrent()->setName('Ventas por dia');
        $writer->addRow(Row::fromValuesWithStyle(['Fecha', 'Total'], $headerStyle));
        foreach ($data['ventas_por_dia'] as $v) {
            $writer->addRow(Row::fromValues([$v['fecha'], $v['total']]));
        }

        // Hoja 3: Horas pico
        $writer->addNewSheetAndMakeItCurrent()->setName('Horas pico');
        $writer->addRow(Row::fromValuesWithStyle(['Hora', 'Pedidos'], $headerStyle));
        foreach ($data['horas_pico'] as $h) {
            $writer->addRow(Row::fromValues([sprintf('%02d:00', (int) $h['hora']), $h['cantidad']]));
        }

        // Hoja 4: Top productos
        $writer->addNewSheetAndMakeItCurrent()->setName('Top productos');
        $writer->addRow(Row::fromValuesWithStyle(['#', 'Producto', 'Unidades', 'Ingresos'], $headerStyle));
        foreach ($data['top_productos'] as $i => $p) {
            $writer->addRow(Row::fromValues([$i + 1, $p['nombre'], $p['unidades'], $p['ingresos']]));
        }

        // Hoja 5: Modalidad
        $writer->addNewSheetAndMakeItCurrent()->setName('Modalidad');
        $writer->addRow(Row::fromValuesWithStyle(['Modalidad', 'Pedidos', 'Porcentaje'], $headerStyle));
        foreach ($data['modalidad'] as $m) {
            $writer->addRow(Row::fromValues([self::nombreModalidad($m['modalidad']), $m['cantidad'], "{$m['pct']}%"]));
        }

        // Hoja 6: Ventas por categoria
        $writer->addNewSheetAndMakeItCurrent()->setName('Ventas por categoria');
        $writer->addRow(Row::fromValuesWithStyle(['Categoría', 'Total'], $headerStyle));
        foreach ($data['ventas_por_categoria'] as $c) {
            $writer->addRow(Row::fromValues([$c['categoria'], $c['total']]));
        }

        $writer->close();

        return $path;
    }

    /** Traduce el codigo crudo de modalidad ("para_llevar", "comer_aqui") a texto legible. */
    public static function nombreModalidad(string $codigo): string
    {
        return match ($codigo) {
            'para_llevar' => 'Para llevar',
            'comer_aqui' => 'Comer aquí',
            default => ucfirst(str_replace('_', ' ', $codigo)),
        };
    }

    /** Texto legible del periodo exportado, igual criterio que el filtro del frontend. */
    public static function etiquetaPeriodo(string $granularidad, Carbon $inicio, Carbon $fin): string
    {
        $inicio = $inicio->copy()->locale('es');
        $fin = $fin->copy()->locale('es');

        if ($granularidad === 'dia') {
            return self::capitalizar($inicio->translatedFormat('l d \d\e F \d\e Y'));
        }

        if ($granularidad === 'semana') {
            $diaInicio = $inicio->format('d');
            $diaFin = self::capitalizar($fin->translatedFormat('d \d\e F \d\e Y'));

            return "Semana del {$diaInicio} al {$diaFin}";
        }

        return self::capitalizar($inicio->translatedFormat('F \d\e Y'));
    }

    private static function capitalizar(string $texto): string
    {
        if ($texto === '') {
            return $texto;
        }

        return mb_strtoupper(mb_substr($texto, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($texto, 1, null, 'UTF-8');
    }
}
