<?php

namespace Tests\Feature\Authorization;

use App\Models\ClassRoom;
use App\Models\Election;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // ADMIN ONLY ROUTES
    // ==========================================

    public function test_admin_can_access_admin_routes()
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertStatus(200);
    }

    public function test_operator_cannot_access_admin_routes()
    {
        $operator = $this->createOperator();

        $this->actingAs($operator)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_voter_cannot_access_admin_routes()
    {
        $voter = $this->createVoter();

        $this->actingAs($voter)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_guest_redirected_to_login_from_admin_routes()
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    // ==========================================
    // OPERATOR ONLY ROUTES
    // ==========================================

    public function test_operator_can_access_operator_dashboard()
    {
        $operator = $this->createOperator();

        $this->actingAs($operator)
            ->get(route('operator.dashboard'))
            ->assertStatus(200);
    }

    public function test_admin_cannot_access_operator_only_dashboard()
    {
        // Route pakai 'role:operator' (hanya operator), admin TIDAK termasuk
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('operator.dashboard'))
            ->assertForbidden();
    }

    public function test_voter_cannot_access_operator_dashboard()
    {
        $voter = $this->createVoter();

        $this->actingAs($voter)
            ->get(route('operator.dashboard'))
            ->assertForbidden();
    }

    // ==========================================
    // VOTER ONLY ROUTES
    // ==========================================

    public function test_voter_can_access_voter_dashboard()
    {
        $voter = $this->createVoter();

        $this->actingAs($voter)
            ->get(route('voter.dashboard'))
            ->assertStatus(200);
    }

    public function test_admin_cannot_access_voter_dashboard()
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('voter.dashboard'))
            ->assertForbidden();
    }

    public function test_operator_cannot_access_voter_dashboard()
    {
        $operator = $this->createOperator();

        $this->actingAs($operator)
            ->get(route('voter.dashboard'))
            ->assertForbidden();
    }

    // ==========================================
    // MULTI-ROLE ROUTES (admin & operator)
    // ==========================================

    public function test_admin_can_access_device_checkin()
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('device.checkin.index'))
            ->assertStatus(200);
    }

    public function test_operator_can_access_device_checkin()
    {
        $operator = $this->createOperator();

        $this->actingAs($operator)
            ->get(route('device.checkin.index'))
            ->assertStatus(200);
    }

    public function test_voter_cannot_access_device_checkin()
    {
        $voter = $this->createVoter();

        $this->actingAs($voter)
            ->get(route('device.checkin.index'))
            ->assertForbidden();
    }

    public function test_guest_redirected_from_device_checkin()
    {
        $this->get(route('device.checkin.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_access_device_voting()
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('device.voting.index'))
            ->assertStatus(200);
    }

    public function test_operator_can_access_device_voting()
    {
        $operator = $this->createOperator();

        $this->actingAs($operator)
            ->get(route('device.voting.index'))
            ->assertStatus(200);
    }

    public function test_voter_cannot_access_device_voting()
    {
        $voter = $this->createVoter();

        $this->actingAs($voter)
            ->get(route('device.voting.index'))
            ->assertForbidden();
    }

    // ==========================================
    // PUBLIC ROUTES (tidak butuh login)
    // ==========================================

    public function test_guest_can_access_home()
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_guest_can_access_cek_status()
    {
        $this->get(route('cek-status.index'))->assertStatus(200);
    }

    public function test_guest_can_access_hasil_index()
    {
        $this->get(route('hasil.index'))->assertStatus(200);
    }

    public function test_guest_can_access_login()
    {
        $this->get(route('login'))->assertStatus(200);
    }

    public function test_guest_can_access_register()
    {
        $this->get(route('register'))->assertStatus(200);
    }

    // ==========================================
    // AUTHENTICATED ROUTES (semua role)
    // ==========================================

    public function test_admin_can_access_profile()
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('profile.index'))
            ->assertStatus(200);
    }

    public function test_operator_can_access_profile()
    {
        $operator = $this->createOperator();

        $this->actingAs($operator)
            ->get(route('profile.index'))
            ->assertStatus(200);
    }

    public function test_voter_can_access_profile()
    {
        $voter = $this->createVoter();

        $this->actingAs($voter)
            ->get(route('profile.index'))
            ->assertStatus(200);
    }

    // ==========================================
    // SCAN ROUTES (voter only)
    // ==========================================

    public function test_voter_can_access_scan_page()
    {
        $voter = $this->createVoter();

        $this->actingAs($voter)
            ->get(route('voter.scan'))
            ->assertStatus(200);
    }

    public function test_admin_cannot_access_voter_scan_page()
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('voter.scan'))
            ->assertForbidden();
    }

    // ==========================================
    // CUSTOM ROLE MIDDLEWARE ERROR MESSAGE
    // ==========================================

    public function test_forbidden_response_contains_correct_message()
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)->get(route('admin.dashboard'));

        $response->assertStatus(403);
        $response->assertSee('Akses Ditolak');
    }

    // ==========================================
    // SWITCHING ROLE USER
    // ==========================================

    public function test_user_can_access_their_own_role_dashboard_only()
    {
        $admin = $this->createAdmin();
        $operator = $this->createOperator();
        $voter = $this->createVoter();

        // Admin → admin dashboard OK
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertStatus(200);
        $this->actingAs($admin)->get(route('operator.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('voter.dashboard'))->assertForbidden();

        // Operator → operator dashboard OK
        $this->actingAs($operator)->get(route('operator.dashboard'))->assertStatus(200);
        $this->actingAs($operator)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($operator)->get(route('voter.dashboard'))->assertForbidden();

        // Voter → voter dashboard OK
        $this->actingAs($voter)->get(route('voter.dashboard'))->assertStatus(200);
        $this->actingAs($voter)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($voter)->get(route('operator.dashboard'))->assertForbidden();
    }
}
