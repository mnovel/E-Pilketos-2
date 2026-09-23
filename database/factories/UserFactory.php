<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'password'          => Hash::make('password'),
            'remember_token'    => Str::random(10),
            'role'              => UserRole::VOTER,
            'status'            => VoterStatus::VERIFIED,
            'email_verified_at' => now(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn() => [
            'role'   => UserRole::ADMIN,
            'status' => VoterStatus::VERIFIED,
        ]);
    }

    public function operator(): static
    {
        return $this->state(fn() => [
            'role'   => UserRole::OPERATOR,
            'status' => VoterStatus::VERIFIED,
        ]);
    }

    public function voter(): static
    {
        return $this->state(fn() => [
            'role'   => UserRole::VOTER,
            'status' => VoterStatus::VERIFIED,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn() => ['status' => VoterStatus::PENDING]);
    }

    public function rejected(): static
    {
        return $this->state(fn() => [
            'status'        => VoterStatus::REJECTED,
            'alasan_reject' => 'Kartu pelajar tidak jelas',
        ]);
    }
}
