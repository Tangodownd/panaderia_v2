<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
  /**
   * Obtener el carrito actual usando el ID de la cookie
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \App\Models\Cart|null
   */
  private function getCart(Request $request)
  {
      // Obtener el cart_id de la cookie
      $cartId = $request->cookie('cart_id');
      
      \Log::info('OrderController@getCart - Usando cookie', [
          'cookie_cart_id' => $cartId
      ]);
      
      if (!$cartId) {
          \Log::warning('No se encontró cart_id en la cookie');
          return null;
      }
      
      // Buscar el carrito por su ID
      $cart = Cart::where('id', $cartId)
                ->where(function($query) {
                    $query->where('status', 'active')
                          ->orWhereNull('status');
                })
                ->first();
      
      if ($cart) {
          \Log::info('Carrito encontrado por cookie', ['cart_id' => $cart->id]);
      } else {
          \Log::warning('Carrito no encontrado con el ID de la cookie', ['cookie_cart_id' => $cartId]);
      }
      
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
          'cart_id_cookie' => $request->cookie('cart_id')
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
          
          // Obtener el carrito actual usando el nuevo método
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
          
          // Calcular el total de forma explícita
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
          
          // Marcar el carrito como completado
          $cart->status = 'completed';
          $cart->save();
          
          // Crear un nuevo carrito activo
          $newCart = new Cart();
          if (auth()->check()) {
              $newCart->user_id = auth()->id();
          }
          $newCart->session_id = null;
          $newCart->total = 0;
          $newCart->status = 'active';
          $newCart->save();
          
          \Log::info('Nuevo carrito activo creado después de la orden', [
              'cart_id' => $newCart->id
          ]);
          
          // Confirmar transacción
          DB::commit();
          
          \Log::info('Orden creada correctamente:', ['order_id' => $order->id]);
          
          // Establecer la cookie con el ID del nuevo carrito
          $response = response()->json([
              'success' => true,
              'message' => 'Orden creada correctamente',
              'order_id' => $order->id
          ]);
          
          $response->cookie('cart_id', $newCart->id, 60 * 24 * 30);
          
          return $response;
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

