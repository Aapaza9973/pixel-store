@extends('layouts.app')

@section('content')
<div class="container">
    <h2>{{ $producto->nombre }}</h2>
    <p><strong>Categoría:</strong> {{ $producto->categoria->nombre }} | <strong>Marca:</strong> {{ $producto->marca->nombre ?? 'N/A' }}</p>
    <p><strong>SKU:</strong> {{ $producto->sku ?? 'Sin SKU' }} | <strong>Precio:</strong> ${{ number_format($producto->precio_unitario, 2) }}</p>

    <h4>Atributos Técnicos Especificados:</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Atributo</th>
                <th>Valor</th>
                <th>Unidad</th>
            </tr>
        </thead>
        <tbody>
            @forelse($producto->atributosEav as $item)
                <tr>
                    <td>{{ $item->atributo->nombre }}</td>
                    <td>{{ $item->valor }}</td>
                    <td>{{ $item->atributo->unidad ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">No hay atributos especificados para este producto.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection