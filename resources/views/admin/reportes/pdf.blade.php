<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }} — Pixel Store</title>

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

        .doc-titulo {
            margin-top: 2px;
            font-size: 14px;
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

        table { width: 100%; border-collapse: collapse; }

        .datos { font-size: 9.5px; }

        .datos thead th {
            padding: 6px 8px;
            border-bottom: 1px solid #94a3b8;
            background-color: #f1f5f9;
            font-size: 8px;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #475569;
            text-align: left;
        }

        .datos tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .datos tbody tr:nth-child(even) td { background-color: #f8fafc; }

        .num { text-align: right; white-space: nowrap; }
        .centro { text-align: center; }

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

    {{-- Cabecera: marca + tipo de reporte y rango --}}
    <table>
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div class="marca">PIXEL STORE</div>
                <div class="marca-sub">Hardware y tecnología</div>
            </td>
            <td style="width: 50%; vertical-align: top; text-align: right;">
                <div class="doc-tipo">Reporte</div>
                <div class="doc-titulo">{{ $titulo }}</div>
                <div class="doc-fecha">
                    Del {{ \Illuminate\Support\Carbon::parse($desde)->format('d/m/Y') }}
                    al {{ \Illuminate\Support\Carbon::parse($hasta)->format('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>

    <div class="regla"></div>

    {{-- Filas del reporte --}}
    <table class="datos">
        <thead>
            <tr>
                @foreach ($columnas as $indice => $columna)
                    <th class="{{ $indice === 0 ? '' : 'num' }}">{{ $columna }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($filas as $fila)
                <tr>
                    @foreach ($fila as $indice => $valor)
                        <td class="{{ $indice === 0 ? '' : 'num' }}">{{ $valor }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columnas) }}" class="centro" style="padding: 12px;">
                        No hay datos en el rango seleccionado.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pie">
        Generado {{ now()->format('Y-m-d H:i') }} · Pixel Store · {{ count($filas) }} fila(s)
    </div>

</body>
</html>
