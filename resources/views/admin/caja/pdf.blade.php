<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cierre de caja — Pixel Store</title>

    {{-- dompdf solo entiende CSS 2.1: sin Tailwind ni variables CSS. --}}
    <style>
        @page { margin: 30px 34px; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10px;
            line-height: 1.45;
            color: #0f172a;
        }

        .marca { font-size: 20px; font-weight: bold; letter-spacing: 1px; }
        .marca-sub {
            margin-top: 2px;
            font-size: 8px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #64748b;
        }

        .doc-tipo {
            margin-top: 14px;
            font-size: 8px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #64748b;
        }

        h1 { margin: 2px 0 0; font-size: 18px; }

        table { width: 100%; border-collapse: collapse; }

        .datos { margin-top: 16px; }
        .datos td { padding: 4px 0; vertical-align: top; }
        .datos .label { width: 32%; color: #64748b; }

        .seccion {
            margin-top: 18px;
            padding-top: 8px;
            border-top: 1px solid #cbd5e1;
            font-size: 8px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #64748b;
        }

        .detalle { margin-top: 6px; }
        .detalle th {
            padding: 6px 8px;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
            font-size: 8px;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #475569;
        }
        .detalle td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .der { text-align: right; }

        .totales { margin-top: 14px; }
        .totales td { padding: 5px 8px; }
        .totales .destacado {
            border-top: 2px solid #0f172a;
            font-size: 12px;
            font-weight: bold;
        }

        .negativo { color: #b91c1c; }
        .positivo { color: #047857; }

        .firmas { margin-top: 44px; }
        .firmas td {
            width: 50%;
            padding-top: 26px;
            border-top: 1px solid #94a3b8;
            text-align: center;
            font-size: 8px;
            color: #64748b;
        }

        .notas { margin-top: 16px; color: #334155; }
        .pie { margin-top: 26px; font-size: 8px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>

<table>
    <tr>
        <td>
            <div class="marca">PIXEL STORE</div>
            <div class="marca-sub">Hardware &amp; componentes</div>
        </td>
        <td class="der">
            <div class="doc-tipo">Comprobante interno</div>
            <h1>Cierre de caja</h1>
        </td>
    </tr>
</table>

<table class="datos">
    <tr>
        <td class="label">Fecha</td>
        <td>{{ $cierre->fecha->format('d/m/Y') }}</td>
        <td class="label">Turno</td>
        <td>{{ ucfirst($cierre->turno) }}</td>
    </tr>
    <tr>
        <td class="label">Responsable</td>
        <td>{{ $cierre->user?->name ?? '—' }}</td>
        <td class="label">Cierre N°</td>
        <td>#{{ $cierre->id }}</td>
    </tr>
    <tr>
        <td class="label">Cobrado en el día</td>
        <td>Bs {{ number_format((float) $resumen['total_ventas'], 2, '.', ',') }}</td>
        <td class="label">Devoluciones</td>
        <td>Bs {{ number_format((float) $resumen['total_devoluciones'], 2, '.', ',') }}</td>
    </tr>
</table>

<div class="seccion">Montos por método de pago</div>
<table class="detalle">
    <thead>
        <tr>
            <th>Método</th>
            <th class="der">Monto del sistema</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($resumen['por_metodo'] as $metodo => $monto)
            <tr>
                <td>{{ $metodo }}</td>
                <td class="der">Bs {{ number_format((float) $monto, 2, '.', ',') }}</td>
            </tr>
        @endforeach

        @foreach ($cierre->pagos as $pago)
            <tr>
                <td>{{ $pago->metodo }} (declarado)</td>
                <td class="der">Bs {{ number_format((float) $pago->monto, 2, '.', ',') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

@php $diferencia = (float) $cierre->diferencia; @endphp
<table class="totales">
    <tr>
        <td class="label">Efectivo esperado</td>
        <td class="der">Bs {{ number_format((float) $cierre->efectivo_esperado, 2, '.', ',') }}</td>
    </tr>
    <tr>
        <td class="label">Efectivo real</td>
        <td class="der">Bs {{ number_format((float) $cierre->efectivo_real, 2, '.', ',') }}</td>
    </tr>
    <tr>
        <td class="label destacado">Diferencia</td>
        <td class="der destacado {{ abs($diferencia) < 0.005 ? 'positivo' : 'negativo' }}">
            Bs {{ number_format($diferencia, 2, '.', ',') }}
        </td>
    </tr>
</table>

@if ($cierre->notas)
    <div class="notas">
        <strong>Notas:</strong> {{ $cierre->notas }}
    </div>
@endif

<table class="firmas">
    <tr>
        <td>Firma del responsable</td>
        <td>Firma de administración</td>
    </tr>
</table>

<div class="pie">
    Documento generado por Pixel Store el {{ now()->format('d/m/Y H:i') }}.
</div>

</body>
</html>
