<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Pedido recibido — Pixel Store</title>
</head>
<body style="margin:0;padding:24px;background:#0b1120;font-family:Arial,Helvetica,sans-serif;color:#e2e8f0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#111827;border:1px solid #1f2937;border-radius:12px;">
        <tr>
            <td style="padding:24px 24px 8px;">
                <p style="margin:0;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#94a3b8;">Pixel Store</p>
                <h1 style="margin:8px 0 0;font-size:22px;color:#f8fafc;">Pedido #{{ $numero }} recibido</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:8px 24px 0;font-size:14px;line-height:1.6;color:#cbd5e1;">
                <p style="margin:0 0 12px;">Hola {{ $venta->cliente?->nombre ?? 'gracias por tu compra' }},</p>
                <p style="margin:0 0 12px;">
                    Registramos tu pedido por un total de
                    <strong style="color:#f8fafc;">Bs {{ number_format((float) $venta->total, 2, ',', '.') }}</strong>.
                    Reservamos el stock para vos.
                </p>
                <p style="margin:0 0 20px;">
                    Podés confirmarlo o cancelarlo con los botones de abajo. El enlace es personal:
                    no lo compartas.
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding:0 24px 8px;">
                <a href="{{ $urlConfirmar }}"
                   style="display:inline-block;padding:12px 20px;border-radius:8px;background:#2563eb;color:#ffffff;font-size:14px;font-weight:bold;text-decoration:none;">
                    Confirmar pedido
                </a>
            </td>
        </tr>
        <tr>
            <td style="padding:8px 24px 24px;">
                <a href="{{ $urlCancelar }}"
                   style="display:inline-block;padding:12px 20px;border-radius:8px;border:1px solid #334155;color:#cbd5e1;font-size:14px;text-decoration:none;">
                    Cancelar pedido
                </a>
            </td>
        </tr>
        <tr>
            <td style="padding:0 24px 24px;font-size:12px;line-height:1.6;color:#64748b;border-top:1px solid #1f2937;">
                <p style="margin:16px 0 0;">
                    Si no reconocés este pedido, ignora este correo o escríbenos.
                    El pago se coordina al entregar.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
