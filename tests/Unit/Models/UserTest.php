<?php

namespace Tests\Unit\Models;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // ROLE HELPERS
    // ==========================================

    public function test_is_admin_returns_true_for_admin()
    {
        $user = User::factory()->make(['role' => UserRole::ADMIN]);
        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isOperator());
        $this->assertFalse($user->isVoter());
    }

    public function test_is_operator_returns_true_for_operator()
    {
        $user = User::factory()->make(['role' => UserRole::OPERATOR]);
        $this->assertTrue($user->isOperator());
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isVoter());
    }

    public function test_is_voter_returns_true_for_voter()
    {
        $user = User::factory()->make(['role' => UserRole::VOTER]);
        $this->assertTrue($user->isVoter());
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isOperator());
    }

    // ==========================================
    // STATUS HELPERS
    // ==========================================

    public function test_is_verified_returns_true_for_verified_status()
    {
        $user = User::factory()->make(['status' => VoterStatus::VERIFIED]);
        $this->assertTrue($user->isVerified());
        $this->assertFalse($user->isPending());
    }

    public function test_is_pending_returns_true_for_pending_status()
    {
        $user = User::factory()->make(['status' => VoterStatus::PENDING]);
        $this->assertTrue($user->isPending());
        $this->assertFalse($user->isVerified());
    }

    // ==========================================
    // RELATIONS
    // ==========================================

    public function test_belongs_to_class_room()
    {
        $class = ClassRoom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);

        $this->assertInstanceOf(ClassRoom::class, $user->classRoom);
        $this->assertEquals($class->id, $user->classRoom->id);
    }

    public function test_has_one_voter()
    {
        $voter = $this->createVoter();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasOne::class,
            $voter->voter()
        );
    }

    public function test_has_many_operated_sessions()
    {
        $operator = $this->createOperator();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $operator->operatedSessions()
        );
    }

    public function test_has_many_activity_logs()
    {
        $user = $this->createAdmin();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $user->activityLogs()
        );
    }

    // ==========================================
    // SPATIE ROLE
    // ==========================================

    public function test_user_can_be_assigned_spatie_role()
    {
        $user = $this->createVoter();

        $this->assertTrue($user->hasRole('voter'));
    }

    public function test_admin_has_admin_role()
    {
        $admin = $this->createAdmin();

        $this->assertTrue($admin->hasRole('admin'));
    }

    // ==========================================
    // CASTS
    // ==========================================

    public function test_role_is_cast_to_enum()
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->assertInstanceOf(UserRole::class, $user->role);
        $this->assertEquals(UserRole::ADMIN, $user->role);
    }

    public function test_status_is_cast_to_enum()
    {
        $user = User::factory()->create(['status' => VoterStatus::VERIFIED]);

        $this->assertInstanceOf(VoterStatus::class, $user->status);
        $this->assertEquals(VoterStatus::VERIFIED, $user->status);
    }

    public function test_password_is_hidden()
    {
        $user = User::factory()->create();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }
}
