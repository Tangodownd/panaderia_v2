<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word . ' ' . $this->faker->word,
            'price' => $this->faker->randomFloat(2, 1, 50),
            'description' => $this->faker->sentence,
            'stock' => $this->faker->numberBetween(0, 100),
            'category_id' => Category::factory(),
            'image' => 'products/default-product.jpg' // Imagen estática en lugar de generada
        ];
    }
}