<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Category;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_product()
    {
        $product = Product::factory()->create([
            'name' => 'Pan Francés',
            'price' => 2.50,
            'description' => 'Pan tradicional francés',
            'stock' => 100
        ]);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Pan Francés', $product->name);
        $this->assertEquals(2.50, $product->price);
        $this->assertEquals('Pan tradicional francés', $product->description);
        $this->assertEquals(100, $product->stock);
    }

    /** @test */
    public function it_belongs_to_a_category()
    {
        $category = Category::factory()->create(['name' => 'Panes']);
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $product->category);
        $this->assertEquals($category->id, $product->category->id);
    }

    /** @test */
    public function it_can_be_searched_by_name()
    {
        // Limpiar la base de datos antes de la prueba
        Product::query()->delete();
        
        // Crear productos con nombres específicos para la prueba
        Product::factory()->create(['name' => 'Pan Integral']);
        Product::factory()->create(['name' => 'Croissant']);
        Product::factory()->create(['name' => 'Pan de Centeno']);

        $results = Product::where('name', 'like', '%Pan%')->get();

        $this->assertEquals(2, $results->count());
        $this->assertTrue($results->contains('name', 'Pan Integral'));
        $this->assertTrue($results->contains('name', 'Pan de Centeno'));
    }

    /** @test */
    public function it_can_decrease_stock()
    {
        $product = Product::factory()->create(['stock' => 10]);
        
        $product->decreaseStock(3);
        
        $this->assertEquals(7, $product->stock);
    }
}