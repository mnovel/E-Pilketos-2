<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\ElectionSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VoteFactory extends Factory
{
    public function definition(): array
    {
        $election  = Election::factory()->create();
        $candidate = Candidate::factory()->create(['election_id' => $election->id]);
        $class     = ClassRoom::factory()->create();
        $session   = ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
        ]);

        return [
            'election_id'  => $election->id,
            'session_id'   => $session->id,
            'candidate_id' => $candidate->id,
            'hash'         => hash('sha256', Str::random(32)),
        ];
    }
}
