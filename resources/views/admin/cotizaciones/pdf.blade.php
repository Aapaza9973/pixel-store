<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización {{ $cotizacion->numero }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #0f1115; font-size: 12px; margin: 0; padding: 24px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #2563eb; padding-bottom: 12px; margin-bottom: 20px; }
        .brand { font-size: 20px; font-weight: 700; color: #2563eb; }
        .meta { text-align: right; font-size: 11px; color: #444; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .cliente { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background: #eef2ff; text-align: left; padding: 8px; font-size: 11px; text-transform: uppercase; color: #333; }
        td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        .num { text-align: right; }
        .totales { margin-left: auto; width: 260px; }
        .totales div { display: flex; justify-content: space-between; padding: 4px 0; }
        .totales .total { font-weight: 700; font-size: 14px; border-top: 1px solid #000; margin-top: 4px; padding-top: 8px; }
        .notas { margin-top: 16px; font-size: 11px; color: #555; }
        .footer { margin-top: 32px; font-size: 10px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">PIXEL STORE</div>
        <div class="meta">
            <div>Cotización {{ $cotizacion->numero }}</div>
            <div>Emitida: {{ $cotizacion->fecha_emision->format('d/m/Y H:i') }}</div>
            <div>Vence: {{ $cotizacion->fecha_vencimiento->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="cliente">
        <strong>Cliente:</strong> {{ $cotizacion->nombre_cliente }}
        @if ($cotizacion->nit_ci) · NIT/CI: {{ $cotizacion->nit_ci }} @endif
        @if ($cotizacion->telefono) · Tel: {{ $cotizacion->telefono }} @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th class="num">Cantidad</th>
                <th class="num">P. unitario</th>
                <th class="num">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cotizacion->items as $item)
                <tr>
                    <td>{{ $item->nombre }}</td>
                    <td class="num">{{ $item->cantidad }}</td>
                    <td class="num">Bs {{ number_format($item->precio_unitario, 2) }}</td>
                    <td class="num">Bs {{ number_format($item->subtotal(), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totales">
        <div><span>Subtotal</span><span>Bs {{ number_format($cotizacion->subtotal, 2) }}</span></div>
        <div><span>Descuento</span><span>Bs {{ number_format($cotizacion->descuento, 2) }}</span></div>
        <div class="total"><span>Total</span><span>Bs {{ number_format($cotizacion->total, 2) }}</span></div>
    </div>

    @if ($cotizacion->notas)
        <div class="notas"><strong>Notas:</strong> {{ $cotizacion->notas }}</div>
    @endif

    <div class="footer">Generado el {{ now()->format('d/m/Y H:i') }} · Pixel Store — Todo el mundo tecnológico, pixel a pixel.</div>
</body>
</html>
