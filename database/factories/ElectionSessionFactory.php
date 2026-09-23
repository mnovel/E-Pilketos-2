<?php

namespace Database\Factories;

use App\Enums\SessionStatus;
use App\Models\ClassRoom;
use App\Models\Election;
use Illuminate\Database\Eloquent\Factories\Factory;

class ElectionSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'election_id'   => Election::factory(),
            'class_id'      => ClassRoom::factory(),
            'tanggal'       => now()->toDateString(),
            'waktu_mulai'   => now()->subMinutes(5)->format('H:i'),
            'waktu_selesai' => now()->addMinutes(10)->format('H:i'),
            'status'        => SessionStatus::SCHEDULED,
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn() => ['status' => SessionStatus::SCHEDULED]);
    }

    public function active(): static
    {
        return $this->state(fn() => [
            'status'       => SessionStatus::ACTIVE,
            'tanggal'      => now()->toDateString(),
            'waktu_mulai'  => now()->subMinutes(5)->format('H:i'),
            'waktu_selesai' => now()->addMinutes(5)->format('H:i'),
            'activated_at' => now()->subMinutes(5),
        ]);
    }
}
