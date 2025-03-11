<?php

namespace Tests\Unit\WhiteBox;

use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->orderService = new OrderService();
    }

    /** @test */
    public function it_validates_product_stock_availability()
    {
        $product = Product::factory()->create(['stock' => 5]);
        
        // Caso 1: Cantidad solicitada menor que el stock disponible
        $result = $this->orderService->validateStock($product->id, 3);
        $this->assertTrue($result['valid']);
        
        // Caso 2: Cantidad solicitada igual al stock disponible
        $result = $this->orderService->validateStock($product->id, 5);
        $this->assertTrue($result['valid']);
        
        // Caso 3: Cantidad solicitada mayor que el stock disponible
        $result = $this->orderService->validateStock($product->id, 10);
        $this->assertFalse($result['valid']);
        $this->assertEquals("No hay suficiente stock para el producto. Stock disponible: 5", $result['message']);
    }

    /** @test */
    public function it_calculates_order_total_correctly()
    {
        $product1 = Product::factory()->create(['price' => 10.50]);
        $product2 = Product::factory()->create(['price' => 5.75]);
        
        $items = [
            ['product_id' => $product1->id, 'quantity' => 2],
            ['product_id' => $product2->id, 'quantity' => 3]
        ];
        
        // Total esperado: (10.50 * 2) + (5.75 * 3) = 21.00 + 17.25 = 38.25
        $total = $this->orderService->calculateTotal($items);
        
        $this->assertEquals(38.25, $total);
    }

    /** @test */
    public function it_processes_order_with_valid_data()
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['price' => 10.00, 'stock' => 10]);
        $product2 = Product::factory()->create(['price' => 15.00, 'stock' => 5]);
        
        $orderData = [
            'user_id' => $user->id,
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 2],
                ['product_id' => $product2->id, 'quantity' => 1]
            ],
            'shipping_address' => '123 Calle Principal',
            'payment_method' => 'credit_card'
        ];
        
        $result = $this->orderService->processOrder($orderData);
        
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['order']);
        $this->assertEquals(35.00, $result['order']->total);
        $this->assertEquals(8, $product1->fresh()->stock);
        $this->assertEquals(4, $product2->fresh()->stock);
    }

    /** @test */
    public function it_handles_insufficient_stock_during_order_processing()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 10.00, 'stock' => 3]);
        
        $orderData = [
            'user_id' => $user->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5]
            ],
            'shipping_address' => '123 Calle Principal',
            'payment_method' => 'credit_card'
        ];
        
        $result = $this->orderService->processOrder($orderData);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('stock', $result['message']);
        $this->assertEquals(3, $product->fresh()->stock); // El stock no debe cambiar
    }
}