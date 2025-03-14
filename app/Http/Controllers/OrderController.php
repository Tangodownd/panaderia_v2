<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Create a new order from the cart
     */
    public function store(Request $request)
    {
        // Registrar TODOS los datos para depuración
        Log::info('==================== INICIO DE DEPURACIÓN DE PEDIDO ====================');
        Log::info('Datos recibidos en OrderController@store:', $request->all());
        Log::info('Cookies recibidas:', ['cart_session_id' => $request->cookie('cart_session_id')]);
        
        // Validar datos del pedido
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'shipping_address' => 'required|string',
            'payment_method' => 'required|in:cash,transfer',
            'cart_items' => 'sometimes|array',
        ]);
        
        Log::info('Datos validados:', $validatedData);
        
        // Obtener el carrito actual
        $sessionId = $request->cookie('cart_session_id');
        
        if (!$sessionId) {
            Log::error('No hay un carrito activo (cookie cart_session_id no encontrada)');
            $sessionId = Str::uuid()->toString();
            Log::info('Se generó un nuevo ID de sesión:', ['session_id' => $sessionId]);
        }
        
        Log::info('Session ID del carrito:', ['session_id' => $sessionId]);
        
        // Verificar si hay items en el carrito
        $cartItems = [];
        $total = 0;
        
        // Si se enviaron items desde el frontend, usarlos
        if ($request->has('cart_items') && is_array($request->cart_items) && count($request->cart_items) > 0) {
            Log::info('Usando items del carrito enviados desde el frontend');
            
            // Crear items temporales para procesar la orden
            foreach ($request->cart_items as $item) {
                if (isset($item['product_id']) && isset($item['quantity'])) {
                    $product = Product::find($item['product_id']);
                    
                    if ($product) {
                        $price = $item['price'] ?? $product->price;
                        $quantity = $item['quantity'];
                        $total += $price * $quantity;
                        
                        $cartItems[] = (object)[
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                            'price' => $price,
                            'product' => $product
                        ];
                        
                        Log::info('Item de carrito procesado:', [
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                            'price' => $price
                        ]);
                    }
                }
            }
        } else {
            // Obtener items del carrito desde la base de datos
            Log::info('Obteniendo items del carrito desde la base de datos');
            
            // Buscar carrito existente
            $cart = Cart::where('session_id', $sessionId)->first();
            
            if (!$cart) {
                Log::error('No se encontró el carrito en la base de datos');
                return response()->json(['error' => 'No se encontró el carrito'], 404);
            }
            
            Log::info('Carrito encontrado:', ['cart_id' => $cart->id]);
            
            $dbCartItems = CartItem::with('product')->where('cart_id', $cart->id)->get();
            
            if ($dbCartItems->isEmpty()) {
                Log::error('El carrito está vacío');
                return response()->json(['error' => 'El carrito está vacío'], 400);
            }
            
            foreach ($dbCartItems as $item) {
                $price = $item->price;
                $quantity = $item->quantity;
                $total += $price * $quantity;
                
                $cartItems[] = $item;
                
                Log::info('Item de carrito de la base de datos:', [
                    'cart_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $quantity,
                    'price' => $price
                ]);
            }
        }
        
        if (empty($cartItems)) {
            Log::error('No hay items en el carrito para procesar');
            return response()->json(['error' => 'No hay productos en el carrito'], 400);
        }
        
        Log::info('Total calculado:', ['total' => $total]);
        
        // Usar una transacción para asegurar la integridad de los datos
        DB::beginTransaction();
        
        try {
            // Crear una nueva orden
            $order = new Order();
            $order->user_id = auth()->id();
            $order->session_id = $sessionId;
            $order->total = $total;
            $order->status = 'pending';
            $order->shipping_address = $request->shipping_address;
            $order->payment_method = $request->payment_method;
            $order->name = $request->name;
            $order->email = $request->email;
            $order->phone = $request->phone;
            $order->notes = $request->notes ?? '';
            
            // Guardar la orden
            $order->save();
            
            Log::info('Orden creada:', ['order_id' => $order->id]);
            
            // Crear los items de la orden
            foreach ($cartItems as $item) {
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->product_id = $item->product_id;
                $orderItem->quantity = $item->quantity;
                $orderItem->price = $item->price;
                $orderItem->save();
                
                Log::info('Item de orden creado:', [
                    'order_item_id' => $orderItem->id,
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price
                ]);
            }
            
            // Limpiar el carrito después de crear el pedido
            $cart = Cart::where('session_id', $sessionId)->first();
            if ($cart) {
                // Eliminar los items del carrito
                CartItem::where('cart_id', $cart->id)->delete();
                
                // Actualizar el total del carrito
                $cart->total = 0;
                $cart->save();
                
                Log::info('Carrito limpiado:', ['cart_id' => $cart->id]);
            }
            
            // Generar un nuevo ID de sesión para el carrito
            $newSessionId = Str::uuid()->toString();
            
            // Crear un nuevo carrito con el nuevo ID de sesión
            $newCart = new Cart();
            $newCart->session_id = $newSessionId;
            $newCart->user_id = auth()->id();
            $newCart->total = 0;
            $newCart->save();
            
            Log::info('Nuevo carrito creado:', ['cart_id' => $newCart->id, 'session_id' => $newSessionId]);
            
            DB::commit();
            
            // Generar un número de orden único
            $orderNumber = 'ORD-' . strtoupper(Str::random(4)) . '-' . $order->id;
            
            // Establecer la nueva cookie de sesión
            $cookie = cookie('cart_session_id', $newSessionId, 60 * 24 * 30); // 30 días
            
            Log::info('==================== FIN DE DEPURACIÓN DE PEDIDO ====================');
            
            return response()->json([
                'success' => true,
                'message' => 'Pedido creado con éxito',
                'order_id' => $order->id,
                'order_number' => $orderNumber
            ])->withCookie($cookie);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear el pedido: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error al crear el pedido: ' . $e->getMessage(),
                'details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }
    
    /**
     * Get user orders
     */
    public function getUserOrders(Request $request)
    {
        // Obtener órdenes del usuario actual o por sesión
        $sessionId = $request->cookie('cart_session_id');
        $userId = auth()->id();
        
        $query = Order::with('items.product');
        
        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            return response()->json(['error' => 'No se pudo identificar al usuario'], 400);
        }
        
        $orders = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json($orders);
    }
    
    /**
     * Get order details
     */
    public function getOrderDetails($id)
    {
        $order = Order::with('items.product')->findOrFail($id);
        
        // Verificar permisos
        if (auth()->id() !== $order->user_id && !auth()->user()?->is_admin) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        
        return response()->json($order);
    }
}