<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|string',
            'payment_method' => 'required|in:credit_card,paypal,cash'
        ]);

        // Validar stock disponible
        foreach ($request->items as $key => $item) {
            $product = Product::find($item['product_id']);
            if ($product->stock < $item['quantity']) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => [
                        "items.{$key}.quantity" => ["No hay suficiente stock para {$product->name}. Stock disponible: {$product->stock}"]
                    ]
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $order = Order::create([
                'user_id' => auth()->id(),
                'status' => 'pending',
                'shipping_address' => $request->shipping_address,
                'payment_method' => $request->payment_method
            ]);

            $total = 0;

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price
                ]);

                $total += $product->price * $item['quantity'];
                
                // Actualizar stock
                $product->decreaseStock($item['quantity']);
            }

            $order->update(['total' => $total]);

            DB::commit();

            return response()->json($order, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al procesar la orden: ' . $e->getMessage()], 500);
        }
    }

    public function index()
    {
        $orders = Order::with('items.product')->where('user_id', auth()->id())->get();
        return response()->json($orders);
    }

    public function show(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json($order->load('items.product'));
    }
}