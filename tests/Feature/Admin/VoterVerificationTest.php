<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoterVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_admin_can_approve_pending_voter()
    {
        $admin = $this->createAdmin();
        $voter = User::factory()->pending()->create(['role' => UserRole::VOTER]);
        $voter->assignRole('voter');

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.approve', $voter));

        $response->assertRedirect();

        $voter->refresh();
        $this->assertEquals(VoterStatus::VERIFIED, $voter->status);
        $this->assertEquals($admin->id, $voter->verified_by);

        $this->assertDatabaseHas('activity_logs', [
            'action'     => 'voter.verified',
            'subject_id' => $voter->id,
        ]);
    }

    public function test_admin_can_reject_voter_with_reason()
    {
        $admin = $this->createAdmin();
        $voter = User::factory()->pending()->create(['role' => UserRole::VOTER]);
        $voter->assignRole('voter');

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.reject', $voter), [
                'alasan_reject' => 'Kartu pelajar tidak jelas',
            ]);

        $response->assertRedirect();

        $voter->refresh();
        $this->assertEquals(VoterStatus::REJECTED, $voter->status);
        $this->assertEquals('Kartu pelajar tidak jelas', $voter->alasan_reject);

        $this->assertDatabaseHas('activity_logs', [
            'action'     => 'voter.rejected',
            'subject_id' => $voter->id,
        ]);
    }

    public function test_reject_requires_alasan()
    {
        $admin = $this->createAdmin();
        $voter = User::factory()->pending()->create(['role' => UserRole::VOTER]);
        $voter->assignRole('voter');

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.reject', $voter), []);

        $response->assertSessionHasErrors('alasan_reject');
    }

    public function test_bulk_approve_verifies_multiple_voters()
    {
        $admin = $this->createAdmin();
        $voters = User::factory()->pending()->count(5)->create([
            'role' => UserRole::VOTER,
        ]);
        $voters->each(fn($v) => $v->assignRole('voter'));

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.bulk-approve'), [
                'ids' => $voters->pluck('id')->toArray(),
            ]);

        $response->assertRedirect();

        $verifiedCount = User::where('role', UserRole::VOTER)
            ->where('status', VoterStatus::VERIFIED)
            ->count();

        $this->assertEquals(5, $verifiedCount);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'voter.bulk_verified',
        ]);
    }

    public function test_non_admin_cannot_approve_voter()
    {
        $operator = $this->createOperator();
        $voter = User::factory()->pending()->create(['role' => UserRole::VOTER]);
        $voter->assignRole('voter');

        $response = $this->actingAs($operator)
            ->post(route('admin.voters.approve', $voter));

        $response->assertForbidden();
    }
}
