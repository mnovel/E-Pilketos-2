<?php

namespace Database\Factories;

use App\Models\Election;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VoterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'election_id' => Election::factory(),
            'user_id'     => User::factory()->voter(),
            'class_id'    => null,
            'checked_in'  => false,
            'has_voted'   => false,
        ];
    }
}
