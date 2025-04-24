<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;

class AdminController extends Controller
{
    public function getStats()
    {
        $totalProducts = Product::count();
        $totalCategories = Category::count();
        $outOfStockProducts = Product::where('stock', 0)->count();
        
        // Contar pedidos pendientes
        $pendingOrders = Order::where('status', 'pending')->count();

        return response()->json([
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'pendingOrders' => $pendingOrders,
            'outOfStockProducts' => $outOfStockProducts
        ]);
    }

    public function getRecentOrders()
    {
        // Obtener los 10 pedidos más recientes con sus items y productos
        $recentOrders = Order::with(['items.product'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($order) {
                // Formatear los datos para la vista
                return [
                    'id' => $order->id,
                    'orderNumber' => $order->order_number ?? 'ORD-' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                    'date' => $order->created_at,
                    'customer' => [
                        'name' => $order->name,
                        'email' => $order->email,
                        'phone' => $order->phone
                    ],
                    'total' => $order->total,
                    'status' => $order->status,
                    'payment_method' => $order->payment_method,
                    'items' => $order->items->map(function ($item) {
                        return [
                            'product_name' => $item->product ? $item->product->name : 'Producto no disponible',
                            'quantity' => $item->quantity,
                            'price' => $item->price
                        ];
                    })
                ];
            });

        return response()->json($recentOrders);
    }
}
