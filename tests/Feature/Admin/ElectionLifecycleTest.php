<?php

namespace Tests\Feature\Admin;

use App\Enums\ElectionStatus;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_election()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.elections.store'), [
            'title'        => 'Pemilihan Ketua OSIS 2026',
            'tahun_ajaran' => '2025/2026',
            'deskripsi'    => 'Pemilihan digital',
            'start_at'     => now()->addHour()->format('Y-m-d H:i'),
            'end_at'       => now()->addHours(2)->format('Y-m-d H:i'),
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('elections', [
            'title'  => 'Pemilihan Ketua OSIS 2026',
            'status' => ElectionStatus::DRAFT->value,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'election.created',
        ]);
    }

    public function test_election_auto_activates_when_time_comes()
    {
        $election = Election::factory()->draft()->create([
            'start_at' => now()->subMinute(),
            'end_at'   => now()->addHour(),
        ]);

        // Butuh 2 kandidat minimal
        Candidate::factory()->count(2)->create(['election_id' => $election->id]);

        $activated = $election->autoActivateIfReady();

        $this->assertTrue($activated);
        $this->assertEquals(ElectionStatus::ACTIVE, $election->fresh()->status);
    }

    public function test_election_wont_activate_with_less_than_2_candidates()
    {
        $election = Election::factory()->draft()->create([
            'start_at' => now()->subMinute(),
            'end_at'   => now()->addHour(),
        ]);

        // Cuma 1 kandidat
        Candidate::factory()->create(['election_id' => $election->id]);

        $activated = $election->autoActivateIfReady();

        $this->assertFalse($activated);
        $this->assertEquals(ElectionStatus::DRAFT, $election->fresh()->status);
    }

    public function test_admin_can_close_active_election()
    {
        $admin = $this->createAdmin();
        $election = Election::factory()->active()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.elections.close', $election));

        $response->assertRedirect();
        $this->assertEquals(ElectionStatus::CLOSED, $election->fresh()->status);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'election.closed',
        ]);
    }

    public function test_admin_can_publish_closed_election_with_votes()
    {
        $admin = $this->createAdmin();
        $election = Election::factory()->closed()->create();

        // Bikin minimal 1 vote
        Vote::factory()->create(['election_id' => $election->id]);

        $response = $this->actingAs($admin)
            ->post(route('admin.elections.publish', $election));

        $response->assertRedirect();

        $election->refresh();
        $this->assertEquals(ElectionStatus::PUBLISHED, $election->status);
        $this->assertNotNull($election->hasil_published_at);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'election.published',
        ]);
    }

    public function test_cannot_publish_without_votes()
    {
        $admin = $this->createAdmin();
        $election = Election::factory()->closed()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.elections.publish', $election));

        $response->assertSessionHas('error');
        $this->assertEquals(ElectionStatus::CLOSED, $election->fresh()->status);
    }

    public function test_cannot_delete_active_election()
    {
        $admin = $this->createAdmin();
        $election = Election::factory()->active()->create();

        $response = $this->actingAs($admin)
            ->delete(route('admin.elections.destroy', $election));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('elections', ['id' => $election->id]);
    }
}
