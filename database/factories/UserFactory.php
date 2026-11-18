<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /** A learner who has filled the participant form, so their CV is unlocked. */
    public function cvReady(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => $attributes['gender'] ?? 'male',
            'date_of_birth' => $attributes['date_of_birth'] ?? '1999-01-01',
            'phone' => $attributes['phone'] ?? '08030000000',
            'state' => $attributes['state'] ?? 'Kano',
            'has_disability' => 'no',
            'displacement_status' => 'none',
            'education_level' => 'secondary',
            'main_work' => $attributes['main_work'] ?? 'self_employed_agri',
            'income_band' => 'under_30000',
            'value_chain_track' => 'poultry',
            'training_objective' => 'expand_enterprise',
        ]);
    }
}
