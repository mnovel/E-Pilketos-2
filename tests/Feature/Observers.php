<?php

namespace Tests\Feature\Observers;

use App\Enums\ElectionStatus;
use App\Enums\SessionStatus;
use App\Models\Candidate;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\ElectionSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // =========================================================
    // ELECTION OBSERVER
    // =========================================================

    public function test_election_observer_clears_cache_on_create()
    {
        $this->seedElectionCache();

        Election::factory()->create();

        $this->assertFalse(Cache::has('home.active_election'));
        $this->assertFalse(Cache::has('home.published_election'));
        $this->assertFalse(Cache::has('admin.dashboard.stats'));
        $this->assertFalse(Cache::has('admin.live_stats'));
    }

    public function test_election_observer_clears_cache_on_update()
    {
        $election = Election::factory()->create();

        $this->seedElectionCache();

        $election->update(['title' => 'Judul Baru']);

        $this->assertFalse(Cache::has('home.active_election'));
        $this->assertFalse(Cache::has('home.published_election'));
        $this->assertFalse(Cache::has('admin.dashboard.stats'));
        $this->assertFalse(Cache::has('admin.live_stats'));
    }

    public function test_election_observer_clears_cache_on_delete()
    {
        $election = Election::factory()->create();

        $this->seedElectionCache();

        $election->delete();

        $this->assertFalse(Cache::has('home.active_election'));
        $this->assertFalse(Cache::has('admin.dashboard.stats'));
    }

    public function test_election_observer_clears_cache_on_restore()
    {
        $election = Election::factory()->create();
        $election->delete();

        $this->seedElectionCache();

        $election->restore();

        $this->assertFalse(Cache::has('home.active_election'));
        $this->assertFalse(Cache::has('admin.live_stats'));
    }

    // =========================================================
    // CANDIDATE OBSERVER
    // =========================================================

    public function test_candidate_observer_clears_cache_on_create()
    {
        $election = Election::factory()->create();

        Cache::put('home.active_election', 'cached-data', 60);
        $this->assertTrue(Cache::has('home.active_election'));

        Candidate::factory()->create(['election_id' => $election->id]);

        $this->assertFalse(Cache::has('home.active_election'));
    }

    public function test_candidate_observer_clears_cache_on_update()
    {
        $candidate = Candidate::factory()->create();

        Cache::put('home.active_election', 'cached-data', 60);

        $candidate->update(['nama' => 'Nama Baru']);

        $this->assertFalse(Cache::has('home.active_election'));
    }

    public function test_candidate_observer_clears_cache_on_delete()
    {
        $candidate = Candidate::factory()->create();

        Cache::put('home.active_election', 'cached-data', 60);

        $candidate->delete(); // soft delete

        $this->assertFalse(Cache::has('home.active_election'));
    }

    public function test_candidate_observer_clears_cache_on_restore()
    {
        $candidate = Candidate::factory()->create();
        $candidate->delete();

        Cache::put('home.active_election', 'cached-data', 60);

        $candidate->restore();

        $this->assertFalse(Cache::has('home.active_election'));
    }

    // =========================================================
    // ELECTION SESSION OBSERVER
    // =========================================================

    public function test_session_observer_clears_cache_on_create()
    {
        $election = Election::factory()->create();
        $class = ClassRoom::factory()->create();

        $this->seedSessionCache();

        ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
        ]);

        $this->assertFalse(Cache::has('admin.live_stats'));
        $this->assertFalse(Cache::has('operator.dashboard'));
    }

    public function test_session_observer_clears_cache_on_update()
    {
        $election = Election::factory()->create();
        $class = ClassRoom::factory()->create();

        $session = ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
        ]);

        $this->seedSessionCache();

        $session->update(['status' => SessionStatus::ACTIVE]);

        $this->assertFalse(Cache::has('admin.live_stats'));
        $this->assertFalse(Cache::has('operator.dashboard'));
    }

    public function test_session_observer_clears_cache_on_delete()
    {
        $election = Election::factory()->create();
        $class = ClassRoom::factory()->create();

        $session = ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
        ]);

        $this->seedSessionCache();

        $session->delete();

        $this->assertFalse(Cache::has('admin.live_stats'));
        $this->assertFalse(Cache::has('operator.dashboard'));
    }

    public function test_session_observer_does_not_clear_home_cache()
    {
        // ✅ Bikin election DULU (biar observer election tidak trigger setelah seed)
        $election = Election::factory()->create();
        $class = ClassRoom::factory()->create();

        $this->seedElectionCache();

        // Sekarang baru bikin session — election sudah ada
        ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
        ]);

        // Session observer tidak menyentuh home cache
        $this->assertTrue(Cache::has('home.active_election'));
        $this->assertTrue(Cache::has('home.published_election'));
    }

    // =========================================================
    // USER OBSERVER
    // =========================================================

    public function test_user_observer_clears_cache_on_create()
    {
        $this->seedDashboardCache();

        User::factory()->create();

        $this->assertFalse(Cache::has('admin.dashboard.stats'));
        $this->assertFalse(Cache::has('admin.live_stats'));
    }

    public function test_user_observer_clears_cache_on_update()
    {
        $user = User::factory()->create();

        $this->seedDashboardCache();

        $user->update(['name' => 'Nama Baru']);

        $this->assertFalse(Cache::has('admin.dashboard.stats'));
        $this->assertFalse(Cache::has('admin.live_stats'));
    }

    public function test_user_observer_clears_cache_on_delete()
    {
        $user = User::factory()->create();

        $this->seedDashboardCache();

        $user->delete();

        $this->assertFalse(Cache::has('admin.dashboard.stats'));
        $this->assertFalse(Cache::has('admin.live_stats'));
    }

    public function test_user_observer_does_not_clear_home_cache()
    {
        $this->seedElectionCache();

        User::factory()->create();

        // User observer tidak menyentuh home cache
        $this->assertTrue(Cache::has('home.active_election'));
        $this->assertTrue(Cache::has('home.published_election'));
    }

    public function test_user_observer_does_not_clear_operator_cache()
    {
        Cache::put('operator.dashboard', 'cached', 60);

        User::factory()->create();

        // User observer tidak menyentuh operator cache
        $this->assertTrue(Cache::has('operator.dashboard'));
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Seed semua cache yang di-clear ElectionObserver.
     */
    protected function seedElectionCache(): void
    {
        Cache::put('home.active_election', 'data', 60);
        Cache::put('home.published_election', 'data', 60);
        Cache::put('admin.dashboard.stats', 'data', 60);
        Cache::put('admin.live_stats', 'data', 60);
    }

    /**
     * Seed semua cache yang di-clear ElectionSessionObserver.
     */
    protected function seedSessionCache(): void
    {
        Cache::put('admin.live_stats', 'data', 60);
        Cache::put('operator.dashboard', 'data', 60);
    }

    /**
     * Seed semua cache yang di-clear UserObserver.
     */
    protected function seedDashboardCache(): void
    {
        Cache::put('admin.dashboard.stats', 'data', 60);
        Cache::put('admin.live_stats', 'data', 60);
    }
}
