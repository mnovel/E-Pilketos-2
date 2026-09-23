<?php

namespace Database\Factories;

use App\Models\ClassRoom;
use App\Models\Election;
use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateFactory extends Factory
{
    public function definition(): array
    {
        static $noUrut = 0;
        $noUrut++;

        return [
            'election_id'   => Election::factory(),
            'class_id'      => ClassRoom::factory(),
            'no_urut'       => $noUrut,
            'nama'          => fake()->name(),
            'visi'          => fake()->sentence(),
            'misi'          => fake()->paragraph(),
            'program_kerja' => fake()->paragraph(),
        ];
    }
}
