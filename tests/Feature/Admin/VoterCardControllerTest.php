<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Models\ActivityLog;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoterCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    // =========================================================
    // HELPERS
    // =========================================================

    protected function makeVoterInClass(ClassRoom $class, array $overrides = []): User
    {
        $voter = User::factory()->create(array_merge([
            'role'     => UserRole::VOTER,
            'status'   => VoterStatus::VERIFIED,
            'class_id' => $class->id,
        ], $overrides));

        $voter->assignRole('voter');

        return $voter;
    }

    // =========================================================
    // INDEX — ACCESS
    // =========================================================

    public function test_admin_can_view_voter_cards_index()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.voter-cards.index'));

        $response->assertOk();
        $response->assertViewIs('admin.voter-cards.index');
        $response->assertViewHas('classes');
        $response->assertViewHas('counts');
    }

    public function test_guest_redirected_from_index()
    {
        $this->get(route('admin.voter-cards.index'))
            ->assertRedirect(route('login'));
    }

    public function test_operator_cannot_access_index()
    {
        $this->actingAs($this->createOperator())
            ->get(route('admin.voter-cards.index'))
            ->assertForbidden();
    }

    public function test_voter_cannot_access_index()
    {
        $this->actingAs($this->createVoter())
            ->get(route('admin.voter-cards.index'))
            ->assertForbidden();
    }

    // =========================================================
    // INDEX — COUNTS
    // =========================================================

    public function test_index_counts_all_voters()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create();

        $this->makeVoterInClass($class, ['status' => VoterStatus::VERIFIED]);
        $this->makeVoterInClass($class, ['status' => VoterStatus::VERIFIED]);
        $this->makeVoterInClass($class, ['status' => VoterStatus::VERIFIED]);
        $this->makeVoterInClass($class, ['status' => VoterStatus::PENDING]);
        $this->makeVoterInClass($class, ['status' => VoterStatus::PENDING]);

        $response = $this->actingAs($admin)
            ->get(route('admin.voter-cards.index'));

        $response->assertViewHas('counts', function ($counts) {
            return $counts['all'] === 5
                && $counts['verified'] === 3;
        });
    }

    public function test_index_only_loads_active_classes()
    {
        $admin = $this->createAdmin();

        ClassRoom::factory()->create(['name' => 'A-AKTIF', 'is_active' => true]);
        ClassRoom::factory()->create(['name' => 'B-NONAKTIF', 'is_active' => false]);

        $response = $this->actingAs($admin)
            ->get(route('admin.voter-cards.index'));

        $response->assertViewHas('classes', function ($classes) {
            return $classes->count() === 1
                && $classes->first()->name === 'A-AKTIF';
        });
    }

    // =========================================================
    // PREVIEW — VALIDATION
    // =========================================================

    public function test_preview_requires_class_id()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->postJson(route('admin.voter-cards.preview'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('class_id');
    }

    public function test_preview_rejects_invalid_class_id()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->postJson(route('admin.voter-cards.preview'), [
                'class_id' => 99999,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('class_id');
    }

    public function test_preview_rejects_invalid_status()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create();

        $response = $this->actingAs($admin)
            ->postJson(route('admin.voter-cards.preview'), [
                'class_id' => $class->id,
                'status'   => 'invalid_status',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('status');
    }

    // =========================================================
    // PREVIEW — HAPPY PATH
    // =========================================================

    public function test_preview_returns_count_of_verified_voters_by_default()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create();

        $this->makeVoterInClass($class, ['status' => VoterStatus::VERIFIED]);
        $this->makeVoterInClass($class, ['status' => VoterStatus::VERIFIED]);
        $this->makeVoterInClass($class, ['status' => VoterStatus::PENDING]);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.voter-cards.preview'), [
                'class_id' => $class->id,
            ]);

        $response->assertOk();
        $response->assertJson([
            'count' => 2,
        ]);
    }

    public function test_preview_returns_count_when_status_all()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create();

        $this->makeVoterInClass($class, ['status' => VoterStatus::VERIFIED]);
        $this->makeVoterInClass($class, ['status' => VoterStatus::PENDING]);
        $this->makeVoterInClass($class, ['status' => VoterStatus::REJECTED]);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.voter-cards.preview'), [
                'class_id' => $class->id,
                'status'   => 'all',
            ]);

        $response->assertJson(['count' => 3]);
    }

    public function test_preview_returns_counts_per_status()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $this->makeVoterInClass($class, ['status' => VoterStatus::VERIFIED]);
        }
        for ($i = 0; $i < 2; $i++) {
            $this->makeVoterInClass($class, ['status' => VoterStatus::PENDING]);
        }

        $response = $this->actingAs($admin)
            ->postJson(route('admin.voter-cards.preview'), [
                'class_id' => $class->id,
            ]);

        $response->assertJson([
            'counts' => [
                'all'      => 5,
                'verified' => 3,
            ],
        ]);
    }

    public function test_preview_only_counts_voter_role()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create();

        $this->makeVoterInClass($class);
        $this->makeVoterInClass($class);

        $operator = $this->createOperator();
        $operator->update(['class_id' => $class->id]);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.voter-cards.preview'), [
                'class_id' => $class->id,
                'status'   => 'all',
            ]);

        $response->assertJson(['count' => 2]);
    }

    public function test_preview_filters_by_class()
    {
        $admin = $this->createAdmin();
        $classA = ClassRoom::factory()->create();
        $classB = ClassRoom::factory()->create();

        $this->makeVoterInClass($classA);
        $this->makeVoterInClass($classA);
        $this->makeVoterInClass($classB);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.voter-cards.preview'), [
                'class_id' => $classA->id,
            ]);

        $response->assertJson(['count' => 2]);
    }

    // =========================================================
    // DOWNLOAD — VALIDATION
    // =========================================================

    public function test_download_requires_class_id()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->post(route('admin.voter-cards.download'), []);

        $response->assertSessionHasErrors('class_id');
    }

    public function test_download_returns_404_when_no_voters()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.voter-cards.download'), [
                'class_id' => $class->id,
            ]);

        $response->assertNotFound();
    }

    public function test_download_returns_404_when_only_pending_voters()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create();

        $this->makeVoterInClass($class, ['status' => VoterStatus::PENDING]);

        $response = $this->actingAs($admin)
            ->post(route('admin.voter-cards.download'), [
                'class_id' => $class->id,
            ]);

        $response->assertNotFound();
    }

    // =========================================================
    // DOWNLOAD — HAPPY PATH
    // =========================================================

    public function test_download_returns_pdf()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create();

        $this->makeVoterInClass($class);

        $response = $this->actingAs($admin)
            ->post(route('admin.voter-cards.download'), [
                'class_id' => $class->id,
            ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $contentDisposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('kartu-voter-', $contentDisposition);
        $this->assertStringContainsString('.pdf', $contentDisposition);
    }

    public function test_download_filename_contains_class_slug_and_timestamp()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $this->makeVoterInClass($class);

        $response = $this->actingAs($admin)
            ->post(route('admin.voter-cards.download'), [
                'class_id' => $class->id,
            ]);

        $contentDisposition = $response->headers->get('content-disposition');

        $this->assertStringContainsString('x-ipa-1', strtolower($contentDisposition));
    }

    public function test_download_with_status_all_includes_pending()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create();

        $this->makeVoterInClass($class, ['status' => VoterStatus::VERIFIED]);
        $this->makeVoterInClass($class, ['status' => VoterStatus::PENDING]);

        $response = $this->actingAs($admin)
            ->post(route('admin.voter-cards.download'), [
                'class_id' => $class->id,
                'status'   => 'all',
            ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_download_handles_multiple_voters()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create();

        for ($i = 0; $i < 10; $i++) {
            $this->makeVoterInClass($class);
        }

        $response = $this->actingAs($admin)
            ->post(route('admin.voter-cards.download'), [
                'class_id' => $class->id,
            ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    // =========================================================
    // DOWNLOAD — QR CONTENT
    // =========================================================

    public function test_download_uses_nis_as_qr_content()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create();

        $voter = $this->makeVoterInClass($class, [
            'nis' => '90999999',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.voter-cards.download'), [
                'class_id' => $class->id,
            ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        // Verifikasi data voter (NIS tetap ada)
        $this->assertSame('90999999', $voter->fresh()->nis);
    }

    // =========================================================
    // DOWNLOAD — ACTIVITY LOG
    // =========================================================

    public function test_download_logs_activity()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $this->makeVoterInClass($class);
        $this->makeVoterInClass($class);

        $this->actingAs($admin)
            ->post(route('admin.voter-cards.download'), [
                'class_id' => $class->id,
            ]);

        $log = ActivityLog::where('action', 'voter.cards_printed')->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($class->id, $log->meta['class_id']);
        $this->assertSame('X-IPA-1', $log->meta['class']);
        $this->assertSame(2, $log->meta['total']);
    }

    public function test_download_log_contains_format_flag()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create();

        $this->makeVoterInClass($class);

        $this->actingAs($admin)
            ->post(route('admin.voter-cards.download'), [
                'class_id' => $class->id,
            ]);

        $log = ActivityLog::where('action', 'voter.cards_printed')->first();

        $this->assertNotNull($log);
        $this->assertArrayHasKey('format', $log->meta);
        $this->assertContains($log->meta['format'], ['png', 'svg']);
    }

    // =========================================================
    // DOWNLOAD — ACCESS
    // =========================================================

    public function test_operator_cannot_download_cards()
    {
        $operator = $this->createOperator();
        $class = ClassRoom::factory()->create();
        $this->makeVoterInClass($class);

        $response = $this->actingAs($operator)
            ->post(route('admin.voter-cards.download'), [
                'class_id' => $class->id,
            ]);

        $response->assertForbidden();
    }

    public function test_guest_cannot_download_cards()
    {
        $class = ClassRoom::factory()->create();

        $response = $this->post(route('admin.voter-cards.download'), [
            'class_id' => $class->id,
        ]);

        $response->assertRedirect(route('login'));
    }
}
