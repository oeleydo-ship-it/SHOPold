<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ProductVariation> */
class ProductVariationFactory extends Factory
{
    protected $model = ProductVariation::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory()->variable(),
            'sku' => 'VAR-' . Str::upper(Str::random(8)),
            'name' => ucfirst(fake()->word()),
            'price' => fake()->randomFloat(2, 5, 200),
            'quantity' => 20,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
