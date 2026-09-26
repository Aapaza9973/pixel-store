<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\AtributoTecnico;
use App\Models\ProductoAtributo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductoController extends Controller
{
 public function index()
{
    $productos = Producto::with(['categoria', 'marca'])->paginate(15);
    return view('inventario.index', compact('productos'));
}

    public function create()
    {
        $categorias = Categoria::all();
        $marcas = Marca::all();
        return view('inventario.create', compact('categorias', 'marcas'));
    }

    // Devuelve atributos vía AJAX según la categoría seleccionada
    public function getAtributosByCategoria($categoriaId)
    {
        $atributos = AtributoTecnico::with('valoresPredefinidos')
            ->where('categoria_id', $categoriaId)
            ->get();

        return response()->json($atributos);
    }

    public function store(Request $request)
    {
        $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'marca_id' => 'nullable|exists:marcas,id',
            'nombre' => 'required|string|unique:productos,nombre|max:255',
            'descripcion' => 'nullable|string',
            'precio_unitario' => 'required|numeric|min:0',
            'costos' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'umbral_alerta' => 'required|integer|min:0',
            'maneja_numero_serie' => 'boolean',
            'sku' => 'nullable|string|unique:productos,sku|max:50',
            'codigo_barras' => 'nullable|string|max:50',
            'visible_catalogo' => 'boolean',
            'atributos' => 'nullable|array',
        ]);

        DB::transaction(function () use ($request) {
            $producto = Producto::create([
                'categoria_id' => $request->categoria_id,
                'marca_id' => $request->marca_id,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'precio_unitario' => $request->precio_unitario,
                'costos' => $request->costos,
                'stock' => $request->stock,
                'umbral_alerta' => $request->umbral_alerta ?? 5,
                'maneja_numero_serie' => $request->has('maneja_numero_serie'),
                'sku' => $request->sku,
                'codigo_barras' => $request->codigo_barras,
                'visible_catalogo' => $request->has('visible_catalogo'),
            ]);

            // Guardado de valores dinámicos EAV
            if ($request->has('atributos')) {
                $this->guardarAtributos($producto->id, $request->atributos);
            }
        });

        return redirect()->route('inventario.index')->with('success', 'Producto guardado exitosamente.');
    }

    public function show($id)
    {
        $producto = Producto::with(['categoria', 'marca', 'atributosEav.atributo', 'atributosEav.valorEnum'])->findOrFail($id);
        return view('inventario.show', compact('producto'));
    }

    public function edit($id)
    {
        $producto = Producto::with('atributosEav')->findOrFail($id);
        $categorias = Categoria::all();
        $marcas = Marca::all();

        return view('inventario.edit', compact('producto', 'categorias', 'marcas'));
    }

    public function update(Request $request, $id)
    {
        $producto = Producto::findOrFail($id);

        $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'marca_id' => 'nullable|exists:marcas,id',
            'nombre' => 'required|string|max:255|unique:productos,nombre,' . $producto->id,
            'descripcion' => 'nullable|string',
            'precio_unitario' => 'required|numeric|min:0',
            'costos' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'umbral_alerta' => 'required|integer|min:0',
            'maneja_numero_serie' => 'boolean',
            'sku' => 'nullable|string|max:50|unique:productos,sku,' . $producto->id,
            'codigo_barras' => 'nullable|string|max:50',
            'visible_catalogo' => 'boolean',
            'atributos' => 'nullable|array',
        ]);

        DB::transaction(function () use ($request, $producto) {
            $producto->update([
                'categoria_id' => $request->categoria_id,
                'marca_id' => $request->marca_id,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'precio_unitario' => $request->precio_unitario,
                'costos' => $request->costos,
                'stock' => $request->stock,
                'umbral_alerta' => $request->umbral_alerta ?? 5,
                'maneja_numero_serie' => $request->has('maneja_numero_serie'),
                'sku' => $request->sku,
                'codigo_barras' => $request->codigo_barras,
                'visible_catalogo' => $request->has('visible_catalogo'),
            ]);

            // Eliminar registros anteriores para refrescar atributos dinámicos
            ProductoAtributo::where('producto_id', $producto->id)->delete();

            // Guardar nuevos valores de atributos
            if ($request->has('atributos')) {
                $this->guardarAtributos($producto->id, $request->atributos);
            }
        });

        return redirect()->route('inventario.index')->with('success', 'Producto actualizado exitosamente.');
    }

    // Función auxiliar para registrar atributos EAV
    private function guardarAtributos($productoId, array $atributos)
    {
        foreach ($atributos as $atributoId => $valor) {
            if (is_null($valor) || $valor === '') continue;

            $atributo = AtributoTecnico::find($atributoId);
            if (!$atributo) continue;

            $data = [
                'producto_id' => $productoId,
                'atributo_id' => $atributoId,
            ];

            switch ($atributo->tipo_dato) {
                case 'string':
                    $data['valor_string'] = $valor;
                    break;
                case 'integer':
                    $data['valor_integer'] = (int) $valor;
                    break;
                case 'decimal':
                    $data['valor_decimal'] = (float) $valor;
                    break;
                case 'boolean':
                    $data['valor_boolean'] = filter_var($valor, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'enum':
                    $data['valor_enum_id'] = $valor;
                    break;
            }

            ProductoAtributo::create($data);
        }
    }
}