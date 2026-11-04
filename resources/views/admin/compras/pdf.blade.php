<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Orden de compra {{ $orden->numero }} — Pixel Store</title>

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

        .marca {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #0f172a;
        }

        .marca-sub {
            margin-top: 2px;
            font-size: 8px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #64748b;
        }

        .doc-tipo {
            font-size: 8px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #64748b;
        }

        .doc-numero {
            margin-top: 2px;
            font-size: 16px;
            font-weight: bold;
            color: #2563eb;
        }

        .doc-fecha {
            margin-top: 2px;
            font-size: 9px;
            color: #475569;
        }

        .regla {
            margin: 14px 0;
            border-top: 1px solid #cbd5e1;
        }

        .seccion-titulo {
            margin: 0 0 4px;
            font-size: 8px;
            letter-spacing: 1.4px;
            text-transform: uppercase;
            color: #64748b;
        }

        .dato {
            margin: 0;
            font-size: 10px;
            color: #0f172a;
        }

        .dato-secundario {
            margin: 1px 0 0;
            font-size: 9px;
            color: #475569;
        }

        table { width: 100%; border-collapse: collapse; }

        .items {
            margin-top: 4px;
            font-size: 9.5px;
        }

        .items thead th {
            padding: 6px 8px;
            border-bottom: 1px solid #94a3b8;
            background-color: #f1f5f9;
            font-size: 8px;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #475569;
            text-align: left;
        }

        .items tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .items tbody tr:nth-child(even) td { background-color: #f8fafc; }

        .num { text-align: right; white-space: nowrap; }
        .centro { text-align: center; }

        .totales {
            width: 46%;
            margin-left: 54%;
            margin-top: 10px;
            font-size: 10px;
        }

        .totales td { padding: 4px 0; }

        .totales .label { color: #475569; }

        .totales .total-final td {
            padding-top: 7px;
            border-top: 1px solid #94a3b8;
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
        }

        .notas {
            margin-top: 16px;
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            font-size: 9px;
            color: #334155;
        }

        .pie {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            font-size: 8px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    {{-- Cabecera: marca + tipo y número de documento --}}
    <table>
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div class="marca">PIXEL STORE</div>
                <div class="marca-sub">Hardware y tecnología</div>
            </td>
            <td style="width: 45%; vertical-align: top; text-align: right;">
                <div class="doc-tipo">Orden de compra</div>
                <div class="doc-numero">{{ $orden->numero }}</div>
                <div class="doc-fecha">
                    Emitida: {{ $orden->fecha_emision?->format('d/m/Y') ?? $orden->fecha->format('d/m/Y') }}
                </div>
                @if ($orden->fecha_entrega)
                    <div class="doc-fecha">Entrega: {{ $orden->fecha_entrega->format('d/m/Y') }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="regla"></div>

    {{-- Proveedor y datos de la orden --}}
    <table>
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <p class="seccion-titulo">Proveedor</p>
                <p class="dato">{{ $orden->proveedor->nombre }}</p>
                @if ($orden->proveedor->nit)
                    <p class="dato-secundario">NIT: {{ $orden->proveedor->nit }}</p>
                @endif
                @if ($orden->proveedor->contacto)
                    <p class="dato-secundario">Contacto: {{ $orden->proveedor->contacto }}</p>
                @endif
                @if ($orden->proveedor->telefono)
                    <p class="dato-secundario">Tel.: {{ $orden->proveedor->telefono }}</p>
                @endif
                @if ($orden->proveedor->email)
                    <p class="dato-secundario">{{ $orden->proveedor->email }}</p>
                @endif
                @if ($orden->proveedor->direccion)
                    <p class="dato-secundario">{{ $orden->proveedor->direccion }}</p>
                @endif
            </td>
            <td style="width: 45%; vertical-align: top;">
                <p class="seccion-titulo">Datos de la orden</p>
                <p class="dato">Estado: {{ ucfirst(str_replace('_', ' ', $orden->estado)) }}</p>
                <p class="dato-secundario">Elaborada por: {{ $orden->usuario?->name ?? '—' }}</p>
                <p class="dato-secundario">Ítems: {{ $orden->detalles->count() }}</p>
            </td>
        </tr>
    </table>

    {{-- Detalle de ítems --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width: 46%;">Producto</th>
                <th class="centro" style="width: 12%;">Cantidad</th>
                <th class="num" style="width: 20%;">Precio unit.</th>
                <th class="num" style="width: 22%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orden->detalles as $detalle)
                <tr>
                    <td>{{ $detalle->producto?->nombre ?? 'Producto eliminado' }}</td>
                    <td class="centro">{{ $detalle->cantidad }}</td>
                    <td class="num">Bs {{ number_format((float) $detalle->precio_unitario, 2, ',', '.') }}</td>
                    <td class="num">Bs {{ number_format((float) $detalle->subtotal, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="centro" style="padding: 12px;">La orden no tiene ítems.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Totales --}}
    <table class="totales">
        <tr>
            <td class="label">Subtotal</td>
            <td class="num">Bs {{ number_format((float) $orden->subtotal, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Impuestos</td>
            <td class="num">Bs {{ number_format((float) $orden->impuestos, 2, ',', '.') }}</td>
        </tr>
        <tr class="total-final">
            <td>Total</td>
            <td class="num">Bs {{ number_format((float) $orden->total, 2, ',', '.') }}</td>
        </tr>
    </table>

    @if ($orden->notas)
        <div class="notas">
            <strong>Notas:</strong> {{ $orden->notas }}
        </div>
    @endif

    <div class="pie">
        Documento generado el {{ now()->format('d/m/Y H:i') }} · Pixel Store · Orden {{ $orden->numero }}
    </div>

</body>
</html>
