<?php

namespace Database\Factories;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AttributeValue> */
class AttributeValueFactory extends Factory
{
    protected $model = AttributeValue::class;

    public function definition(): array
    {
        $value = ucfirst(fake()->unique()->safeColorName());

        return ['attribute_id' => Attribute::factory(), 'value' => $value, 'slug' => Str::slug($value) . '-' . Str::lower(Str::random(4)), 'sort_order' => 0];
    }
}
