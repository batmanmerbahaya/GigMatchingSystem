<?php

namespace Database\Factories;

use App\Enums\EntityType;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Every user is created together with the matching GIG_WORKER or
 * CONTRACT_ENTITY profile row, as registration does.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone_number' => '01'.fake()->numerify('#-#######'),
            'role' => UserRole::GigWorker,
            'remember_token' => Str::random(10),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            if ($user->isGigWorker()) {
                $user->gigWorker()->firstOrCreate([], ['bio' => fake()->sentence(12)]);
            } else {
                $user->contractEntity()->firstOrCreate([], ['entity_type' => EntityType::Individual]);
            }
        });
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function gigWorker(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::GigWorker,
        ]);
    }

    public function contractEntity(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::ContractEntity,
        ]);
    }

    public function business(string $organisation = 'Demo Enterprise Sdn Bhd'): static
    {
        return $this->contractEntity()->afterCreating(function (User $user) use ($organisation) {
            $user->contractEntity()->update([
                'entity_type' => EntityType::Business->value,
                'organisation_name' => $organisation,
            ]);
        });
    }
}
