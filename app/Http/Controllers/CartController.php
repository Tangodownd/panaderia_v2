<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    /**
     * Obtener o crear el carrito para el usuario o sesión actual
     */
    private function getOrCreateCart(Request $request)
    {
        // Depurar información
        \Log::info('getOrCreateCart - Request:', [
            'user_id' => auth()->id(),
            'session_id' => $request->cookie('cart_session_id'),
            'headers' => $request->headers->all(),
            'cookies' => $request->cookies->all()
        ]);
        
        // Si el usuario está autenticado, buscar por user_id
        if (auth()->check()) {
            $cart = Cart::where('user_id', auth()->id())->first();
            
            if ($cart) {
                \Log::info('Carrito encontrado por user_id', ['cart_id' => $cart->id]);
                return $cart;
            }
        }
        
        // Si no está autenticado o no tiene carrito, buscar por session_id
        $sessionId = $request->cookie('cart_session_id');
        
        if (!$sessionId) {
            $sessionId = (string) Str::uuid();
            \Log::info('Generando nuevo session_id', ['session_id' => $sessionId]);
            cookie()->queue('cart_session_id', $sessionId, 60 * 24 * 30); // 30 días
        }
        
        $cart = Cart::where('session_id', $sessionId)->first();
        
        // Si no existe, crear uno nuevo
        if (!$cart) {
            $cart = new Cart();
            $cart->session_id = $sessionId;
            
            if (auth()->check()) {
                $cart->user_id = auth()->id();
            }
            
            $cart->save();
            \Log::info('Nuevo carrito creado', ['cart_id' => $cart->id, 'session_id' => $sessionId]);
        } else {
            \Log::info('Carrito encontrado por session_id', ['cart_id' => $cart->id]);
        }
        
        return $cart;
    }

    /**
     * Obtener el carrito actual
     */
    public function index(Request $request)
    {
        \Log::info('CartController@index - Request:', [
            'user_id' => auth()->id(),
            'session_id' => $request->cookie('cart_session_id'),
            'headers' => $request->headers->all()
        ]);
        
        // Obtener o crear el carrito
        $cart = $this->getOrCreateCart($request);
        
        // Guardar el carrito para asegurarnos de que tenga un ID
        if (!$cart->id) {
            $cart->save();
            \Log::info('Carrito guardado en index', ['cart_id' => $cart->id]);
        }
        
        // Cargar los items con sus productos
        $items = CartItem::with('product')
            ->where('cart_id', $cart->id)
            ->get();
        
        // Calcular el total
        $total = 0;
        foreach ($items as $item) {
            $total += $item->quantity * $item->price;
        }
        
        // Actualizar el total en el carrito
        $cart->total = $total;
        $cart->save();
        
        // Establecer la cookie de sesión en la respuesta
        $response = response()->json([
            'cart' => $cart,
            'items' => $items
        ]);
        
        if (!$request->cookie('cart_session_id')) {
            $response->cookie('cart_session_id', $cart->session_id, 60 * 24 * 30);
            \Log::info('Estableciendo cookie en respuesta', ['session_id' => $cart->session_id]);
        }
        
        return $response;
    }

    /**
     * Añadir un producto al carrito
     */
    public function add(Request $request)
    {
        \Log::info('CartController@add - Request:', [
            'data' => $request->all(),
            'user_id' => auth()->id(),
            'session_id' => $request->cookie('cart_session_id')
        ]);
        
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);
        
        // Obtener o crear el carrito
        $cart = $this->getOrCreateCart($request);
        
        // Guardar el carrito para asegurarnos de que tenga un ID
        if (!$cart->id) {
            $cart->save();
            \Log::info('Carrito guardado en add', ['cart_id' => $cart->id]);
        }
        
        // Obtener el producto
        $product = Product::findOrFail($request->product_id);
        
        // Verificar si el producto ya está en el carrito
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();
        
        if ($cartItem) {
            // Actualizar la cantidad si ya existe
            $cartItem->quantity += $request->quantity;
            $cartItem->save();
            \Log::info('Item actualizado en carrito', ['item_id' => $cartItem->id, 'quantity' => $cartItem->quantity]);
        } else {
            // Crear un nuevo item si no existe
            $cartItem = new CartItem();
            $cartItem->cart_id = $cart->id;
            $cartItem->product_id = $product->id;
            $cartItem->quantity = $request->quantity;
            $cartItem->price = $product->price;
            $cartItem->save();
            \Log::info('Nuevo item añadido al carrito', ['item_id' => $cartItem->id]);
        }
        
        // Recalcular el total
        $total = CartItem::where('cart_id', $cart->id)
            ->sum(\DB::raw('quantity * price'));
        
        // Actualizar el total en el carrito
        $cart->total = $total;
        $cart->save();
        
        // Cargar los items con sus productos
        $items = CartItem::with('product')
            ->where('cart_id', $cart->id)
            ->get();
        
        // Establecer la cookie de sesión en la respuesta
        $response = response()->json([
            'cart' => $cart,
            'items' => $items
        ]);
        
        if (!$request->cookie('cart_session_id')) {
            $response->cookie('cart_session_id', $cart->session_id, 60 * 24 * 30);
            \Log::info('Estableciendo cookie en respuesta de add', ['session_id' => $cart->session_id]);
        }
        
        return $response;
    }

    /**
     * Eliminar un producto del carrito
     */
    public function remove(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);
        
        // Obtener el carrito
        $cart = $this->getOrCreateCart($request);
        
        // Eliminar el item
        CartItem::where('cart_id', $cart->id)
            ->where('product_id', $request->product_id)
            ->delete();
        
        // Recalcular el total
        $total = CartItem::where('cart_id', $cart->id)
            ->sum(\DB::raw('quantity * price'));
        
        // Actualizar el total en el carrito
        $cart->total = $total;
        $cart->save();
        
        // Cargar los items con sus productos
        $items = CartItem::with('product')
            ->where('cart_id', $cart->id)
            ->get();
        
        return response()->json([
            'cart' => $cart,
            'items' => $items
        ]);
    }

    /**
     * Actualizar la cantidad de un producto en el carrito
     */
    public function update(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);
        
        // Obtener el carrito
        $cart = $this->getOrCreateCart($request);
        
        // Actualizar el item
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $request->product_id)
            ->first();
        
        if ($cartItem) {
            $cartItem->quantity = $request->quantity;
            $cartItem->save();
        }
        
        // Recalcular el total
        $total = CartItem::where('cart_id', $cart->id)
            ->sum(\DB::raw('quantity * price'));
        
        // Actualizar el total en el carrito
        $cart->total = $total;
        $cart->save();
        
        // Cargar los items con sus productos
        $items = CartItem::with('product')
            ->where('cart_id', $cart->id)
            ->get();
        
        return response()->json([
            'cart' => $cart,
            'items' => $items
        ]);
    }

/**
 * Marcar el carrito como completado (sin eliminar registros)
 */
public function markAsCompleted(Request $request)
{
    // Obtener el carrito actual
    $cart = $this->getOrCreateCart($request);
    
    // Crear un nuevo carrito para el usuario/sesión
    $newCart = new Cart();
    $newCart->user_id = auth()->id();
    $newCart->session_id = $cart->session_id;
    $newCart->total = 0;
    $newCart->save();
    
    // Devolver el nuevo carrito vacío
    return response()->json([
        'cart' => $newCart,
        'items' => []
    ]);
}
}

