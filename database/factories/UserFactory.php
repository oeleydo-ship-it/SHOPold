<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_admin' => false,
        ];
    }

    /** An admin with the given role: super_admin, manager or editor. */
    public function admin(string $role = 'super_admin'): static
    {
        return $this->state(fn () => ['is_admin' => true, 'admin_role' => $role]);
    }

    public function driver(): static
    {
        return $this->state(fn () => ['is_driver' => true, 'driver_status' => 'available', 'driver_commission_rate' => 10]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
