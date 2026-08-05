<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnaliticasRequest;
use App\Http\Resources\AnaliticasResource;
use App\Models\Sucursal;
use App\Services\AnaliticasExportService;
use App\Services\AnaliticasService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Analiticas del panel admin (ventas, pedidos, horas pico, top productos).
 */
final class AnaliticasController extends Controller
{
    public function __construct(
        private readonly AnaliticasService $analiticas,
        private readonly AnaliticasExportService $exportador,
    ) {
    }

    /**
     * GET /api/admin/analiticas?granularidad=mes|semana|dia|rango&mes=YYYY-MM&fecha=YYYY-MM-DD&sucursal_id=N&desde=YYYY-MM-DD&hasta=YYYY-MM-DD
     */
    public function index(AnaliticasRequest $request): JsonResponse
    {
        $granularidad = $request->validated('granularidad');
        $mes = $request->validated('mes');
        $fecha = $request->validated('fecha');
        $sucursalId = $request->validated('sucursal_id') ? (int) $request->validated('sucursal_id') : null;
        $desde = $request->validated('desde');
        $hasta = $request->validated('hasta');

        $datos = $this->analiticas->resumen($granularidad, $mes, $fecha, $sucursalId, $desde, $hasta);

        return (new AnaliticasResource($datos))->response();
    }

    /**
     * GET /api/admin/analiticas/exportar/excel?granularidad=mes|semana|dia|rango&mes=YYYY-MM&fecha=YYYY-MM-DD&sucursal_id=N&desde=YYYY-MM-DD&hasta=YYYY-MM-DD
     */
    public function exportarExcel(AnaliticasRequest $request): BinaryFileResponse
    {
        [$datos, $etiqueta, $periodo] = $this->prepararExport($request);

        $sucursalId = $request->validated('sucursal_id') ? (int) $request->validated('sucursal_id') : null;
        $path = $this->exportador->generarExcel($datos, $etiqueta, $this->nombreSucursal($sucursalId));

        return response()->download($path, "analiticas-{$periodo}.xlsx")->deleteFileAfterSend(true);
    }

    /**
     * GET /api/admin/analiticas/exportar/pdf?granularidad=mes|semana|dia|rango&mes=YYYY-MM&fecha=YYYY-MM-DD&sucursal_id=N&desde=YYYY-MM-DD&hasta=YYYY-MM-DD
     */
    public function exportarPdf(AnaliticasRequest $request): Response
    {
        [$datos, $etiqueta, $periodo] = $this->prepararExport($request);

        $sucursalId = $request->validated('sucursal_id') ? (int) $request->validated('sucursal_id') : null;
        $pdf = Pdf::loadView('exports.analiticas', [
            'data' => $datos,
            'etiquetaPeriodo' => $etiqueta,
            'sucursalNombre' => $this->nombreSucursal($sucursalId),
            'generadoEn' => now()->format('d/m/Y H:i'),
        ]);

        return $pdf->download("analiticas-{$periodo}.pdf");
    }

    /**
     * Datos compartidos por ambos exports: el resumen, la etiqueta legible del periodo y el
     * identificador de periodo (para el nombre del archivo).
     *
     * @return array{0: array, 1: string, 2: string}
     */
    private function prepararExport(AnaliticasRequest $request): array
    {
        $granularidad = $request->validated('granularidad');
        $mes = $request->validated('mes');
        $fecha = $request->validated('fecha');
        $sucursalId = $request->validated('sucursal_id') ? (int) $request->validated('sucursal_id') : null;
        $desde = $request->validated('desde');
        $hasta = $request->validated('hasta');

        $datos = $this->analiticas->resumen($granularidad, $mes, $fecha, $sucursalId, $desde, $hasta);

        [$granularidadResuelta, $periodo] = $this->analiticas->resolverPeriodo($granularidad, $mes, $fecha, $desde, $hasta);
        [$inicio, $fin] = $this->analiticas->rango($granularidadResuelta, $periodo);
        $etiqueta = AnaliticasExportService::etiquetaPeriodo($granularidadResuelta, $inicio, $fin);

        return [$datos, $etiqueta, $periodo];
    }

    private function nombreSucursal(?int $sucursalId): string
    {
        if ($sucursalId === null) {
            return 'Todas las sucursales';
        }

        return Sucursal::find($sucursalId)?->nombre ?? "Sucursal #{$sucursalId}";
    }
}
