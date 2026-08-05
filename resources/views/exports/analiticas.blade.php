<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reportes y analíticas</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e1e1e; margin: 0; padding: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .subtitulo { font-size: 11px; color: #6b7280; margin: 0 0 20px; }
        h2 { font-size: 13px; margin: 22px 0 8px; padding-bottom: 4px; border-bottom: 2px solid #e13642; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
        th { background: #e13642; color: #fff; font-weight: 700; }
        tr:nth-child(even) td { background: #f9fafb; }
        .kpis { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .kpis td { width: 25%; border: 1px solid #e5e7eb; padding: 10px; text-align: center; }
        .kpi-val { display: block; font-size: 16px; font-weight: 700; color: #e13642; }
        .kpi-label { display: block; font-size: 9px; color: #6b7280; text-transform: uppercase; margin-top: 2px; }
        .vacio { color: #9ca3af; font-style: italic; }
        .footer { margin-top: 24px; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>
    <h1>Reportes y analíticas</h1>
    <p class="subtitulo">{{ $etiquetaPeriodo }} &middot; {{ $sucursalNombre }} &middot; Generado el {{ $generadoEn }}</p>

    <h2>Resumen</h2>
    <table class="kpis">
        <tr>
            <td><span class="kpi-val">₡{{ number_format($data['ventas_mes'], 0, ',', '.') }}</span><span class="kpi-label">Ventas</span></td>
            <td><span class="kpi-val">{{ $data['pedidos_mes'] }}</span><span class="kpi-label">Pedidos</span></td>
            <td><span class="kpi-val">₡{{ number_format($data['ticket_promedio'], 0, ',', '.') }}</span><span class="kpi-label">Ticket promedio</span></td>
            <td>
                <span class="kpi-val">
                    @if ($data['comparacion_periodo_anterior']['ventas_pct'] === null)
                        N/D
                    @else
                        {{ $data['comparacion_periodo_anterior']['ventas_pct'] }}%
                    @endif
                </span>
                <span class="kpi-label">Vs. periodo anterior</span>
            </td>
        </tr>
    </table>

    <h2>Ventas por día</h2>
    @if (count($data['ventas_por_dia']) > 0)
        <table>
            <thead><tr><th>Fecha</th><th>Total</th></tr></thead>
            <tbody>
            @foreach ($data['ventas_por_dia'] as $v)
                <tr><td>{{ $v['fecha'] }}</td><td>₡{{ number_format($v['total'], 0, ',', '.') }}</td></tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p class="vacio">Sin datos de ventas en el rango.</p>
    @endif

    <h2>Horas pico</h2>
    @if (count($data['horas_pico']) > 0)
        <table>
            <thead><tr><th>Hora</th><th>Pedidos</th></tr></thead>
            <tbody>
            @foreach ($data['horas_pico'] as $h)
                <tr><td>{{ sprintf('%02d:00', (int) $h['hora']) }}</td><td>{{ $h['cantidad'] }}</td></tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p class="vacio">Sin datos de horas pico.</p>
    @endif

    <h2>Productos más vendidos</h2>
    @if (count($data['top_productos']) > 0)
        <table>
            <thead><tr><th>#</th><th>Producto</th><th>Unidades</th><th>Ingresos</th></tr></thead>
            <tbody>
            @foreach ($data['top_productos'] as $i => $p)
                <tr><td>{{ $i + 1 }}</td><td>{{ $p['nombre'] }}</td><td>{{ $p['unidades'] }}</td><td>₡{{ number_format($p['ingresos'], 0, ',', '.') }}</td></tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p class="vacio">Sin datos de productos.</p>
    @endif

    <h2>Modalidad de pedido</h2>
    @if (count($data['modalidad']) > 0)
        <table>
            <thead><tr><th>Modalidad</th><th>Pedidos</th><th>Porcentaje</th></tr></thead>
            <tbody>
            @foreach ($data['modalidad'] as $m)
                <tr><td>{{ \App\Services\AnaliticasExportService::nombreModalidad($m['modalidad']) }}</td><td>{{ $m['cantidad'] }}</td><td>{{ $m['pct'] }}%</td></tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p class="vacio">Sin datos de modalidad.</p>
    @endif

    <h2>Ventas por categoría</h2>
    @if (count($data['ventas_por_categoria']) > 0)
        <table>
            <thead><tr><th>Categoría</th><th>Total</th></tr></thead>
            <tbody>
            @foreach ($data['ventas_por_categoria'] as $c)
                <tr><td>{{ $c['categoria'] }}</td><td>₡{{ number_format($c['total'], 0, ',', '.') }}</td></tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p class="vacio">Sin datos de categorías.</p>
    @endif

    <p class="footer">Rooster Pizza &amp; Grill — Panel administrativo</p>
</body>
</html>
