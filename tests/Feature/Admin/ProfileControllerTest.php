<?php

namespace Tests\Feature\Admin;

use App\Enums\VoterStatus;
use App\Models\ActivityLog;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    // ==========================================
    // INDEX — ACCESS
    // ==========================================

    public function test_admin_can_view_profile()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('profile.index'));

        $response->assertOk();
        $response->assertViewIs('admin.profile.index');
        $response->assertViewHas('user');
    }

    public function test_operator_can_view_profile()
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)->get(route('profile.index'));

        $response->assertOk();
    }

    public function test_voter_can_view_profile()
    {
        $voter = $this->createVoter();

        $response = $this->actingAs($voter)->get(route('profile.index'));

        $response->assertOk();
    }

    public function test_guest_redirected_to_login()
    {
        $this->get(route('profile.index'))
            ->assertRedirect(route('login'));
    }

    public function test_voter_profile_loads_class_room_and_verifier()
    {
        $class = ClassRoom::factory()->create();
        $voter = $this->createVoter($class);
        $voter->update([
            'status'      => VoterStatus::VERIFIED,
            'verified_by' => $this->createAdmin()->id,
            'verified_at' => now(),
        ]);

        $response = $this->actingAs($voter)->get(route('profile.index'));

        $response->assertViewHas('user', function ($u) {
            return $u->relationLoaded('classRoom')
                && $u->relationLoaded('verifier');
        });
    }

    public function test_admin_profile_does_not_load_voter_relations()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('profile.index'));

        $response->assertViewHas('user', function ($u) {
            return !$u->relationLoaded('classRoom')
                && !$u->relationLoaded('verifier');
        });
    }

    // ==========================================
    // UPDATE — HAPPY PATH
    // ==========================================

    public function test_admin_can_update_name_and_email()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->put(route('profile.update'), [
            'name'  => 'Nama Baru',
            'email' => 'baru@pilketos.test',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $admin->refresh();
        $this->assertSame('Nama Baru', $admin->name);
        $this->assertSame('baru@pilketos.test', $admin->email);
    }

    public function test_operator_can_update_own_profile()
    {
        $operator = $this->createOperator();

        $this->actingAs($operator)->put(route('profile.update'), [
            'name'  => 'Operator Baru',
            'email' => 'op-baru@pilketos.test',
        ]);

        $this->assertSame('Operator Baru', $operator->fresh()->name);
    }

    public function test_voter_can_update_own_profile()
    {
        $voter = $this->createVoter();

        $this->actingAs($voter)->put(route('profile.update'), [
            'name'  => 'Voter Baru',
            'email' => 'voter-baru@pilketos.test',
        ]);

        $this->assertSame('Voter Baru', $voter->fresh()->name);
    }

    public function test_update_does_not_change_role_or_status()
    {
        $voter = $this->createVoter();

        // ✅ Capture nilai original SEBELUM request
        $originalStatus = $voter->status;
        $originalNis    = $voter->nis;
        $originalRole   = $voter->role;

        $this->actingAs($voter)->put(route('profile.update'), [
            'name'  => 'Coba Hack',
            'email' => 'hack@pilketos.test',
        ]);

        $voter->refresh();

        // Role, status, nis tidak berubah
        $this->assertTrue($voter->isVoter());
        $this->assertSame($originalRole, $voter->role);
        $this->assertSame($originalStatus, $voter->status);
        $this->assertSame($originalNis, $voter->nis);
    }

    public function test_update_allows_same_email_for_self()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->put(route('profile.update'), [
            'name'  => $admin->name,
            'email' => $admin->email,
        ]);

        $response->assertSessionHasNoErrors();
    }

    // ==========================================
    // UPDATE — VALIDATION
    // ==========================================

    public function test_update_requires_name()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->put(route('profile.update'), [
            'name'  => '',
            'email' => $admin->email,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_update_requires_email()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->put(route('profile.update'), [
            'name'  => $admin->name,
            'email' => '',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_update_rejects_invalid_email()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->put(route('profile.update'), [
            'name'  => $admin->name,
            'email' => 'bukan-email',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_update_rejects_email_used_by_other_user()
    {
        $admin = $this->createAdmin();
        $other = $this->createAdmin();

        $response = $this->actingAs($admin)->put(route('profile.update'), [
            'name'  => $admin->name,
            'email' => $other->email,
        ]);

        $response->assertSessionHasErrors('email');
    }

    // ==========================================
    // UPDATE — ACTIVITY LOG
    // ==========================================

    public function test_update_logs_activity_when_name_changed()
    {
        $admin = $this->createAdmin();

        // ✅ Capture nama original SEBELUM request
        $originalName = $admin->name;

        $this->actingAs($admin)->put(route('profile.update'), [
            'name'  => 'Nama Berubah',
            'email' => $admin->email,
        ]);

        $log = ActivityLog::where('action', 'profile.updated')->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertArrayHasKey('name', $log->meta['changes']);
        $this->assertSame($originalName, $log->meta['changes']['name']['from']);
        $this->assertSame('Nama Berubah', $log->meta['changes']['name']['to']);
    }

    public function test_update_logs_activity_when_email_changed()
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->put(route('profile.update'), [
            'name'  => $admin->name,
            'email' => 'email-baru@pilketos.test',
        ]);

        $log = ActivityLog::where('action', 'profile.updated')->first();

        $this->assertNotNull($log);
        $this->assertArrayHasKey('email', $log->meta['changes']);
    }

    public function test_update_does_not_log_when_nothing_changed()
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->put(route('profile.update'), [
            'name'  => $admin->name,
            'email' => $admin->email,
        ]);

        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'profile.updated',
        ]);
    }

    public function test_update_logs_never_contain_password()
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->put(route('profile.update'), [
            'name'  => 'Nama Baru',
            'email' => $admin->email,
        ]);

        $log = ActivityLog::where('action', 'profile.updated')->first();

        $this->assertNotNull($log);
        $this->assertArrayNotHasKey('password', $log->meta['changes']);
    }

    // ==========================================
    // UPDATE PASSWORD — HAPPY PATH
    // ==========================================

    public function test_user_can_change_password()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->put(route('profile.password'), [
            'current_password'      => 'password',
            'password'              => 'password-baru',
            'password_confirmation' => 'password-baru',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('password-baru', $admin->fresh()->password));
    }

    public function test_change_password_does_not_affect_other_users()
    {
        $admin = $this->createAdmin();
        $other = $this->createAdmin();
        $otherOldHash = $other->password;

        $this->actingAs($admin)->put(route('profile.password'), [
            'current_password'      => 'password',
            'password'              => 'password-baru',
            'password_confirmation' => 'password-baru',
        ]);

        $this->assertSame($otherOldHash, $other->fresh()->password);
    }

    // ==========================================
    // UPDATE PASSWORD — VALIDATION
    // ==========================================

    public function test_change_password_requires_current_password()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->put(route('profile.password'), [
            'current_password'      => '',
            'password'              => 'password-baru',
            'password_confirmation' => 'password-baru',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_change_password_rejects_wrong_current_password()
    {
        $admin = $this->createAdmin();
        $oldHash = $admin->password;

        $response = $this->actingAs($admin)->put(route('profile.password'), [
            'current_password'      => 'password-salah',
            'password'              => 'password-baru',
            'password_confirmation' => 'password-baru',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertSame($oldHash, $admin->fresh()->password);
    }

    public function test_change_password_requires_new_password()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->put(route('profile.password'), [
            'current_password'      => 'password',
            'password'              => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_change_password_requires_confirmation()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->put(route('profile.password'), [
            'current_password'      => 'password',
            'password'              => 'password-baru',
            'password_confirmation' => 'beda-beda',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_change_password_rejects_short_password()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->put(route('profile.password'), [
            'current_password'      => 'password',
            'password'              => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertSessionHasErrors('password');
    }

    // ==========================================
    // UPDATE PASSWORD — ACTIVITY LOG
    // ==========================================

    public function test_change_password_logs_activity()
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->put(route('profile.password'), [
            'current_password'      => 'password',
            'password'              => 'password-baru',
            'password_confirmation' => 'password-baru',
        ]);

        $log = ActivityLog::where('action', 'profile.password')->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($admin->email, $log->meta['email']);
    }

    public function test_change_password_log_never_contains_password()
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->put(route('profile.password'), [
            'current_password'      => 'password',
            'password'              => 'password-baru',
            'password_confirmation' => 'password-baru',
        ]);

        $log = ActivityLog::where('action', 'profile.password')->first();

        $this->assertNotNull($log);
        $this->assertArrayNotHasKey('password', $log->meta);
        $this->assertArrayNotHasKey('current_password', $log->meta);
        $this->assertArrayNotHasKey('new_password', $log->meta);

        // Konfirmasi: pastikan tidak ada string password di meta manapun
        $metaJson = json_encode($log->meta);
        $this->assertStringNotContainsString('password-baru', $metaJson);
    }

    public function test_wrong_current_password_does_not_log_activity()
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->put(route('profile.password'), [
            'current_password'      => 'password-salah',
            'password'              => 'password-baru',
            'password_confirmation' => 'password-baru',
        ]);

        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'profile.password',
        ]);
    }

    // ==========================================
    // GUEST — NOT AUTHENTICATED
    // ==========================================

    public function test_guest_cannot_update_profile()
    {
        $this->put(route('profile.update'), [
            'name'  => 'Coba',
            'email' => 'coba@pilketos.test',
        ])->assertRedirect(route('login'));
    }

    public function test_guest_cannot_change_password()
    {
        $this->put(route('profile.password'), [
            'current_password'      => 'password',
            'password'              => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertRedirect(route('login'));
    }
}
