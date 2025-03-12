<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Order;

class AdminController extends Controller
{
    public function getStats()
    {
        $totalProducts = Blog::count();
        $totalCategories = Category::count();
        $outOfStockProducts = Blog::where('availabilityStatus', 'Out of Stock')->count();
        
        // Si tienes un modelo Order, puedes usar esto
        // $pendingOrders = Order::where('status', 'pending')->count();
        
        // Para propósitos de demostración
        $pendingOrders = 3;

        return response()->json([
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'pendingOrders' => $pendingOrders,
            'outOfStockProducts' => $outOfStockProducts
        ]);
    }

    public function getRecentOrders()
    {
        // Si tienes un modelo Order, puedes usar esto
        // $orders = Order::with('customer')->orderBy('created_at', 'desc')->take(5)->get();
        
        // Para propósitos de demostración
        $orders = [
            [
                'id' => 1,
                'orderNumber' => 'ORD-1234',
                'date' => now()->toISOString(),
                'customer' => ['name' => 'Juan Pérez'],
                'status' => 'pending'
            ],
            [
                'id' => 2,
                'orderNumber' => 'ORD-1235',
                'date' => now()->subDay()->toISOString(),
                'customer' => ['name' => 'María López'],
                'status' => 'completed'
            ]
        ];

        return response()->json($orders);
    }
}

