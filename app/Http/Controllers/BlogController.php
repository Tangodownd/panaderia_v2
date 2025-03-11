<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::with('category')->get();
        return response()->json($blogs);
    }

    public function store(Request $request)
    {
        Log::info('Datos recibidos en store:', $request->all());

        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'contenido' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'precio' => 'required|numeric|min:0',
            'descuento' => 'required|numeric|min:0|max:100',
            'valoracion' => 'required|numeric|min:0|max:5',
            'stock' => 'required|integer|min:0',
            'etiquetas' => 'nullable|string',
            'brand' => 'nullable|string',
            'sku' => 'nullable|string',
            'weight' => 'nullable|numeric',
            'dimensions' => 'nullable|string',
            'warrantyInformation' => 'nullable|string',
            'shippingInformation' => 'nullable|string',
            'availabilityStatus' => 'nullable|string',
            'reviews' => 'nullable|string',
            'returnPolicy' => 'nullable|string',
            'minimumOrderQuantity' => 'nullable|integer',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $data['barcode'] = $this->generateRandomBarcode();

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $data['thumbnail'] = $path;
        }

        if (isset($data['dimensions'])) {
            $data['dimensions'] = json_decode($data['dimensions'], true);
        }

        if (isset($data['etiquetas'])) {
            $data['etiquetas'] = json_decode($data['etiquetas'], true);
        }

        if (isset($data['reviews'])) {
            $data['reviews'] = json_decode($data['reviews'], true);
        }
        
        $blog = Blog::create($data);
        return response()->json([
            'blog' => $blog
        ], 201);
    }

    public function show(Blog $blog)
    {
        return response()->json($blog->load('category'));
    }

    public function update(Request $request, Blog $blog)
    {
        Log::info('Datos recibidos en update:', $request->all());

        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'contenido' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'precio' => 'required|numeric|min:0',
            'descuento' => 'required|numeric|min:0|max:100',
            'valoracion' => 'required|numeric|min:0|max:5',
            'stock' => 'required|integer|min:0',
            'etiquetas' => 'nullable|string',
            'brand' => 'nullable|string',
            'sku' => 'nullable|string',
            'weight' => 'nullable|numeric',
            'dimensions' => 'nullable|string',
            'warrantyInformation' => 'nullable|string',
            'shippingInformation' => 'nullable|string',
            'availabilityStatus' => 'nullable|string',
            'reviews' => 'nullable|string',
            'returnPolicy' => 'nullable|string',
            'minimumOrderQuantity' => 'nullable|integer',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($request->hasFile('thumbnail')) {
            if ($blog->thumbnail) {
                Storage::disk('public')->delete($blog->thumbnail);
            }
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $data['thumbnail'] = $path;
        }

        if (isset($data['dimensions'])) {
            $data['dimensions'] = json_decode($data['dimensions'], true);
        }

        if (isset($data['etiquetas'])) {
            $data['etiquetas'] = json_decode($data['etiquetas'], true);
        }

        if (isset($data['reviews'])) {
            $data['reviews'] = json_decode($data['reviews'], true);
        }

        $blog->update($data);
        return response()->json([
            'blog' => $blog
        ]);
    }

    public function destroy(Blog $blog)
    {
        if ($blog->thumbnail) {
            Storage::disk('public')->delete($blog->thumbnail);
        }
        $blog->delete();
        return response()->json([
            'mensaje' => '¡Registro eliminado correctamente!'
        ]);
    }

    private function generateRandomBarcode()
    {
        return str_pad(rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
    }

    public function getCategories()
    {
        $categories = Category::all();
        return response()->json($categories);
    }

    public function getProductsByCategory(Category $category)
    {
        $products = $category->blogs;
        return response()->json($products);
    }

    public function addReview(Request $request, Blog $blog)
    {
        $data = $request->validate([
            'reviewerName' => 'required|string',
            'reviewerEmail' => 'required|email',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string',
            'date' => 'required|date'
        ]);

        $reviews = $blog->reviews ?? [];
        $reviews[] = $data;
        $blog->reviews = $reviews;

        // Recalcular la valoración promedio
        $totalRating = array_sum(array_column($reviews, 'rating'));
        $blog->valoracion = $totalRating / count($reviews);

        $blog->save();

        return response()->json([
            'message' => 'Reseña agregada con éxito',
            'blog' => $blog
        ]);
    }
}