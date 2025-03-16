<?php

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_add_a_product_to_cart()
    {
        // Crear un producto de prueba
        $product = Product::factory()->create([
            'name' => 'Pan Francés',
            'price' => 2.50
        ]);

        // Crear un carrito
        $cart = Cart::create([
            'session_id' => null,
            'status' => 'active',
            'total' => 0
        ]);

        // Añadir el producto al carrito
        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price
        ]);

        // Recalcular el total
        $total = CartItem::where('cart_id', $cart->id)
            ->sum(\DB::raw('quantity * price'));
        $cart->total = $total;
        $cart->save();

        // Verificar que el producto se haya añadido correctamente
        $this->assertEquals(1, $cart->items()->count());
        $this->assertEquals($product->id, $cart->items()->first()->product_id);
        $this->assertEquals(2, $cart->items()->first()->quantity);
        $this->assertEquals(5.00, $cart->total);
    }

    /** @test */
    public function it_can_remove_a_product_from_cart()
    {
        // Crear un producto de prueba
        $product = Product::factory()->create([
            'name' => 'Pan Integral',
            'price' => 3.00
        ]);

        // Crear un carrito
        $cart = Cart::create([
            'session_id' => null,
            'status' => 'active',
            'total' => 0
        ]);

        // Añadir el producto al carrito
        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price
        ]);

        // Verificar que el producto se haya añadido
        $this->assertEquals(1, $cart->items()->count());

        // Eliminar el producto del carrito
        CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->delete();

        // Recalcular el total
        $total = CartItem::where('cart_id', $cart->id)
            ->sum(\DB::raw('quantity * price'));
        $cart->total = $total;
        $cart->save();

        // Verificar que el producto se haya eliminado
        $this->assertEquals(0, $cart->items()->count());
        $this->assertEquals(0, $cart->total);
    }

    /** @test */
    public function it_can_update_product_quantity_in_cart()
    {
        // Crear un producto de prueba
        $product = Product::factory()->create([
            'name' => 'Croissant',
            'price' => 4.00
        ]);

        // Crear un carrito
        $cart = Cart::create([
            'session_id' => null,
            'status' => 'active',
            'total' => 0
        ]);

        // Añadir el producto al carrito
        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price
        ]);

        // Actualizar la cantidad
        $cartItem->quantity = 3;
        $cartItem->save();

        // Recalcular el total
        $total = CartItem::where('cart_id', $cart->id)
            ->sum(\DB::raw('quantity * price'));
        $cart->total = $total;
        $cart->save();

        // Verificar que la cantidad se haya actualizado
        $this->assertEquals(3, $cart->items()->first()->quantity);
        $this->assertEquals(12.00, $cart->total);
    }
}