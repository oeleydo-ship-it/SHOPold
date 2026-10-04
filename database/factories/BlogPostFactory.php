<?php

namespace Database\Factories;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<BlogPost> */
class BlogPostFactory extends Factory
{
    protected $model = BlogPost::class;

    public function definition(): array
    {
        $title = ucfirst(fake()->unique()->sentence(4));

        return [
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::lower(Str::random(4)),
            'excerpt' => fake()->sentence(),
            'content' => '<p>' . fake()->paragraph() . '</p>',
            'author_id' => User::factory()->admin(),
            'status' => 'published',
            'published_at' => now()->subDay(),
            'allow_comments' => true,
        ];
    }
}
