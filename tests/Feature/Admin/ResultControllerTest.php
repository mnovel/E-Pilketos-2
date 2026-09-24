<?php

namespace Tests\Feature\Admin;

use App\Enums\ElectionStatus;
use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Models\ActivityLog;
use App\Models\Candidate;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\ElectionSession;
use App\Models\User;
use App\Models\Vote;
use App\Models\Voter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResultControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Bikin election + n kandidat di kelas random.
     * Return array [election, candidates].
     */
    protected function makeElectionWithCandidates(int $candidateCount = 2, array $overrides = []): array
    {
        $election = Election::factory()->create(array_merge([
            'status'             => ElectionStatus::CLOSED,
            'start_at'           => now()->subDays(2),
            'end_at'             => now()->subDay(),
        ], $overrides));

        $candidates = collect();

        for ($i = 1; $i <= $candidateCount; $i++) {
            $class = ClassRoom::factory()->create();

            $candidates->push(Candidate::factory()->create([
                'election_id' => $election->id,
                'class_id'    => $class->id,
                'no_urut'     => $i,
                'nama'        => "Kandidat {$i}",
            ]));
        }

        return [$election, $candidates];
    }

    /**
     * Bikin vote untuk kandidat tertentu sebanyak $count.
     */
    protected function makeVotesFor(Election $election, Candidate $candidate, int $count): void
    {
        $class = ClassRoom::factory()->create();
        $session = ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
        ]);

        for ($i = 0; $i < $count; $i++) {
            Vote::create([
                'election_id'  => $election->id,
                'session_id'   => $session->id,
                'candidate_id' => $candidate->id,
                'hash'         => hash('sha256', Str::uuid()),
            ]);
        }
    }

    /**
     * Bikin voter record.
     */
    protected function makeVoterRecord(Election $election, ClassRoom $class, array $overrides = []): Voter
    {
        $voterUser = $this->createVoter($class);

        return Voter::create(array_merge([
            'election_id' => $election->id,
            'user_id'     => $voterUser->id,
            'class_id'    => $class->id,
        ], $overrides));
    }

    // =========================================================
    // SHOW — ACCESS
    // =========================================================

    public function test_admin_can_view_result_page()
    {
        [$election] = $this->makeElectionWithCandidates();

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.elections.hasil', $election));

        $response->assertOk();
        $response->assertViewIs('admin.results.show');
        $response->assertViewHas('election');
        $response->assertViewHas('stats');
        $response->assertViewHas('candidates');
        $response->assertViewHas('sessions');
    }

    public function test_guest_redirected_from_result_page()
    {
        [$election] = $this->makeElectionWithCandidates();

        $this->get(route('admin.elections.hasil', $election))
            ->assertRedirect(route('login'));
    }

    public function test_operator_cannot_view_result_page()
    {
        [$election] = $this->makeElectionWithCandidates();

        $this->actingAs($this->createOperator())
            ->get(route('admin.elections.hasil', $election))
            ->assertForbidden();
    }

    public function test_voter_cannot_view_result_page()
    {
        [$election] = $this->makeElectionWithCandidates();

        $this->actingAs($this->createVoter())
            ->get(route('admin.elections.hasil', $election))
            ->assertForbidden();
    }

    // =========================================================
    // SHOW — STATS
    // =========================================================

    public function test_show_calculates_total_voters()
    {
        [$election] = $this->makeElectionWithCandidates();
        $class = ClassRoom::factory()->create();

        // 5 voter
        for ($i = 0; $i < 5; $i++) {
            $this->makeVoterRecord($election, $class);
        }

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.elections.hasil', $election));

        $response->assertViewHas('stats', function ($stats) {
            return $stats['total_voters'] === 5;
        });
    }

    public function test_show_calculates_total_voted()
    {
        [$election, $candidates] = $this->makeElectionWithCandidates();

        // 3 vote untuk kandidat 1
        $this->makeVotesFor($election, $candidates[0], 3);

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.elections.hasil', $election));

        $response->assertViewHas('stats', function ($stats) {
            return $stats['total_voted'] === 3;
        });
    }

    public function test_show_calculates_golput()
    {
        [$election, $candidates] = $this->makeElectionWithCandidates();
        $class = ClassRoom::factory()->create();

        // 10 voter, 3 vote → golput 7
        for ($i = 0; $i < 10; $i++) {
            $this->makeVoterRecord($election, $class);
        }
        $this->makeVotesFor($election, $candidates[0], 3);

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.elections.hasil', $election));

        $response->assertViewHas('stats', function ($stats) {
            return $stats['total_voters'] === 10
                && $stats['total_voted'] === 3
                && $stats['total_golput'] === 7;
        });
    }

    public function test_show_calculates_participation_percentage()
    {
        [$election, $candidates] = $this->makeElectionWithCandidates();
        $class = ClassRoom::factory()->create();

        // 4 voter, 3 vote = 75%
        for ($i = 0; $i < 4; $i++) {
            $this->makeVoterRecord($election, $class);
        }
        $this->makeVotesFor($election, $candidates[0], 3);

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.elections.hasil', $election));

        $response->assertViewHas('stats', function ($stats) {
            return $stats['participation_pct'] === 75.0;
        });
    }

    public function test_show_participation_zero_when_no_voters()
    {
        [$election] = $this->makeElectionWithCandidates();

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.elections.hasil', $election));

        $response->assertViewHas('stats', function ($stats) {
            return $stats['participation_pct'] === 0;
        });
    }

    // =========================================================
    // SHOW — CANDIDATE RESULTS
    // =========================================================

    public function test_show_candidate_votes_and_percentage()
    {
        [$election, $candidates] = $this->makeElectionWithCandidates(2);

        // Kandidat 1: 6 vote (60%)
        // Kandidat 2: 4 vote (40%)
        $this->makeVotesFor($election, $candidates[0], 6);
        $this->makeVotesFor($election, $candidates[1], 4);

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.elections.hasil', $election));

        $response->assertViewHas('candidates', function ($results) {
            $k1 = collect($results)->firstWhere('no_urut', 1);
            $k2 = collect($results)->firstWhere('no_urut', 2);

            return $k1['votes'] === 6
                && $k1['percentage'] === 60.0
                && $k2['votes'] === 4
                && $k2['percentage'] === 40.0;
        });
    }

    public function test_show_marks_winner_correctly()
    {
        [$election, $candidates] = $this->makeElectionWithCandidates(2);

        // Kandidat 1 menang
        $this->makeVotesFor($election, $candidates[0], 7);
        $this->makeVotesFor($election, $candidates[1], 3);

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.elections.hasil', $election));

        $response->assertViewHas('candidates', function ($results) {
            $k1 = collect($results)->firstWhere('no_urut', 1);
            $k2 = collect($results)->firstWhere('no_urut', 2);

            return $k1['is_winner'] === true
                && $k2['is_winner'] === false;
        });
    }

    public function test_show_marks_multiple_winners_when_tie()
    {
        [$election, $candidates] = $this->makeElectionWithCandidates(2);

        // Seri: 5 vote masing-masing
        $this->makeVotesFor($election, $candidates[0], 5);
        $this->makeVotesFor($election, $candidates[1], 5);

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.elections.hasil', $election));

        $response->assertViewHas('candidates', function ($results) {
            // Keduanya dianggap winner karena votes == max
            return $results[0]['is_winner'] === true
                && $results[1]['is_winner'] === true;
        });
    }

    public function test_show_ranks_candidates_by_votes_descending()
    {
        [$election, $candidates] = $this->makeElectionWithCandidates(3);

        // Kandidat 1: 3 vote, Kandidat 2: 7 vote, Kandidat 3: 5 vote
        $this->makeVotesFor($election, $candidates[0], 3);
        $this->makeVotesFor($election, $candidates[1], 7);
        $this->makeVotesFor($election, $candidates[2], 5);

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.elections.hasil', $election));

        $response->assertViewHas('candidates', function ($results) {
            // Urutan: kandidat 2 (rank 1), kandidat 3 (rank 2), kandidat 1 (rank 3)
            return $results[0]['no_urut'] === 2
                && $results[0]['rank'] === 1
                && $results[1]['no_urut'] === 3
                && $results[1]['rank'] === 2
                && $results[2]['no_urut'] === 1
                && $results[2]['rank'] === 3;
        });
    }

    public function test_show_candidate_zero_votes_handles_zero_percentage()
    {
        [$election, $candidates] = $this->makeElectionWithCandidates(2);

        // Tidak ada vote sama sekali

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.elections.hasil', $election));

        $response->assertViewHas('candidates', function ($results) {
            return collect($results)->every(fn($c) => $c['votes'] === 0 && $c['percentage'] === 0);
        });
    }

    public function test_show_candidate_zero_votes_not_marked_winner()
    {
        [$election, $candidates] = $this->makeElectionWithCandidates(2);

        // Kandidat 1: 3 vote, kandidat 2: 0 vote
        $this->makeVotesFor($election, $candidates[0], 3);

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.elections.hasil', $election));

        $response->assertViewHas('candidates', function ($results) {
            $k2 = collect($results)->firstWhere('no_urut', 2);
            return $k2['votes'] === 0
                && $k2['is_winner'] === false;
        });
    }

    // =========================================================
    // SHOW — SESSION RESULTS
    // =========================================================

    public function test_show_session_rekap_by_class()
    {
        [$election] = $this->makeElectionWithCandidates();

        $classA = ClassRoom::factory()->create(['name' => 'X-IPA-1']);
        $classB = ClassRoom::factory()->create(['name' => 'X-IPA-2']);

        $sessionA = ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $classA->id,
        ]);

        // Sesi A: 4 voter, 3 vote
        for ($i = 0; $i < 4; $i++) {
            $this->makeVoterRecord($election, $classA, [
                'session_id' => $sessionA->id,
                'has_voted'  => $i < 3,
                'voted_at'   => $i < 3 ? now() : null,
            ]);
        }

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.elections.hasil', $election));

        $response->assertViewHas('sessions', function ($sessions) {
            $s = collect($sessions)->firstWhere('kelas', 'X-IPA-1');

            return $s !== null
                && $s['total_voters'] === 4
                && $s['voted'] === 3
                && $s['golput'] === 1
                && $s['participation'] === 75.0;
        });
    }

    public function test_show_session_includes_runtime_status()
    {
        [$election] = $this->makeElectionWithCandidates();

        $class = ClassRoom::factory()->create();

        ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
            'status'      => SessionStatus::CLOSED,
        ]);

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.elections.hasil', $election));

        $response->assertViewHas('sessions', function ($sessions) {
            return count($sessions) > 0
                && isset($sessions[0]['status'])
                && isset($sessions[0]['status_label']);
        });
    }

    // =========================================================
    // EXPORT PDF — ACCESS & OUTPUT
    // =========================================================

    public function test_admin_can_export_pdf()
    {
        [$election] = $this->makeElectionWithCandidates();

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.elections.export-pdf', $election));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_export_pdf_filename_contains_slug_and_timestamp()
    {
        [$election] = $this->makeElectionWithCandidates(2, [
            'title' => 'Pemilihan Ketua OSIS 2026',
        ]);

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.elections.export-pdf', $election));

        $contentDisposition = $response->headers->get('content-disposition');

        $this->assertStringContainsString('hasil-pilketos-', $contentDisposition);
        $this->assertStringContainsString('.pdf', $contentDisposition);
        $this->assertStringContainsString('pemilihan-ketua-osis-2026', strtolower($contentDisposition));
    }

    public function test_export_pdf_logs_activity()
    {
        $admin = $this->createAdmin();
        [$election] = $this->makeElectionWithCandidates();

        $this->actingAs($admin)
            ->get(route('admin.elections.export-pdf', $election));

        $log = ActivityLog::where('action', 'result.exported_pdf')->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($election->id, $log->subject_id);
        $this->assertSame($election->title, $log->meta['title']);
    }

    public function test_export_pdf_guest_redirected()
    {
        [$election] = $this->makeElectionWithCandidates();

        $this->get(route('admin.elections.export-pdf', $election))
            ->assertRedirect(route('login'));
    }

    public function test_export_pdf_operator_forbidden()
    {
        [$election] = $this->makeElectionWithCandidates();

        $this->actingAs($this->createOperator())
            ->get(route('admin.elections.export-pdf', $election))
            ->assertForbidden();
    }

    // =========================================================
    // EXPORT EXCEL — ACCESS & OUTPUT
    // =========================================================

    public function test_admin_can_export_excel()
    {
        [$election] = $this->makeElectionWithCandidates();

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.elections.export-excel', $election));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    public function test_export_excel_filename_contains_slug_and_timestamp()
    {
        [$election] = $this->makeElectionWithCandidates(2, [
            'title' => 'Pemilihan Ketua OSIS 2026',
        ]);

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.elections.export-excel', $election));

        $contentDisposition = $response->headers->get('content-disposition');

        $this->assertStringContainsString('hasil-pilketos-', $contentDisposition);
        $this->assertStringContainsString('.xlsx', $contentDisposition);
    }

    public function test_export_excel_logs_activity()
    {
        $admin = $this->createAdmin();
        [$election] = $this->makeElectionWithCandidates();

        $this->actingAs($admin)
            ->get(route('admin.elections.export-excel', $election));

        $log = ActivityLog::where('action', 'result.exported_excel')->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($election->id, $log->subject_id);
        $this->assertSame($election->title, $log->meta['title']);
    }

    public function test_export_excel_guest_redirected()
    {
        [$election] = $this->makeElectionWithCandidates();

        $this->get(route('admin.elections.export-excel', $election))
            ->assertRedirect(route('login'));
    }

    public function test_export_excel_operator_forbidden()
    {
        [$election] = $this->makeElectionWithCandidates();

        $this->actingAs($this->createOperator())
            ->get(route('admin.elections.export-excel', $election))
            ->assertForbidden();
    }

    // =========================================================
    // INTEGRATION
    // =========================================================

    public function test_show_works_for_published_election()
    {
        [$election, $candidates] = $this->makeElectionWithCandidates(2, [
            'status'             => ElectionStatus::PUBLISHED,
            'hasil_published_at' => now(),
        ]);

        $this->makeVotesFor($election, $candidates[0], 5);

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.elections.hasil', $election));

        $response->assertOk();
    }

    public function test_show_works_for_election_with_no_candidates()
    {
        $election = Election::factory()->create([
            'status'   => ElectionStatus::CLOSED,
            'start_at' => now()->subDays(2),
            'end_at'   => now()->subDay(),
        ]);

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.elections.hasil', $election));

        $response->assertOk();
        $response->assertViewHas('candidates', fn($c) => count($c) === 0);
    }
}
