<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(3, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(5)),
            'product_type' => 'simple',
            'description' => '<p>' . fake()->paragraph() . '</p>',
            'short_description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 5, 200),
            'sku' => 'SKU-' . Str::upper(Str::random(8)),
            'quantity' => 50,
            'category_id' => Category::factory(),
            'is_active' => true,
            'is_featured' => false,
        ];
    }

    public function variable(): static
    {
        return $this->state(fn () => ['product_type' => 'variable']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
