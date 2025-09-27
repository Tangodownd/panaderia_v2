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
    public function getAllOrders(\Illuminate\Http\Request $request)
{
    $status = strtolower((string) $request->query('status', '')); // ej: pending | paid | cash_on_delivery | awaiting_payment
    $query  = \App\Models\Order::query()->orderByDesc('id');

    if ($status !== '') {
        $query->whereRaw('LOWER(status) = ?', [$status]);
    }

    // opcional: paginado simple
    $perPage = (int) $request->query('perPage', 20);
    $orders  = $query->paginate($perPage);

    $map = $orders->getCollection()->map(function (\App\Models\Order $o) {
        $label = 'Desconocido'; $badge = 'secondary';
        switch (strtolower($o->status ?? '')) {
            case 'paid':
            case 'completed':
                $label = 'Completado'; $badge = 'success'; break;
            case 'awaiting_payment':
            case 'awaiting_review':
            case 'processing':
                $label = 'En espera'; $badge = 'info'; break;
            case 'cash_on_delivery':
            case 'pending':
                $label = 'Pendiente'; $badge = 'warning'; break;
            case 'cancelled':
                $label = 'Cancelado'; $badge = 'danger'; break;
        }

        return [
            'id'                  => $o->id,
            'code'                => sprintf('ORD-%04d', $o->id),
            'created_at'          => $o->created_at,
            'customer_name'       => $o->name,
            'status'              => $o->status,
            'status_label'        => $label,
            'status_badge'        => $badge,
            'total'               => (float) $o->total,
            'payment_method'      => $o->payment_method,
            'payment_reference'   => $o->payment_reference ?? null,
            'payment_verified_at' => $o->payment_verified_at,
        ];
    });

    // reemplaza la colección por el mapeo
    $orders->setCollection($map);

    return response()->json([
        'success' => true,
        'data'    => $orders->items(),
        'meta'    => [
            'current_page' => $orders->currentPage(),
            'per_page'     => $orders->perPage(),
            'total'        => $orders->total(),
            'last_page'    => $orders->lastPage(),
        ],
    ]);
}

}
