<?php

namespace Database\Factories;

use App\Enums\ElectionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ElectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title'        => 'Pemilihan ' . fake()->word(),
            'tahun_ajaran' => '2025/2026',
            'deskripsi'    => fake()->sentence(),
            'start_at'     => now()->subMinute(),
            'end_at'       => now()->addHour(),
            'status'       => ElectionStatus::DRAFT,
            'created_by'   => User::factory()->admin(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn() => ['status' => ElectionStatus::DRAFT]);
    }

    public function active(): static
    {
        return $this->state(fn() => [
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subMinutes(5),
            'end_at'   => now()->addHour(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn() => [
            'status'   => ElectionStatus::CLOSED,
            'start_at' => now()->subHours(2),
            'end_at'   => now()->subHour(),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn() => [
            'status'             => ElectionStatus::PUBLISHED,
            'start_at'           => now()->subHours(2),
            'end_at'             => now()->subHour(),
            'hasil_published_at' => now()->subMinutes(30),
        ]);
    }
}
