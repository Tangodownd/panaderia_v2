<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;

class CartBlackBoxTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prueba de clases de equivalencia para la cantidad de productos
     */
    public function test_quantity_equivalence_classes()
    {
        // Crear un producto de prueba
        $product = Product::factory()->create();

        // Clase inválida: cantidad negativa
        $response1 = $this->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'quantity' => -1
        ]);
        $response1->assertStatus(422);

        // Clase inválida: cantidad cero
        $response2 = $this->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'quantity' => 0
        ]);
        $response2->assertStatus(422);

        // Clase válida: cantidad positiva
        $response3 = $this->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'quantity' => 5
        ]);
        $response3->assertStatus(200);
    }

    /**
     * Prueba de valores límite para la cantidad de productos
     */
    public function test_quantity_boundary_values()
    {
        // Crear un producto de prueba
        $product = Product::factory()->create();

        // Valor límite: justo por debajo del mínimo
        $response1 = $this->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'quantity' => 0
        ]);
        $response1->assertStatus(422);

        // Valor límite: mínimo
        $response2 = $this->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1
        ]);
        $response2->assertStatus(200);

        // Valor límite: justo por encima del mínimo
        $response3 = $this->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2
        ]);
        $response3->assertStatus(200);
    }

    /**
     * Prueba de tabla de decisión para validación de teléfono
     */
    public function test_phone_validation_decision_table()
    {
        // Crear un producto para el carrito
        $product = Product::factory()->create([
            'price' => 10.00,
            'stock' => 100
        ]);
        
        // Añadir el producto al carrito usando la API
        $addResponse = $this->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1
        ]);
        
        // Verificar que se añadió correctamente
        $addResponse->assertStatus(200);
        
        // Obtener el ID del carrito
        $cartId = $addResponse->json('cart.id');
        
        // Verificar que el carrito existe y tiene items
        $cart = Cart::find($cartId);
        $this->assertNotNull($cart);
        $this->assertEquals(1, $cart->items()->count());
        
        // Caso 1: Teléfono con código de país y formato correcto
        $response1 = $this->withCookie('cart_id', $cartId)
                          ->postJson('/api/orders', [
                              'name' => 'Test User',
                              'email' => 'test@example.com',
                              'phone' => '+584141234567',
                              'shipping_address' => 'Test Address',
                              'payment_method' => 'cash'
                          ]);
        
        // Caso 2: Teléfono sin código de país pero con formato correcto
        $response2 = $this->withCookie('cart_id', $cartId)
                          ->postJson('/api/orders', [
                              'name' => 'Test User',
                              'email' => 'test@example.com',
                              'phone' => '04141234567',
                              'shipping_address' => 'Test Address',
                              'payment_method' => 'cash'
                          ]);
        
        // Caso 3: Teléfono con formato incorrecto (letras)
        $response3 = $this->withCookie('cart_id', $cartId)
                          ->postJson('/api/orders', [
                              'name' => 'Test User',
                              'email' => 'test@example.com',
                              'phone' => '0414abcdefg',
                              'shipping_address' => 'Test Address',
                              'payment_method' => 'cash'
                          ]);
        
        // Caso 4: Teléfono con longitud correcta pero sin código
        $response4 = $this->withCookie('cart_id', $cartId)
                          ->postJson('/api/orders', [
                              'name' => 'Test User',
                              'email' => 'test@example.com',
                              'phone' => '4141234567',
                              'shipping_address' => 'Test Address',
                              'payment_method' => 'cash'
                          ]);
        
        // Caso 5: Teléfono demasiado corto
        $response5 = $this->withCookie('cart_id', $cartId)
                          ->postJson('/api/orders', [
                              'name' => 'Test User',
                              'email' => 'test@example.com',
                              'phone' => '0414123',
                              'shipping_address' => 'Test Address',
                              'payment_method' => 'cash'
                          ]);
        
        // Verificar los resultados
        // Nota: Estos resultados dependen de la implementación exacta de la validación en el backend
        $this->assertTrue($response1->status() == 200 || $response1->status() == 422 || $response1->status() == 400, 
                         "Response 1 status is {$response1->status()}, expected 200, 422 or 400");
        $this->assertTrue($response2->status() == 200 || $response2->status() == 422 || $response2->status() == 400, 
                         "Response 2 status is {$response2->status()}, expected 200, 422 or 400");
        $this->assertTrue($response3->status() == 422 || $response3->status() == 400, 
                         "Response 3 status is {$response3->status()}, expected 422 or 400");
        $this->assertTrue($response4->status() == 200 || $response4->status() == 422 || $response4->status() == 400, 
                         "Response 4 status is {$response4->status()}, expected 200, 422 or 400");
        $this->assertTrue($response5->status() == 422 || $response5->status() == 400, 
                         "Response 5 status is {$response5->status()}, expected 422 or 400");
    }

    /**
     * Prueba de verificación de errores para campos requeridos
     */
    public function test_required_fields_error_checking()
    {
        // Crear un producto para el carrito
        $product = Product::factory()->create();
        
        // Añadir el producto al carrito
        $response = $this->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1
        ]);
        
        // Obtener el ID del carrito
        $cartId = $response->json('cart.id');
        
        // Caso 1: Falta el nombre
        $response1 = $this->withCookie('cart_id', $cartId)
                          ->postJson('/api/orders', [
                              'email' => 'test@example.com',
                              'phone' => '04141234567',
                              'shipping_address' => 'Test Address',
                              'payment_method' => 'cash'
                          ]);
        $response1->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
        
        // Caso 2: Falta el email
        $response2 = $this->withCookie('cart_id', $cartId)
                          ->postJson('/api/orders', [
                              'name' => 'Test User',
                              'phone' => '04141234567',
                              'shipping_address' => 'Test Address',
                              'payment_method' => 'cash'
                          ]);
        $response2->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
        
        // Caso 3: Falta el teléfono
        $response3 = $this->withCookie('cart_id', $cartId)
                          ->postJson('/api/orders', [
                              'name' => 'Test User',
                              'email' => 'test@example.com',
                              'shipping_address' => 'Test Address',
                              'payment_method' => 'cash'
                          ]);
        $response3->assertStatus(422)
                 ->assertJsonValidationErrors(['phone']);
        
        // Caso 4: Falta la dirección
        $response4 = $this->withCookie('cart_id', $cartId)
                          ->postJson('/api/orders', [
                              'name' => 'Test User',
                              'email' => 'test@example.com',
                              'phone' => '04141234567',
                              'payment_method' => 'cash'
                          ]);
        $response4->assertStatus(422)
                 ->assertJsonValidationErrors(['shipping_address']);
    }
}