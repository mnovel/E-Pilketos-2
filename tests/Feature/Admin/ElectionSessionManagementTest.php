<?php

namespace Tests\Feature\Admin;

use App\Enums\ElectionStatus;
use App\Enums\SessionStatus;
use App\Enums\VoterStatus;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\ElectionSession;
use App\Models\Voter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectionSessionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    /**
     * Buat election aktif dengan window LUAS (2 jam sebelum - 4 jam sesudah).
     */
    protected function createWideElection(): Election
    {
        return Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHours(2),
            'end_at'   => now()->addHours(4),
        ]);
    }

    // ==========================================
    // STORE
    // ==========================================

    public function test_admin_can_create_session()
    {
        $this->travelTo(now()->setTime(10, 0));

        $admin = $this->createAdmin();
        $election = $this->createWideElection();
        $class = ClassRoom::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.sessions.store'), [
            'election_id'   => $election->id,
            'class_id'      => $class->id,
            'tanggal'       => now()->toDateString(),
            'waktu_mulai'   => '11:00',
            'waktu_selesai' => '11:30',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('election_sessions', [
            'election_id' => $election->id,
            'class_id'    => $class->id,
            'status'      => SessionStatus::SCHEDULED->value,
        ]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'session.created']);
    }

    public function test_create_session_fails_if_class_already_has_session()
    {
        $admin = $this->createAdmin();
        $election = $this->createWideElection();
        $class = ClassRoom::factory()->create();

        ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
        ]);

        $start = now()->addMinutes(10);
        $end   = now()->addMinutes(30);

        $response = $this->actingAs($admin)->post(route('admin.sessions.store'), [
            'election_id'   => $election->id,
            'class_id'      => $class->id,
            'tanggal'       => $start->toDateString(),
            'waktu_mulai'   => $start->format('H:i'),
            'waktu_selesai' => $end->format('H:i'),
        ]);

        $response->assertSessionHasErrors('class_id');
    }

    public function test_create_session_fails_if_outside_election_range()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create();

        $election = Election::factory()->create([
            'status'   => ElectionStatus::DRAFT,
            'start_at' => now()->addHours(2),
            'end_at'   => now()->addHours(4),
        ]);

        $sessionStart = now()->addMinutes(5);
        $sessionEnd   = now()->addMinutes(25);

        $response = $this->actingAs($admin)->post(route('admin.sessions.store'), [
            'election_id'   => $election->id,
            'class_id'      => $class->id,
            'tanggal'       => $sessionStart->toDateString(),
            'waktu_mulai'   => $sessionStart->format('H:i'),
            'waktu_selesai' => $sessionEnd->format('H:i'),
        ]);

        $response->assertSessionHasErrors('waktu_mulai');
    }

    public function test_create_session_fails_if_operator_conflict()
    {
        $this->travelTo(now()->setTime(10, 0));

        $admin = $this->createAdmin();
        $operator = $this->createOperator();
        $election = $this->createWideElection();

        $class1 = ClassRoom::factory()->create();
        $class2 = ClassRoom::factory()->create();

        // Existing session 11:00 - 12:00
        ElectionSession::create([
            'election_id'   => $election->id,
            'class_id'      => $class1->id,
            'operator_id'   => $operator->id,
            'tanggal'       => now()->toDateString(),
            'waktu_mulai'   => '11:00',
            'waktu_selesai' => '12:00',
            'status'        => SessionStatus::SCHEDULED,
        ]);

        // New session 11:30 - 12:30 → harus konflik
        $response = $this->actingAs($admin)->post(route('admin.sessions.store'), [
            'election_id'   => $election->id,
            'class_id'      => $class2->id,
            'tanggal'       => now()->toDateString(),
            'waktu_mulai'   => '11:30',
            'waktu_selesai' => '12:30',
            'operator_id'   => $operator->id,
        ]);

        $response->assertSessionHasErrors('operator_id');
    }

    public function test_create_session_without_operator_is_allowed()
    {
        $admin = $this->createAdmin();
        $election = $this->createWideElection();
        $class = ClassRoom::factory()->create();

        $start = now()->addMinutes(10);
        $end   = now()->addMinutes(30);

        $response = $this->actingAs($admin)->post(route('admin.sessions.store'), [
            'election_id'   => $election->id,
            'class_id'      => $class->id,
            'tanggal'       => $start->toDateString(),
            'waktu_mulai'   => $start->format('H:i'),
            'waktu_selesai' => $end->format('H:i'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('election_sessions', [
            'election_id' => $election->id,
            'operator_id' => null,
        ]);
    }

    // ==========================================
    // UPDATE
    // ==========================================

    public function test_admin_can_update_scheduled_session()
    {
        $this->travelTo(now()->setTime(10, 0));

        $admin = $this->createAdmin();

        $election = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->setTime(8, 0),
            'end_at'   => now()->setTime(16, 0),
        ]);

        $class = ClassRoom::factory()->create();

        $session = ElectionSession::factory()->create([
            'election_id'   => $election->id,
            'class_id'      => $class->id,
            'status'        => SessionStatus::SCHEDULED,
            'tanggal'       => now()->toDateString(),
            'waktu_mulai'   => '11:00',
            'waktu_selesai' => '11:30',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.sessions.update', $session), [
            'class_id'      => $class->id,
            'tanggal'       => now()->toDateString(),
            'waktu_mulai'   => '12:00',
            'waktu_selesai' => '12:30',
        ]);

        $response->assertRedirect();

        $session->refresh();

        // ✅ Kompatibel MySQL (12:00:00) & MariaDB (12:00)
        $this->assertStringStartsWith('12:00', $session->waktu_mulai);
        $this->assertStringStartsWith('12:30', $session->waktu_selesai);

        $this->assertDatabaseHas('activity_logs', ['action' => 'session.updated']);
    }

    public function test_cannot_update_active_session()
    {
        $admin = $this->createAdmin();
        $session = ElectionSession::factory()->active()->create();

        $response = $this->actingAs($admin)->put(route('admin.sessions.update', $session), [
            'class_id'      => $session->class_id,
            'tanggal'       => $session->tanggal->format('Y-m-d'),
            'waktu_mulai'   => $session->waktu_mulai,
            'waktu_selesai' => $session->waktu_selesai,
        ]);

        $response->assertSessionHas('error');
    }

    // ==========================================
    // DESTROY
    // ==========================================

    public function test_admin_can_delete_scheduled_session()
    {
        $admin = $this->createAdmin();
        $session = ElectionSession::factory()->create([
            'status' => SessionStatus::SCHEDULED,
        ]);
        $sessionId = $session->id;

        $response = $this->actingAs($admin)
            ->delete(route('admin.sessions.destroy', $session));

        $response->assertRedirect();
        $this->assertDatabaseMissing('election_sessions', ['id' => $sessionId]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'session.deleted']);
    }

    public function test_cannot_delete_active_session()
    {
        $admin = $this->createAdmin();
        $session = ElectionSession::factory()->active()->create();

        $response = $this->actingAs($admin)
            ->delete(route('admin.sessions.destroy', $session));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('election_sessions', ['id' => $session->id]);
    }

    // ==========================================
    // ASSIGN VOTERS
    // ==========================================

    public function test_assign_voters_assigns_verified_voters_from_same_class()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create();
        $election = $this->createWideElection();

        $session = ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
            'status'      => SessionStatus::SCHEDULED,
        ]);

        for ($i = 0; $i < 3; $i++) {
            $user = $this->createVoter($class);
            $user->update(['status' => VoterStatus::VERIFIED]);
        }

        $response = $this->actingAs($admin)
            ->post(route('admin.sessions.assign-voters', $session));

        $response->assertRedirect();
        $this->assertEquals(3, Voter::where('session_id', $session->id)->count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'session.assigned']);
    }

    public function test_assign_voters_only_assigns_matching_class()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create();
        $otherClass = ClassRoom::factory()->create();
        $election = $this->createWideElection();

        $session = ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
            'status'      => SessionStatus::SCHEDULED,
        ]);

        for ($i = 0; $i < 2; $i++) {
            $user = $this->createVoter($class);
            $user->update(['status' => VoterStatus::VERIFIED]);
        }

        for ($i = 0; $i < 3; $i++) {
            $user = $this->createVoter($otherClass);
            $user->update(['status' => VoterStatus::VERIFIED]);
        }

        $this->actingAs($admin)
            ->post(route('admin.sessions.assign-voters', $session));

        $this->assertEquals(2, Voter::where('session_id', $session->id)->count());
    }

    public function test_cannot_assign_voters_to_active_session()
    {
        $admin = $this->createAdmin();
        $session = ElectionSession::factory()->active()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.sessions.assign-voters', $session));

        $response->assertSessionHas('error');
    }

    // ==========================================
    // CLOSE
    // ==========================================

    public function test_admin_can_close_active_session()
    {
        $admin = $this->createAdmin();
        $session = ElectionSession::factory()->active()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.sessions.close', $session));

        $response->assertRedirect();

        $session->refresh();
        $this->assertEquals(SessionStatus::CLOSED, $session->status);
        $this->assertNotNull($session->closed_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'session.closed']);
    }

    public function test_cannot_close_scheduled_session()
    {
        $admin = $this->createAdmin();
        $session = ElectionSession::factory()->create([
            'status' => SessionStatus::SCHEDULED,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.sessions.close', $session));

        $response->assertSessionHas('error');
        $this->assertEquals(SessionStatus::SCHEDULED, $session->fresh()->status);
    }

    // ==========================================
    // AUTHORIZATION
    // ==========================================

    public function test_operator_cannot_manage_sessions()
    {
        $operator = $this->createOperator();
        $election = $this->createWideElection();
        $class = ClassRoom::factory()->create();

        $start = now()->addMinutes(10);
        $end   = now()->addMinutes(30);

        $response = $this->actingAs($operator)->post(route('admin.sessions.store'), [
            'election_id'   => $election->id,
            'class_id'      => $class->id,
            'tanggal'       => $start->toDateString(),
            'waktu_mulai'   => $start->format('H:i'),
            'waktu_selesai' => $end->format('H:i'),
        ]);

        $response->assertForbidden();
    }

    public function test_voter_cannot_manage_sessions()
    {
        $voter = $this->createVoter();
        $election = $this->createWideElection();
        $class = ClassRoom::factory()->create();

        $start = now()->addMinutes(10);
        $end   = now()->addMinutes(30);

        $response = $this->actingAs($voter)->post(route('admin.sessions.store'), [
            'election_id'   => $election->id,
            'class_id'      => $class->id,
            'tanggal'       => $start->toDateString(),
            'waktu_mulai'   => $start->format('H:i'),
            'waktu_selesai' => $end->format('H:i'),
        ]);

        $response->assertForbidden();
    }
}
