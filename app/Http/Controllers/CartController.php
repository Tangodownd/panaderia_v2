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
     * Obtener o crear el carrito para la sesión actual
     * Usando un identificador de carrito en cookie
     */
    private function getOrCreateCart(Request $request)
    {
        // Obtener el cart_id de la cookie
        $cartId = $request->cookie('cart_id');
        
        \Log::info('getOrCreateCart - Usando cookie', [
            'cookie_cart_id' => $cartId
        ]);
        
        $cart = null;
        
        // Si tenemos un cart_id en la cookie, intentar encontrar ese carrito
        if ($cartId) {
            $cart = Cart::where('id', $cartId)
                      ->where(function($query) {
                          $query->where('status', 'active')
                                ->orWhereNull('status');
                      })
                      ->first();
                      
            if ($cart) {
                \Log::info('Carrito encontrado por cookie', ['cart_id' => $cart->id]);
            } else {
                \Log::info('Carrito no encontrado con el ID de la cookie', ['cookie_cart_id' => $cartId]);
            }
        }
        
        // Si no encontramos un carrito válido, crear uno nuevo
        if (!$cart) {
            $cart = new Cart();
            $cart->session_id = null; // No usamos session_id en la base de datos
            $cart->status = 'active';
            $cart->total = 0;
            
            // Mantener la asignación de user_id si el usuario está autenticado
            if (auth()->check()) {
                $cart->user_id = auth()->id();
            }
            
            $cart->save();
            
            // Establecer la cookie con el ID del nuevo carrito
            cookie()->queue('cart_id', $cart->id, 60 * 24 * 30); // 30 días
            
            \Log::info('Nuevo carrito creado', ['cart_id' => $cart->id]);
        }
        
        return $cart;
    }

    /**
     * Obtener el carrito actual
     */
    public function index(Request $request)
    {
        \Log::info('CartController@index - Request');
        
        // Obtener o crear el carrito
        $cart = $this->getOrCreateCart($request);
        
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
        
        \Log::info('Carrito obtenido con total calculado', [
            'cart_id' => $cart->id,
            'total' => $cart->total,
            'items_count' => count($items)
        ]);
        
        // Establecer la cookie en la respuesta
        $response = response()->json([
            'cart' => $cart,
            'items' => $items
        ]);
        
        if (!$request->cookie('cart_id')) {
            $response->cookie('cart_id', $cart->id, 60 * 24 * 30);
        }
        
        return $response;
    }

    /**
     * Añadir un producto al carrito
     */
    public function add(Request $request)
    {
        \Log::info('CartController@add - Request:', [
            'data' => $request->all()
        ]);
        
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);
        
        // Obtener o crear el carrito
        $cart = $this->getOrCreateCart($request);
        
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
        
        // Recalcular el total de forma explícita
        $items = CartItem::where('cart_id', $cart->id)->get();
        $total = 0;
        foreach ($items as $item) {
            $total += $item->quantity * $item->price;
        }
        
        // Actualizar el total en el carrito
        $cart->total = $total;
        $cart->save();
        
        \Log::info('Total del carrito actualizado', [
            'cart_id' => $cart->id,
            'total' => $cart->total,
            'calculated_total' => $total
        ]);
        
        // Cargar los items con sus productos para la respuesta
        $items = CartItem::with('product')
            ->where('cart_id', $cart->id)
            ->get();
        
        // Establecer la cookie en la respuesta
        $response = response()->json([
            'cart' => $cart,
            'items' => $items
        ]);
        
        if (!$request->cookie('cart_id')) {
            $response->cookie('cart_id', $cart->id, 60 * 24 * 30);
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
        
        // Recalcular el total de forma explícita
        $items = CartItem::where('cart_id', $cart->id)->get();
        $total = 0;
        foreach ($items as $item) {
            $total += $item->quantity * $item->price;
        }
        
        // Actualizar el total en el carrito
        $cart->total = $total;
        $cart->save();
        
        \Log::info('Item eliminado y total actualizado', [
            'cart_id' => $cart->id,
            'total' => $cart->total
        ]);
        
        // Cargar los items con sus productos para la respuesta
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
        
        // Recalcular el total de forma explícita
        $items = CartItem::where('cart_id', $cart->id)->get();
        $total = 0;
        foreach ($items as $item) {
            $total += $item->quantity * $item->price;
        }
        
        // Actualizar el total en el carrito
        $cart->total = $total;
        $cart->save();
        
        \Log::info('Item actualizado y total recalculado', [
            'cart_id' => $cart->id,
            'total' => $cart->total
        ]);
        
        // Cargar los items con sus productos para la respuesta
        $items = CartItem::with('product')
            ->where('cart_id', $cart->id)
            ->get();
        
        return response()->json([
            'cart' => $cart,
            'items' => $items
        ]);
    }

    /**
     * Marcar el carrito como completado y crear uno nuevo
     */
    public function markAsCompleted(Request $request)
    {
        \Log::info('CartController@markAsCompleted - Request');
        
        // Obtener el carrito actual
        $cart = $this->getOrCreateCart($request);
        
        // Recalcular el total de forma explícita antes de marcar como completado
        $items = CartItem::where('cart_id', $cart->id)->get();
        $total = 0;
        foreach ($items as $item) {
            $total += $item->quantity * $item->price;
        }
        
        // Marcar el carrito actual como completado
        $cart->status = 'completed';
        $cart->total = $total; // Asegurar que el total es correcto
        $cart->save();
        
        \Log::info('Carrito marcado como completado', [
            'cart_id' => $cart->id, 
            'total' => $cart->total,
            'status' => $cart->status
        ]);
        
        // Crear un nuevo carrito activo
        $newCart = new Cart();
        if (auth()->check()) {
            $newCart->user_id = auth()->id();
        }
        $newCart->session_id = null; // No usamos session_id en la base de datos
        $newCart->total = 0;
        $newCart->status = 'active';
        $newCart->save();
        
        \Log::info('Nuevo carrito activo creado', [
            'cart_id' => $newCart->id
        ]);
        
        // Establecer la cookie con el ID del nuevo carrito
        $response = response()->json([
            'success' => true,
            'message' => 'Carrito marcado como completado',
            'cart' => $newCart,
            'items' => []
        ]);
        
        $response->cookie('cart_id', $newCart->id, 60 * 24 * 30);
        
        return $response;
    }
    
    /**
     * Vaciar el carrito actual
     */
    public function clear(Request $request)
    {
        // Obtener el carrito
        $cart = $this->getOrCreateCart($request);
        
        // Eliminar todos los items
        CartItem::where('cart_id', $cart->id)->delete();
        
        // Actualizar el total a 0
        $cart->total = 0;
        $cart->save();
        
        \Log::info('Carrito vaciado', [
            'cart_id' => $cart->id
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Carrito vaciado',
            'cart' => $cart,
            'items' => []
        ]);
    }
}

