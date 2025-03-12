<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;

class AdminController extends Controller
{
    public function getStats()
    {
        $totalProducts = Product::count();
        $totalCategories = Category::count();
        $outOfStockProducts = Product::where('stock', 0)->count();
        
        // Para propósitos de demostración
        $pendingOrders = 0;

        return response()->json([
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'pendingOrders' => $pendingOrders,
            'outOfStockProducts' => $outOfStockProducts
        ]);
    }

    public function getRecentOrders()
    {
        // Como no tenemos un modelo de pedidos real, devolvemos un array vacío
        // En una aplicación real, aquí consultaríamos la base de datos
        return response()->json([]);
    }
}

