<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_redirected_to_admin_dashboard()
    {
        $admin = $this->createAdmin();

        $response = $this->post('/login', [
            'email'    => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_voter_pending_cannot_login()
    {
        $voter = User::factory()->pending()->create(['role' => UserRole::VOTER]);
        $voter->assignRole('voter');

        $response = $this->post('/login', [
            'email'    => $voter->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_voter_rejected_cannot_login()
    {
        $voter = User::factory()->rejected()->create(['role' => UserRole::VOTER]);
        $voter->assignRole('voter');

        $response = $this->post('/login', [
            'email'    => $voter->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_rejected_logs_activity()
    {
        $voter = User::factory()->pending()->create(['role' => UserRole::VOTER]);
        $voter->assignRole('voter');

        $this->post('/login', [
            'email'    => $voter->email,
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action'  => 'auth.login_rejected',
            'user_id' => $voter->id,
        ]);
    }

    public function test_successful_login_logs_activity()
    {
        $admin = $this->createAdmin();

        $this->post('/login', [
            'email'    => $admin->email,
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action'  => 'auth.login',
            'user_id' => $admin->id,
        ]);
    }

    public function test_logout_logs_activity_with_correct_user()
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post('/logout');

        $this->assertDatabaseHas('activity_logs', [
            'action'  => 'auth.logout',
            'user_id' => $admin->id,
        ]);
    }
}
