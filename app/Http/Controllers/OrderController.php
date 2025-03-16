<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Obtener el carrito actual o crear uno nuevo si no existe
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\Cart|null
     */
    private function getCart(Request $request)
    {
        $cart_session_id = $request->cookie('cart_session_id');

        if (!$cart_session_id) {
            return null;
        }

        $cart = \App\Models\Cart::where('session_id', $cart_session_id)->first();

        return $cart;
    }

    /**
     * Crear una nueva orden
     */
    public function store(Request $request)
    {
        \Log::info('OrderController@store - Request:', [
            'data' => $request->all(),
            'user_id' => auth()->id(),
            'session_id' => $request->cookie('cart_session_id')
        ]);
        
        try {
            // Validar los datos de la orden
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20',
                'shipping_address' => 'required|string',
                'payment_method' => 'required|in:cash,card,transfer',
            ]);

            // Iniciar transacción
            DB::beginTransaction();
            
            // Obtener el carrito actual
            $cart = $this->getCart($request);
            
            if (!$cart) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró un carrito válido'
                ], 400);
            }
            
            // Cargar los items del carrito
            $cartItems = CartItem::with('product')
                ->where('cart_id', $cart->id)
                ->get();
            
            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El carrito está vacío'
                ], 400);
            }
            
            // Calcular el total
            $total = 0;
            foreach ($cartItems as $item) {
                $total += $item->quantity * $item->price;
            }
            
            // Crear la orden
            $order = new Order();
            $order->user_id = auth()->id();
            $order->name = $request->name;
            $order->email = $request->email;
            $order->phone = $request->phone;
            $order->shipping_address = $request->shipping_address;
            $order->payment_method = $request->payment_method;
            $order->notes = $request->notes;
            $order->total = $total;
            $order->status = 'pending';
            $order->save();
            
            // Crear los items de la orden
            foreach ($cartItems as $item) {
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->product_id = $item->product_id;
                $orderItem->quantity = $item->quantity;
                $orderItem->price = $item->price;
                $orderItem->save();
            }
            
            // Vaciar el carrito
            CartItem::where('cart_id', $cart->id)->delete();
            
            // Actualizar el total del carrito
            $cart->total = 0;
            $cart->save();
            
            // Confirmar transacción
            DB::commit();
            
            \Log::info('Orden creada correctamente:', ['order_id' => $order->id]);
            
            return response()->json([
                'success' => true,
                'message' => 'Orden creada correctamente',
                'order_id' => $order->id
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Revertir transacción en caso de error de validación
            DB::rollBack();
            
            // Registrar el error
            \Log::error('Error de validación al crear orden: ' . json_encode($e->errors()));
            
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Revertir transacción en caso de error
            DB::rollBack();
            
            // Registrar el error
            \Log::error('Error al crear orden: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la orden: ' . $e->getMessage()
            ], 500);
        }
    }
}

