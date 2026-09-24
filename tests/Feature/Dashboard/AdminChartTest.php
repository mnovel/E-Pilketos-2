<?php

namespace Tests\Feature\Dashboard;

use App\Enums\ElectionStatus;
use App\Enums\ElectionStatus as Status;
use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Models\Candidate;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\ElectionSession;
use App\Models\User;
use App\Models\Vote;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminChartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush(); // Reset cache antar test
    }

    // ==========================================
    // VIEW DATA
    // ==========================================

    public function test_admin_dashboard_has_chart_data()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('chartData');
    }

    public function test_chart_data_structure_is_correct()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertViewHas('chartData', function ($data) {
            return isset($data['has_active_election'])
                && isset($data['partisipasi_per_kelas'])
                && isset($data['status_pemilih'])
                && isset($data['trend_voting']);
        });
    }

    // ==========================================
    // NO ACTIVE ELECTION
    // ==========================================

    public function test_chart_data_no_active_election()
    {
        $admin = $this->createAdmin();

        // Tidak ada election aktif
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertViewHas('chartData', function ($data) {
            return $data['has_active_election'] === false
                && empty($data['partisipasi_per_kelas']['labels'])
                && empty($data['trend_voting']['labels']);
        });
    }

    // ==========================================
    // PARTISIPASI PER KELAS
    // ==========================================

    public function test_partisipasi_per_kelas_data()
    {
        $admin = $this->createAdmin();
        $election = Election::factory()->create([
            'status'   => Status::ACTIVE,
            'start_at' => now()->subHour(),
            'end_at'   => now()->addHour(),
        ]);

        $classA = ClassRoom::factory()->create(['name' => 'X-IPA-1']);
        $classB = ClassRoom::factory()->create(['name' => 'X-IPA-2']);

        // Kelas A: 2 voter, 2 vote (100%)
        $this->createVotersWithVotes($election, $classA, 2, 2);

        // Kelas B: 4 voter, 2 vote (50%)
        $this->createVotersWithVotes($election, $classB, 4, 2);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertViewHas('chartData', function ($data) {
            $partisipasi = $data['partisipasi_per_kelas'];

            // Ada 2 kelas
            $this->assertCount(2, $partisipasi['labels']);
            $this->assertContains('X-IPA-1', $partisipasi['labels']);
            $this->assertContains('X-IPA-2', $partisipasi['labels']);

            // Cek percentage
            $idx1 = array_search('X-IPA-1', $partisipasi['labels']);
            $idx2 = array_search('X-IPA-2', $partisipasi['labels']);

            $this->assertEquals(100.0, $partisipasi['voted_pct'][$idx1]);
            $this->assertEquals(50.0, $partisipasi['voted_pct'][$idx2]);

            return true;
        });
    }

    public function test_partisipasi_per_kelas_empty_when_no_voters()
    {
        $admin = $this->createAdmin();
        $election = Election::factory()->create([
            'status'   => Status::ACTIVE,
            'start_at' => now()->subHour(),
            'end_at'   => now()->addHour(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertViewHas('chartData', function ($data) {
            return empty($data['partisipasi_per_kelas']['labels']);
        });
    }

    // ==========================================
    // STATUS PEMILIH
    // ==========================================

    public function test_status_pemilih_counts()
    {
        $admin = $this->createAdmin();

        // 3 verified
        User::factory()->count(3)->voter()->create([
            'role'   => UserRole::VOTER,
            'status' => VoterStatus::VERIFIED,
        ]);

        // 2 pending
        User::factory()->count(2)->pending()->create(['role' => UserRole::VOTER]);

        // 1 rejected
        User::factory()->count(1)->rejected()->create(['role' => UserRole::VOTER]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertViewHas('chartData', function ($data) {
            return $data['status_pemilih']['verified'] === 3
                && $data['status_pemilih']['pending'] === 2
                && $data['status_pemilih']['rejected'] === 1;
        });
    }

    // ==========================================
    // TREND VOTING
    // ==========================================

    public function test_trend_voting_per_jam()
    {
        $admin = $this->createAdmin();
        $election = Election::factory()->create([
            'status'   => Status::ACTIVE,
            'start_at' => now()->subHours(2),
            'end_at'   => now()->addHour(),
        ]);

        $candidate = Candidate::factory()->create(['election_id' => $election->id]);
        $class = ClassRoom::factory()->create();
        $session = ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
        ]);

        // 3 vote di jam 08, 2 vote di jam 09, 1 vote di jam 10
        $this->createVotesAtHour($election, $session, $candidate, 8, 3);
        $this->createVotesAtHour($election, $session, $candidate, 9, 2);
        $this->createVotesAtHour($election, $session, $candidate, 10, 1);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertViewHas('chartData', function ($data) {
            $trend = $data['trend_voting'];

            $this->assertCount(3, $trend['labels']);     // 08:00, 09:00, 10:00
            $this->assertEquals([3, 2, 1], $trend['counts']);

            return true;
        });
    }

    public function test_trend_voting_empty_when_no_votes()
    {
        $admin = $this->createAdmin();
        Election::factory()->create([
            'status'   => Status::ACTIVE,
            'start_at' => now()->subHour(),
            'end_at'   => now()->addHour(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertViewHas('chartData', function ($data) {
            return empty($data['trend_voting']['labels'])
                && empty($data['trend_voting']['counts']);
        });
    }

    // ==========================================
    // CACHE
    // ==========================================

    public function test_chart_data_is_cached()
    {
        $admin = $this->createAdmin();

        // Request pertama
        $this->actingAs($admin)->get(route('admin.dashboard'));
        $this->assertTrue(Cache::has('admin.dashboard.charts'));

        // Request kedua
        $this->actingAs($admin)->get(route('admin.dashboard'));
        $this->assertTrue(Cache::has('admin.dashboard.charts'));
    }

    // ==========================================
    // HELPERS
    // ==========================================

    private function createVotersWithVotes(Election $election, ClassRoom $class, int $total, int $voted): void
    {
        $candidate = Candidate::factory()->create(['election_id' => $election->id]);
        $session = ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
        ]);

        for ($i = 0; $i < $total; $i++) {
            $user = $this->createVoter($class);

            $voter = Voter::create([
                'election_id' => $election->id,
                'user_id'     => $user->id,
                'class_id'    => $class->id,
                'session_id'  => $session->id,
                'checked_in'  => true,
                'has_voted'   => $i < $voted,
            ]);

            if ($i < $voted) {
                Vote::create([
                    'election_id'  => $election->id,
                    'session_id'   => $session->id,
                    'candidate_id' => $candidate->id,
                    'hash'         => hash('sha256', Str::uuid()),
                ]);
            }
        }
    }

    private function createVotesAtHour(Election $election, ElectionSession $session, Candidate $candidate, int $hour, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            // ✅ Instantiate manual + set created_at + save
            $vote = new Vote([
                'election_id'  => $election->id,
                'session_id'   => $session->id,
                'candidate_id' => $candidate->id,
                'hash'         => hash('sha256', Str::uuid()),
            ]);
            $vote->created_at = now()->setTime($hour, 0, 0);
            $vote->save();
        }
    }
}
