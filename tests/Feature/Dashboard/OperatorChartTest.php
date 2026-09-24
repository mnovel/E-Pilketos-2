<?php

namespace Tests\Feature\Dashboard;

use App\Enums\ElectionStatus;
use App\Enums\SessionStatus;
use App\Models\Candidate;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\ElectionSession;
use App\Models\Vote;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class OperatorChartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // ==========================================
    // VIEW DATA
    // ==========================================

    public function test_operator_dashboard_has_chart_data()
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)->get(route('operator.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('chartData');
    }

    public function test_chart_data_structure_is_correct()
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)->get(route('operator.dashboard'));

        $response->assertViewHas('chartData', function ($data) {
            return isset($data['has_active_session'])
                && isset($data['partisipasi_sesi'])
                && isset($data['gauge_partisipasi']);
        });
    }

    // ==========================================
    // NO ACTIVE SESSION
    // ==========================================

    public function test_chart_data_no_active_session()
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)->get(route('operator.dashboard'));

        $response->assertViewHas('chartData', function ($data) {
            return $data['has_active_session'] === false
                && empty($data['partisipasi_sesi']['labels']);
        });
    }

    // ==========================================
    // PARTISIPASI PER SESI
    // ==========================================

    public function test_partisipasi_per_sesi_data()
    {
        $operator = $this->createOperator();
        $election = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHour(),
            'end_at'   => now()->addHour(),
        ]);

        $classA = ClassRoom::factory()->create(['name' => 'X-IPA-1']);
        $classB = ClassRoom::factory()->create(['name' => 'X-IPA-2']);

        // Sesi A: 4 voter, 3 vote, 4 check-in (75%, 100%)
        $this->createSessionWithData($election, $classA, 4, 4, 3);

        // Sesi B: 4 voter, 1 vote, 2 check-in (25%, 50%)
        $this->createSessionWithData($election, $classB, 4, 2, 1);

        $response = $this->actingAs($operator)->get(route('operator.dashboard'));

        $response->assertViewHas('chartData', function ($data) {
            $partisipasi = $data['partisipasi_sesi'];

            $this->assertCount(2, $partisipasi['labels']);
            $this->assertContains('X-IPA-1', $partisipasi['labels']);
            $this->assertContains('X-IPA-2', $partisipasi['labels']);

            $idx1 = array_search('X-IPA-1', $partisipasi['labels']);
            $idx2 = array_search('X-IPA-2', $partisipasi['labels']);

            // X-IPA-1: 3/4 = 75% vote, 4/4 = 100% check-in
            $this->assertEquals(75.0, $partisipasi['voted_pct'][$idx1]);
            $this->assertEquals(100.0, $partisipasi['checked_in_pct'][$idx1]);

            // X-IPA-2: 1/4 = 25% vote, 2/4 = 50% check-in
            $this->assertEquals(25.0, $partisipasi['voted_pct'][$idx2]);
            $this->assertEquals(50.0, $partisipasi['checked_in_pct'][$idx2]);

            return true;
        });
    }

    // ==========================================
    // GAUGE PARTISIPASI
    // ==========================================

    public function test_gauge_partisipasi_data()
    {
        $operator = $this->createOperator();
        $election = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHour(),
            'end_at'   => now()->addHour(),
        ]);

        $class = ClassRoom::factory()->create();
        // 10 voter, 7 vote (70%)
        $this->createSessionWithData($election, $class, 10, 10, 7);

        $response = $this->actingAs($operator)->get(route('operator.dashboard'));

        $response->assertViewHas('chartData', function ($data) {
            $gauge = $data['gauge_partisipasi'];

            $this->assertEquals(70.0, $gauge['percentage']);
            $this->assertEquals(10, $gauge['total_voters']);
            $this->assertEquals(7, $gauge['total_voted']);
            $this->assertEquals(3, $gauge['total_golput']);

            return true;
        });
    }

    public function test_gauge_partisipasi_zero_when_no_voters()
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)->get(route('operator.dashboard'));

        $response->assertViewHas('chartData', function ($data) {
            return $data['gauge_partisipasi']['percentage'] === 0.0
                && $data['gauge_partisipasi']['total_voters'] === 0
                && $data['gauge_partisipasi']['total_voted'] === 0;
        });
    }

    // ==========================================
    // AUTHORIZATION
    // ==========================================

    public function test_admin_cannot_access_operator_dashboard()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('operator.dashboard'));

        $response->assertForbidden();
    }

    // ==========================================
    // CACHE
    // ==========================================

    public function test_chart_data_is_cached()
    {
        $operator = $this->createOperator();

        $this->actingAs($operator)->get(route('operator.dashboard'));
        $this->assertTrue(Cache::has('operator.dashboard'));
    }

    // ==========================================
    // HELPERS
    // ==========================================

    private function createSessionWithData(
        Election $election,
        ClassRoom $class,
        int $total,
        int $checkedIn,
        int $voted
    ): ElectionSession {
        $candidate = Candidate::factory()->create(['election_id' => $election->id]);

        $session = ElectionSession::factory()->create([
            'election_id'   => $election->id,
            'class_id'      => $class->id,
            'status'        => SessionStatus::ACTIVE,
            'tanggal'       => now()->toDateString(),
            'waktu_mulai'   => now()->subMinutes(10)->format('H:i'),
            'waktu_selesai' => now()->addMinutes(20)->format('H:i'),
        ]);

        for ($i = 0; $i < $total; $i++) {
            $user = $this->createVoter($class);

            $isCheckedIn = $i < $checkedIn;
            $hasVoted    = $i < $voted;

            Voter::create([
                'election_id'   => $election->id,
                'user_id'       => $user->id,
                'class_id'      => $class->id,
                'session_id'    => $session->id,
                'checked_in'    => $isCheckedIn,
                'checked_in_at' => $isCheckedIn ? now() : null,
                'has_voted'     => $hasVoted,
                'voted_at'      => $hasVoted ? now() : null,
            ]);

            if ($hasVoted) {
                Vote::create([
                    'election_id'  => $election->id,
                    'session_id'   => $session->id,
                    'candidate_id' => $candidate->id,
                    'hash'         => hash('sha256', Str::uuid()),
                ]);
            }
        }

        return $session;
    }
}
