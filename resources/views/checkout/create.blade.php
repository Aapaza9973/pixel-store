@extends('layouts.shop')
@section('title', 'Confirmar pedido | Tu Tienda PC')

@section('content')
<h1 class="font-display mb-6 text-2xl font-bold sm:text-3xl">Confirmar pedido</h1>

<div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
    <form action="{{ route('checkout.store') }}" method="POST" class="panel space-y-4 rounded p-5">
        @csrf
        @foreach ([['nombre','Nombre completo','text',true],['telefono','Teléfono / WhatsApp','tel',true],['email','Correo electrónico (opcional)','email',false],['direccion','Dirección de entrega (opcional)','text',false]] as [$n,$l,$t,$req])
            <div>
                <label for="{{ $n }}" class="mb-1 block text-sm font-medium">{{ $l }}</label>
                <input id="{{ $n }}" type="{{ $t }}" name="{{ $n }}" value="{{ old($n) }}" class="input" @required($req)>
                @error($n) <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
        @endforeach
        <div>
            <label for="notas" class="mb-1 block text-sm font-medium">Notas (opcional)</label>
            <textarea id="notas" name="notas" rows="3" class="input">{{ old('notas') }}</textarea>
            @error('notas') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>
        <button class="btn w-full">Enviar pedido</button>
    </form>

    <aside class="panel h-fit rounded p-5">
        <h2 class="font-display mb-3 text-lg font-semibold">Resumen</h2>
        <ul class="space-y-2 text-sm">
            @foreach ($lines as $l)
                <li class="flex justify-between gap-3"><span>{{ $l->cantidad }} × {{ $l->producto->nombre }}</span><span>Bs. {{ number_format($l->subtotal(), 2) }}</span></li>
            @endforeach
        </ul>
        <p class="font-display mt-4 flex justify-between border-t pt-3 text-xl font-bold" style="border-color:var(--line)">
            <span>Total</span><span style="color:var(--cyan)">Bs. {{ number_format($total, 2) }}</span>
        </p>
    </aside>
</div>
@endsection
