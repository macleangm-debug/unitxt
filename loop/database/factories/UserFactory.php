<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'country_code' => '+255',
            'phone' => (string) fake()->unique()->numerify('7########'),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => User::ROLE_CUSTOMER,
            'remember_token' => Str::random(10),
            'is_active' => true,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_ADMIN,
            'business_id' => null,
        ]);
    }

    public function owner(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_OWNER]);
    }

    public function frontDesk(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_FRONT_DESK]);
    }

    public function customer(): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_CUSTOMER,
            'password' => null,
        ]);
    }
}
