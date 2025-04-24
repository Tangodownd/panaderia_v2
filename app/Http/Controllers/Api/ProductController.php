<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
        $this->middleware('admin')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        // Obtener parámetros de consulta
        $showOutOfStock = $request->query('show_out_of_stock', false);
        $categoryId = $request->query('category_id');
        $search = $request->query('search');
        
        // Iniciar la consulta
        $query = Product::with('category');
        
        // Filtrar por categoría si se proporciona
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        // Filtrar por término de búsqueda si se proporciona
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Filtrar productos sin stock a menos que se solicite mostrarlos
        // Solo para clientes, no para el panel de administración
        if (!$showOutOfStock && !$request->is('api/admin/*')) {
            $query->where('stock', '>', 0);
        }
        
        $products = $query->get();
        
        // Agregar información de stock a cada producto
        $productsWithStockInfo = $products->map(function($product) {
            $product->has_stock = $product->stock > 0;
            return $product;
        });
        
        return response()->json(['data' => $productsWithStockInfo]);
    }

    public function show($id)
    {
        $product = Product::with('category')->find($id);
        
        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }
        
        // Agregar información de stock
        $product->has_stock = $product->stock > 0;
        
        return response()->json(['data' => $product]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|max:2048'
        ]);
        
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image'] = $path;
        }
        
        $product = Product::create($validated);
        
        return response()->json(['data' => $product], 201);
    }

    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        
        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'description' => 'nullable|string',
            'stock' => 'sometimes|required|integer|min:0',
            'category_id' => 'sometimes|required|exists:categories,id',
            'image' => 'nullable|image|max:2048'
        ]);
        
        if ($request->hasFile('image')) {
            // Eliminar imagen anterior si existe
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            
            $path = $request->file('image')->store('products', 'public');
            $validated['image'] = $path;
        }
        
        $product->update($validated);
        
        return response()->json(['data' => $product]);
    }

    public function destroy($id)
    {
        $product = Product::find($id);
        
        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }
        
        // Eliminar imagen si existe
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        
        $product->delete();
        
        return response()->json(null, 204);
    }
}