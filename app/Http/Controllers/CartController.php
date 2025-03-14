<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    /**
     * Get the current cart or create a new one
     */
    public function getCart(Request $request)
    {
        // Obtener o crear un carrito basado en la sesión
        $sessionId = $request->cookie('cart_session_id');
        
        if (!$sessionId) {
            $sessionId = Str::uuid()->toString();
            Log::info('Creando nuevo ID de sesión para el carrito: ' . $sessionId);
        } else {
            Log::info('Usando ID de sesión existente: ' . $sessionId);
        }
        
        // Buscar carrito existente
        $cart = Cart::where('session_id', $sessionId)->first();

        // Si no existe, crear uno nuevo
        if (!$cart) {
            $cart = new Cart();
            $cart->session_id = $sessionId;
            $cart->user_id = auth()->id();
            $cart->total = 0;
            $cart->save();
            Log::info('Carrito creado con ID: ' . $cart->id);
        } else {
            Log::info('Carrito existente con ID: ' . $cart->id);
        }
        
        // Cargar los items del carrito con sus productos
        $items = CartItem::with('product')
            ->where('cart_id', $cart->id)
            ->get();
        
        // Calcular el total del carrito
        $total = $items->sum(function ($item) {
            return $item->price * $item->quantity;
        });
        
        // Actualizar el total en el carrito
        $cart->total = $total;
        $cart->save();
        
        // Establecer cookie de sesión
        $cookie = cookie('cart_session_id', $sessionId, 60 * 24 * 30); // 30 días
        
        return response()->json([
            'cart' => $cart,
            'items' => $items
        ])->withCookie($cookie);
    }
    
    /**
     * Add a product to the cart
     */
    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);
        
        // Obtener o crear un carrito
        $sessionId = $request->cookie('cart_session_id');
        
        if (!$sessionId) {
            $sessionId = Str::uuid()->toString();
            Log::info('Creando nuevo ID de sesión para añadir al carrito: ' . $sessionId);
        } else {
            Log::info('Usando ID de sesión existente para añadir al carrito: ' . $sessionId);
        }
        
        // Buscar carrito existente
        $cart = Cart::where('session_id', $sessionId)->first();

        // Si no existe, crear uno nuevo
        if (!$cart) {
            $cart = new Cart();
            $cart->session_id = $sessionId;
            $cart->user_id = auth()->id();
            $cart->total = 0;
            $cart->save();
            Log::info('Carrito creado con ID: ' . $cart->id);
        } else {
            Log::info('Carrito existente con ID: ' . $cart->id);
        }
        
        // Obtener el producto
        $product = Product::findOrFail($request->product_id);
        
        // Verificar si el producto ya está en el carrito
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();
        
        if ($cartItem) {
            // Actualizar cantidad si ya existe
            $cartItem->quantity += $request->quantity;
            $cartItem->save();
            Log::info('Actualizada cantidad de producto en carrito: ' . $product->id);
        } else {
            // Crear nuevo item si no existe
            $cartItem = new CartItem();
            $cartItem->cart_id = $cart->id;
            $cartItem->product_id = $product->id;
            $cartItem->quantity = $request->quantity;
            $cartItem->price = $product->price;
            $cartItem->save();
            Log::info('Añadido nuevo producto al carrito: ' . $product->id);
        }
        
        // Recalcular el total del carrito
        $this->recalculateCart($cart->id);
        
        // Cargar el carrito actualizado con sus items
        $cart = Cart::find($cart->id);
        $items = CartItem::with('product')->where('cart_id', $cart->id)->get();
        
        // Establecer cookie de sesión
        $cookie = cookie('cart_session_id', $sessionId, 60 * 24 * 30); // 30 días
        
        return response()->json([
            'cart' => $cart,
            'items' => $items
        ])->withCookie($cookie);
    }
    
    /**
     * Update cart item quantity
     */
    public function updateCartItem(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);
        
        $cartItem = CartItem::findOrFail($id);
        $cartItem->quantity = $request->quantity;
        $cartItem->save();
        
        // Recalcular el total del carrito
        $cart = $this->recalculateCart($cartItem->cart_id);
        
        // Cargar los items del carrito
        $items = CartItem::with('product')->where('cart_id', $cart->id)->get();
        
        return response()->json([
            'cart' => $cart,
            'items' => $items
        ]);
    }
    
    /**
     * Remove an item from the cart
     */
    public function removeFromCart($id)
    {
        $cartItem = CartItem::findOrFail($id);
        $cartId = $cartItem->cart_id;
        $cartItem->delete();
        
        // Recalcular el total del carrito
        $cart = $this->recalculateCart($cartId);
        
        // Cargar los items del carrito
        $items = CartItem::with('product')->where('cart_id', $cart->id)->get();
        
        return response()->json([
            'cart' => $cart,
            'items' => $items
        ]);
    }
    
    /**
     * Clear the cart
     */
    public function clearCart(Request $request)
    {
        $sessionId = $request->cookie('cart_session_id');
        
        if (!$sessionId) {
            return response()->json(['error' => 'No hay un carrito activo'], 400);
        }
        
        $cart = Cart::where('session_id', $sessionId)->first();
        
        if (!$cart) {
            return response()->json(['error' => 'No se encontró el carrito'], 404);
        }
        
        // Eliminar todos los items del carrito
        CartItem::where('cart_id', $cart->id)->delete();
        
        // Actualizar el total del carrito
        $cart->total = 0;
        $cart->save();
        
        return response()->json([
            'cart' => $cart,
            'items' => []
        ]);
    }
    
    /**
     * Recalculate cart total
     */
    private function recalculateCart($cartId)
    {
        $items = CartItem::where('cart_id', $cartId)->get();
        
        $total = $items->sum(function ($item) {
            return $item->price * $item->quantity;
        });
        
        $cart = Cart::find($cartId);
        $cart->total = $total;
        $cart->save();
        
        return $cart;
    }
}