<?php

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Http\Controllers\CartController;
use Illuminate\Http\Request;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_add_product_to_cart()
    {
        // Crear un producto de prueba
        $product = Product::factory()->create([
            'name' => 'Pan de Chocolate',
            'price' => 5.00
        ]);

        // Simular una solicitud para añadir el producto al carrito
        $response = $this->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2
        ]);

        // Verificar la respuesta
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'cart',
                    'items'
                ]);

        // Verificar que el producto se haya añadido al carrito
        $cart = Cart::where('status', 'active')->first();
        $this->assertNotNull($cart);
        $this->assertEquals(1, $cart->items()->count());
        $this->assertEquals($product->id, $cart->items()->first()->product_id);
        $this->assertEquals(2, $cart->items()->first()->quantity);
        $this->assertEquals(10.00, $cart->total);
    }

    /** @test */
    public function it_can_remove_product_from_cart()
    {
        // Crear un producto de prueba
        $product = Product::factory()->create([
            'name' => 'Donut',
            'price' => 2.50
        ]);

        // Crear un carrito directamente
        $cart = new Cart();
        $cart->status = 'active';
        $cart->total = 0;
        $cart->save();

        // Añadir el producto al carrito directamente
        $cartItem = new CartItem();
        $cartItem->cart_id = $cart->id;
        $cartItem->product_id = $product->id;
        $cartItem->quantity = 1;
        $cartItem->price = $product->price;
        $cartItem->save();

        // Actualizar el total del carrito
        $cart->total = $product->price;
        $cart->save();

        // Verificar que el producto se haya añadido
        $this->assertEquals(1, $cart->items()->count());

        // Crear una instancia del controlador y una solicitud
        $controller = new CartController();
        $request = new Request();
        $request->merge(['product_id' => $product->id]);
        
        // Establecer la cookie en la solicitud
        $request->cookies->set('cart_id', $cart->id);

        // Llamar directamente al método remove
        $controller->remove($request);

        // Refrescar el modelo del carrito
        $cart = $cart->fresh();
        
        // Verificar que el producto se haya eliminado
        $this->assertEquals(0, $cart->items()->count());
        $this->assertEquals(0, $cart->total);
    }

    /** @test */
    public function it_can_mark_cart_as_completed()
    {
        // Crear un producto de prueba
        $product = Product::factory()->create([
            'name' => 'Pastel',
            'price' => 15.00
        ]);

        // Crear un carrito directamente
        $cart = new Cart();
        $cart->status = 'active';
        $cart->total = 0;
        $cart->save();

        // Añadir el producto al carrito directamente
        $cartItem = new CartItem();
        $cartItem->cart_id = $cart->id;
        $cartItem->product_id = $product->id;
        $cartItem->quantity = 1;
        $cartItem->price = $product->price;
        $cartItem->save();

        // Actualizar el total del carrito
        $cart->total = $product->price;
        $cart->save();

        // Verificar que el carrito tiene el producto
        $this->assertEquals(1, $cart->items()->count());
        $this->assertEquals(15.00, $cart->total);

        // Crear una instancia del controlador y una solicitud
        $controller = new CartController();
        $request = new Request();
        
        // Establecer la cookie en la solicitud
        $request->cookies->set('cart_id', $cart->id);

        // Llamar directamente al método markAsCompleted
        $controller->markAsCompleted($request);

        // Refrescar el modelo del carrito
        $cart = $cart->fresh();
        
        // Verificar que el carrito se haya marcado como completado
        $this->assertEquals('completed', $cart->status);
        $this->assertEquals(15.00, $cart->total);

        // Verificar que se haya creado un nuevo carrito activo
        $newCart = Cart::where('status', 'active')->orderBy('id', 'desc')->first();
        $this->assertNotNull($newCart);
        $this->assertEquals(0, $newCart->total);
    }
}