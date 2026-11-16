<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización {{ $cotizacion->numero }} — Pixel Store</title>
</head>
<body style="margin:0;padding:24px;background:#0b1120;font-family:Arial,Helvetica,sans-serif;color:#e2e8f0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#111827;border:1px solid #1f2937;border-radius:12px;">
        <tr>
            <td style="padding:24px 24px 8px;">
                <p style="margin:0;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#94a3b8;">Pixel Store</p>
                <h1 style="margin:8px 0 0;font-size:22px;color:#f8fafc;">Cotización {{ $cotizacion->numero }}</h1>
                <p style="margin:6px 0 0;font-size:13px;color:#94a3b8;">
                    Emitida el {{ $cotizacion->fecha_emision?->format('d/m/Y') }}
                    · válida {{ $cotizacion->validez_dias }} días
                </p>
            </td>
        </tr>

        <tr>
            <td style="padding:8px 24px 0;font-size:14px;line-height:1.6;color:#cbd5e1;">
                <p style="margin:0 0 12px;">Hola {{ $cotizacion->nombre_cliente }},</p>
                <p style="margin:0 0 16px;">Te compartimos el detalle de tu cotización:</p>
            </td>
        </tr>

        <tr>
            <td style="padding:0 24px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="text-align:left;color:#94a3b8;">
                            <th style="padding:8px 0;border-bottom:1px solid #1f2937;">Producto</th>
                            <th style="padding:8px 0;border-bottom:1px solid #1f2937;text-align:right;">Cant.</th>
                            <th style="padding:8px 0;border-bottom:1px solid #1f2937;text-align:right;">Precio</th>
                            <th style="padding:8px 0;border-bottom:1px solid #1f2937;text-align:right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cotizacion->items as $item)
                            <tr>
                                <td style="padding:8px 0;border-bottom:1px solid #1f2937;color:#e2e8f0;">{{ $item->nombre }}</td>
                                <td style="padding:8px 0;border-bottom:1px solid #1f2937;text-align:right;color:#cbd5e1;">{{ $item->cantidad }}</td>
                                <td style="padding:8px 0;border-bottom:1px solid #1f2937;text-align:right;color:#cbd5e1;">Bs {{ number_format((float) $item->precio_unitario, 2, ',', '.') }}</td>
                                <td style="padding:8px 0;border-bottom:1px solid #1f2937;text-align:right;color:#e2e8f0;">Bs {{ number_format($item->subtotal(), 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="padding:12px 0 0;text-align:right;font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#94a3b8;">Total</td>
                            <td style="padding:12px 0 0;text-align:right;font-size:18px;font-weight:bold;color:#f8fafc;">Bs {{ number_format((float) $cotizacion->total, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </td>
        </tr>

        <tr>
            <td style="padding:24px;font-size:12px;line-height:1.6;color:#64748b;border-top:1px solid #1f2937;">
                <p style="margin:0;">
                    El precio puede variar después de la fecha de validez. Para confirmar
                    tu pedido respondé este correo o escribinos por WhatsApp.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
