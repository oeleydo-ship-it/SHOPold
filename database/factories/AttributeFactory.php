<?php

namespace Database\Factories;

use App\Models\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Attribute> */
class AttributeFactory extends Factory
{
    protected $model = Attribute::class;

    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word());

        return ['name' => $name, 'slug' => Str::slug($name) . '-' . Str::lower(Str::random(4)), 'type' => 'select', 'is_visible' => true, 'is_variation' => true, 'sort_order' => 0];
    }
}
