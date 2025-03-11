<?php

namespace Tests\Feature\BlackBox;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_all_products()
    {
        Product::factory()->count(5)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
                 ->assertJsonCount(5, 'data')
                 ->assertJsonStructure([
                     'data' => [
                         '*' => ['id', 'name', 'price', 'description', 'stock', 'image', 'category_id']
                     ]
                 ]);
    }

    /** @test */
    public function it_returns_a_single_product()
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'id' => $product->id,
                         'name' => $product->name,
                         'price' => $product->price,
                         'description' => $product->description,
                         'stock' => $product->stock,
                         'image' => $product->image,
                         'category_id' => $product->category_id
                     ]
                 ]);
    }

    /** @test */
    public function it_returns_404_for_non_existent_product()
    {
        $response = $this->getJson('/api/products/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_create_a_product()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        $productData = [
            'name' => 'Nuevo Pan',
            'price' => 3.50,
            'description' => 'Descripción del nuevo pan',
            'stock' => 50,
            'category_id' => $category->id,
            'image' => null
        ];

        $response = $this->actingAs($admin)
                         ->postJson('/api/products', $productData);

        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'name' => 'Nuevo Pan',
                     'price' => 3.50,
                     'description' => 'Descripción del nuevo pan',
                     'stock' => 50,
                     'category_id' => $category->id
                 ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Nuevo Pan',
            'price' => 3.50
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_a_product()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
                         ->postJson('/api/products', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'price', 'category_id']);
    }

    /** @test */
    public function it_can_update_a_product()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $newCategory = Category::factory()->create();

        $updatedData = [
            'name' => 'Pan Actualizado',
            'price' => 4.75,
            'description' => 'Descripción actualizada',
            'stock' => 30,
            'category_id' => $newCategory->id
        ];

        $response = $this->actingAs($admin)
                         ->putJson("/api/products/{$product->id}", $updatedData);

        $response->assertStatus(200)
                 ->assertJsonFragment($updatedData);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Pan Actualizado',
            'price' => 4.75
        ]);
    }

    /** @test */
    public function it_can_delete_a_product()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();

        $response = $this->actingAs($admin)
                         ->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id
        ]);
    }

    /** @test */
    public function it_prevents_non_admin_users_from_modifying_products()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create();

        $response = $this->actingAs($user)
                         ->postJson('/api/products', [
                             'name' => 'Nuevo Pan',
                             'price' => 3.50,
                             'category_id' => 1
                         ]);

        $response->assertStatus(403);

        $response = $this->actingAs($user)
                         ->putJson("/api/products/{$product->id}", [
                             'name' => 'Pan Actualizado'
                         ]);

        $response->assertStatus(403);

        $response = $this->actingAs($user)
                         ->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(403);
    }
}