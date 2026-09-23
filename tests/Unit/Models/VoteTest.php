<?php

namespace Tests\Unit\Models;

use App\Models\Candidate;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\ElectionSession;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VoteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper: bikin vote lengkap dengan semua FK.
     */
    protected function createVoteWithRelations(): Vote
    {
        $election = Election::factory()->create();
        $candidate = Candidate::factory()->create(['election_id' => $election->id]);
        $class = ClassRoom::factory()->create();
        $session = ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
        ]);

        return Vote::create([
            'election_id'  => $election->id,
            'session_id'   => $session->id,
            'candidate_id' => $candidate->id,
            'hash'         => hash('sha256', Str::uuid()),
        ]);
    }

    // ==========================================
    // ANONIMITAS
    // ==========================================

    public function test_vote_table_has_no_user_id_column()
    {
        $vote = new Vote();
        $columns = $vote->getFillable();

        $this->assertNotContains('user_id', $columns);
        $this->assertNotContains('voter_id', $columns);
    }

    public function test_vote_fillable_only_contains_safe_columns()
    {
        $vote = new Vote();
        $expected = ['election_id', 'session_id', 'candidate_id', 'hash'];

        $this->assertEqualsCanonicalizing($expected, $vote->getFillable());
    }

    public function test_vote_can_be_created_without_user_info()
    {
        $vote = $this->createVoteWithRelations();

        $this->assertDatabaseHas('votes', ['id' => $vote->id]);
        $this->assertNull($vote->user_id ?? null);
    }

    // ==========================================
    // HASH
    // ==========================================

    public function test_hash_is_sha256_length()
    {
        $hash = hash('sha256', Str::uuid());
        $this->assertEquals(64, strlen($hash));
    }

    // ==========================================
    // RELATIONS
    // ==========================================

    public function test_belongs_to_election()
    {
        $vote = $this->createVoteWithRelations();

        $this->assertInstanceOf(Election::class, $vote->election);
    }

    public function test_belongs_to_candidate()
    {
        $vote = $this->createVoteWithRelations();

        $this->assertInstanceOf(Candidate::class, $vote->candidate);
    }

    public function test_belongs_to_session()
    {
        $vote = $this->createVoteWithRelations();

        $this->assertInstanceOf(ElectionSession::class, $vote->session);
    }

    // ==========================================
    // TIMESTAMPS
    // ==========================================

    public function test_timestamps_disabled_for_updated_at()
    {
        $vote = new Vote();
        $this->assertFalse($vote->usesTimestamps());
    }

    public function test_created_at_is_cast_to_datetime()
    {
        $vote = $this->createVoteWithRelations();
        $vote->refresh();

        $this->assertInstanceOf(\Carbon\Carbon::class, $vote->created_at);
    }
}
