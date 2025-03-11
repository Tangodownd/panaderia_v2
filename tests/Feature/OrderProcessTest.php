<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class OrderProcessTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_user_can_place_an_order()
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['stock' => 10]);
        $product2 = Product::factory()->create(['stock' => 5]);

        // Autenticar al usuario con Sanctum
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 2],
                ['product_id' => $product2->id, 'quantity' => 1],
            ],
            'shipping_address' => '123 Calle Principal',
            'payment_method' => 'credit_card'
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['id', 'total', 'status']);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'shipping_address' => '123 Calle Principal',
            'payment_method' => 'credit_card'
        ]);

        $order = Order::where('user_id', $user->id)->first();

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'price' => $product1->price
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'price' => $product2->price
        ]);

        // Verificar que el stock se haya actualizado
        $this->assertEquals(8, $product1->fresh()->stock);
        $this->assertEquals(4, $product2->fresh()->stock);
    }

    /** @test */
    public function it_validates_order_data()
    {
        $user = User::factory()->create();
        
        // Autenticar al usuario con Sanctum
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/orders', [
            'items' => [],
            'shipping_address' => '',
            'payment_method' => ''
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['items', 'shipping_address', 'payment_method']);
    }

    /** @test */
    public function it_prevents_ordering_out_of_stock_products()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 2]);
        
        // Autenticar al usuario con Sanctum
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
            'shipping_address' => '123 Calle Principal',
            'payment_method' => 'credit_card'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['items.0.quantity']);
    }
}