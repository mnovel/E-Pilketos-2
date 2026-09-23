<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Models\ActivityLog;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VoterExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    // ==========================================
    // INDEX
    // ==========================================

    public function test_admin_can_access_export_page()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.voters.export.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.voters.export.index');
        $response->assertViewHas('classes');
        $response->assertViewHas('counts');
    }

    public function test_operator_cannot_access_export_page()
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)
            ->get(route('admin.voters.export.index'));

        $response->assertForbidden();
    }

    public function test_voter_cannot_access_export_page()
    {
        $voter = $this->createVoter();

        $response = $this->actingAs($voter)
            ->get(route('admin.voters.export.index'));

        $response->assertForbidden();
    }

    public function test_guest_redirected_to_login()
    {
        $this->get(route('admin.voters.export.index'))
            ->assertRedirect(route('login'));
    }

    // ==========================================
    // PREVIEW
    // ==========================================

    public function test_preview_returns_count_of_all_voters()
    {
        $admin = $this->createAdmin();

        // Bikin 5 voter
        User::factory()->count(5)->voter()->create(['role' => UserRole::VOTER]);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.voters.export.preview'), [
                'status' => 'all',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'count' => 5,
            'reset' => false,
        ]);
    }

    public function test_preview_filters_by_class()
    {
        $admin = $this->createAdmin();
        $classA = ClassRoom::factory()->create(['name' => 'X-IPA-1']);
        $classB = ClassRoom::factory()->create(['name' => 'X-IPA-2']);

        // 3 voter di kelas A
        User::factory()->count(3)->voter()->create([
            'role'     => UserRole::VOTER,
            'class_id' => $classA->id,
        ]);

        // 2 voter di kelas B
        User::factory()->count(2)->voter()->create([
            'role'     => UserRole::VOTER,
            'class_id' => $classB->id,
        ]);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.voters.export.preview'), [
                'class_id' => $classA->id,
                'status'   => 'all',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['count' => 3]);
    }

    public function test_preview_filters_by_status()
    {
        $admin = $this->createAdmin();

        User::factory()->count(3)->voter()->create([
            'role'   => UserRole::VOTER,
            'status' => VoterStatus::VERIFIED,
        ]);

        User::factory()->count(2)->pending()->create([
            'role' => UserRole::VOTER,
        ]);

        User::factory()->count(1)->rejected()->create([
            'role' => UserRole::VOTER,
        ]);

        // Filter pending
        $response = $this->actingAs($admin)
            ->postJson(route('admin.voters.export.preview'), [
                'status' => 'pending',
            ]);

        $response->assertJson(['count' => 2]);

        // Filter verified
        $response = $this->actingAs($admin)
            ->postJson(route('admin.voters.export.preview'), [
                'status' => 'verified',
            ]);

        $response->assertJson(['count' => 3]);

        // Filter rejected
        $response = $this->actingAs($admin)
            ->postJson(route('admin.voters.export.preview'), [
                'status' => 'rejected',
            ]);

        $response->assertJson(['count' => 1]);
    }

    public function test_preview_returns_reset_flag_when_checked()
    {
        $admin = $this->createAdmin();
        User::factory()->count(2)->voter()->create(['role' => UserRole::VOTER]);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.voters.export.preview'), [
                'status'         => 'all',
                'reset_password' => 1,
            ]);

        $response->assertJson(['reset' => true]);
    }

    // ==========================================
    // EXPORT — TANPA RESET PASSWORD
    // ==========================================

    public function test_export_returns_excel_file()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        User::factory()->count(3)->voter()->create([
            'role'     => UserRole::VOTER,
            'class_id' => $class->id,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.export.download'), [
                'status' => 'all',
            ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_export_does_not_reset_password_by_default()
    {
        $admin = $this->createAdmin();
        $voter = User::factory()->voter()->create([
            'role'     => UserRole::VOTER,
            'password' => Hash::make('original_password'),
        ]);

        $originalHash = $voter->password;

        $this->actingAs($admin)
            ->post(route('admin.voters.export.download'), [
                'status' => 'all',
            ]);

        // Password tidak berubah
        $this->assertEquals($originalHash, $voter->fresh()->password);
    }

    public function test_export_logs_activity()
    {
        $admin = $this->createAdmin();
        User::factory()->count(3)->voter()->create(['role' => UserRole::VOTER]);

        $this->actingAs($admin)
            ->post(route('admin.voters.export.download'), [
                'status' => 'all',
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'action'  => 'voter.credentials_exported',
            'user_id' => $admin->id,
        ]);
    }

    // ==========================================
    // EXPORT — DENGAN RESET PASSWORD
    // ==========================================

    public function test_export_resets_passwords_when_requested()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create();

        $voter1 = User::factory()->voter()->create([
            'role'     => UserRole::VOTER,
            'class_id' => $class->id,
            'password' => Hash::make('old_pass_1'),
        ]);

        $voter2 = User::factory()->voter()->create([
            'role'     => UserRole::VOTER,
            'class_id' => $class->id,
            'password' => Hash::make('old_pass_2'),
        ]);

        $oldHash1 = $voter1->password;
        $oldHash2 = $voter2->password;

        $this->actingAs($admin)
            ->post(route('admin.voters.export.download'), [
                'status'         => 'all',
                'reset_password' => 1,
            ]);

        // Password berubah
        $this->assertNotEquals($oldHash1, $voter1->fresh()->password);
        $this->assertNotEquals($oldHash2, $voter2->fresh()->password);
    }

    public function test_reset_password_logs_activity_with_flag()
    {
        $admin = $this->createAdmin();
        User::factory()->count(3)->voter()->create(['role' => UserRole::VOTER]);

        $this->actingAs($admin)
            ->post(route('admin.voters.export.download'), [
                'status'         => 'all',
                'reset_password' => 1,
            ]);

        $log = ActivityLog::where('action', 'voter.credentials_exported')->first();

        $this->assertNotNull($log);
        $this->assertEquals(3, $log->meta['total']);
        $this->assertTrue($log->meta['password_reset']);
    }

    // ==========================================
    // EXPORT — FILTER
    // ==========================================

    public function test_export_only_includes_filtered_class()
    {
        $admin = $this->createAdmin();
        $classA = ClassRoom::factory()->create();
        $classB = ClassRoom::factory()->create();

        $voterA = User::factory()->voter()->create([
            'role'     => UserRole::VOTER,
            'class_id' => $classA->id,
            'password' => Hash::make('pass_a'),
        ]);

        $voterB = User::factory()->voter()->create([
            'role'     => UserRole::VOTER,
            'class_id' => $classB->id,
            'password' => Hash::make('pass_b'),
        ]);

        $oldHashB = $voterB->password;

        // Export kelas A + reset password
        $this->actingAs($admin)
            ->post(route('admin.voters.export.download'), [
                'class_id'       => $classA->id,
                'status'         => 'all',
                'reset_password' => 1,
            ]);

        // Voter A password berubah
        $this->assertNotEquals(Hash::make('pass_a'), $voterA->fresh()->password);

        // Voter B password TIDAK berubah (tidak di-include)
        $this->assertEquals($oldHashB, $voterB->fresh()->password);
    }

    public function test_export_only_includes_filtered_status()
    {
        $admin = $this->createAdmin();

        $verified = User::factory()->voter()->create([
            'role'     => UserRole::VOTER,
            'status'   => VoterStatus::VERIFIED,
            'password' => Hash::make('verified_pass'),
        ]);

        $pending = User::factory()->pending()->create([
            'role'     => UserRole::VOTER,
            'password' => Hash::make('pending_pass'),
        ]);

        $oldVerifiedHash = $verified->password;
        $oldPendingHash = $pending->password;

        // Export hanya status pending
        $this->actingAs($admin)
            ->post(route('admin.voters.export.download'), [
                'status'         => 'pending',
                'reset_password' => 1,
            ]);

        // Verified password TIDAK berubah
        $this->assertEquals($oldVerifiedHash, $verified->fresh()->password);

        // Pending password berubah
        $this->assertNotEquals($oldPendingHash, $pending->fresh()->password);
    }

    // ==========================================
    // EXPORT — EMPTY RESULT
    // ==========================================

    public function test_export_returns_404_when_no_voters_match()
    {
        $admin = $this->createAdmin();

        // Tidak ada voter
        $response = $this->actingAs($admin)
            ->post(route('admin.voters.export.download'), [
                'status' => 'all',
            ]);

        $response->assertStatus(404);
    }

    public function test_export_returns_404_when_filter_matches_nothing()
    {
        $admin = $this->createAdmin();

        // Bikin voter verified
        User::factory()->count(3)->voter()->create([
            'role'   => UserRole::VOTER,
            'status' => VoterStatus::VERIFIED,
        ]);

        // Filter pending → kosong
        $response = $this->actingAs($admin)
            ->post(route('admin.voters.export.download'), [
                'status' => 'pending',
            ]);

        $response->assertStatus(404);
    }

    // ==========================================
    // EXPORT — VALIDATION
    // ==========================================

    public function test_export_validates_class_id_exists()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.export.download'), [
                'class_id' => 99999,
                'status'   => 'all',
            ]);

        $response->assertSessionHasErrors('class_id');
    }

    public function test_export_validates_status_enum()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.export.download'), [
                'status' => 'invalid_status',
            ]);

        $response->assertSessionHasErrors('status');
    }

    // ==========================================
    // EXPORT — EXCLUDE NON-VOTER
    // ==========================================

    public function test_export_only_includes_voter_role()
    {
        $admin = $this->createAdmin();

        // Bikin voter
        User::factory()->count(2)->voter()->create(['role' => UserRole::VOTER]);

        // Bikin operator (tidak boleh ke-export)
        $this->createOperator();

        $response = $this->actingAs($admin)
            ->postJson(route('admin.voters.export.preview'), [
                'status' => 'all',
            ]);

        // Hanya 2 voter
        $response->assertJson(['count' => 2]);
    }

    // ==========================================
    // EXPORT — FILE NAME
    // ==========================================

    public function test_export_filename_contains_timestamp()
    {
        $admin = $this->createAdmin();
        User::factory()->count(1)->voter()->create(['role' => UserRole::VOTER]);

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.export.download'), [
                'status' => 'all',
            ]);

        $contentDisposition = $response->headers->get('content-disposition');

        $this->assertStringContainsString('credentials-voter-', $contentDisposition);
        $this->assertStringContainsString('.xlsx', $contentDisposition);
    }
}
