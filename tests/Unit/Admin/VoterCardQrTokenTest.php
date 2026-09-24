<?php

namespace Tests\Unit\Admin;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Http\Controllers\Admin\VoterCardController;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class VoterCardQrTokenTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================
    // HELPERS
    // =========================================================

    protected function makeVoterUser(array $overrides = []): User
    {
        $class = ClassRoom::factory()->create();

        $voter = User::factory()->create(array_merge([
            'role'     => UserRole::VOTER,
            'status'   => VoterStatus::VERIFIED,
            'class_id' => $class->id,
            'nis'      => '90999999',
        ], $overrides));

        $voter->assignRole('voter');

        return $voter;
    }

    protected function makeVoterRecord(User $user, array $overrides = []): Voter
    {
        $election = Election::factory()->create();

        return Voter::create(array_merge([
            'election_id' => $election->id,
            'user_id'     => $user->id,
            'class_id'    => $user->class_id,
        ], $overrides));
    }

    protected function resolveQrContent(User $voter): string
    {
        $controller = new VoterCardController();
        $method = new ReflectionMethod($controller, 'resolveQrContent');
        $method->setAccessible(true);

        return $method->invoke($controller, $voter);
    }

    // =========================================================
    // QR TOKEN SOURCE
    // =========================================================

    public function test_uses_qr_token_from_voter_record()
    {
        $voter = $this->makeVoterUser();

        $this->makeVoterRecord($voter, [
            'qr_token' => 'PLK-CUSTOM-TOKEN-XYZ123',
        ]);

        $result = $this->resolveQrContent($voter);

        $this->assertSame('PLK-CUSTOM-TOKEN-XYZ123', $result);
    }

    public function test_does_not_use_qr_token_from_users_table()
    {
        // ✅ Regression test untuk BUG #1
        $voter = $this->makeVoterUser(['nis' => '90111222']);

        $this->makeVoterRecord($voter, [
            'qr_token' => 'PLK-FROM-VOTER-TABLE',
        ]);

        $result = $this->resolveQrContent($voter);

        $this->assertSame('PLK-FROM-VOTER-TABLE', $result);
        $this->assertNotSame('90111222', $result);
    }

    // =========================================================
    // FALLBACK BEHAVIOR
    // =========================================================

    public function test_falls_back_to_nis_when_no_voter_record()
    {
        $voter = $this->makeVoterUser(['nis' => '90888777']);

        $result = $this->resolveQrContent($voter);

        $this->assertSame('90888777', $result);
    }

    public function test_falls_back_to_nis_when_qr_token_empty_string()
    {
        // ✅ Empty string di DB (misal hasil migrasi lama / data corrupt)
        //    → controller pakai ?: → fallback ke NIS
        $voter = $this->makeVoterUser(['nis' => '90444333']);

        $voterRecord = $this->makeVoterRecord($voter);
        $voterRecord->update(['qr_token' => '']);

        $result = $this->resolveQrContent($voter);

        $this->assertSame('90444333', $result);
    }

    public function test_falls_back_to_dash_when_no_qr_token_and_no_nis()
    {
        $voter = $this->makeVoterUser(['nis' => null]);

        $result = $this->resolveQrContent($voter);

        $this->assertSame('-', $result);
    }

    public function test_falls_back_to_dash_when_qr_token_empty_and_nis_empty()
    {
        $voter = $this->makeVoterUser(['nis' => '']);

        $voterRecord = $this->makeVoterRecord($voter);
        $voterRecord->update(['qr_token' => '']);

        $result = $this->resolveQrContent($voter);

        $this->assertSame('-', $result);
    }

    // =========================================================
    // MULTIPLE VOTER RECORDS
    // =========================================================

    public function test_uses_latest_voter_record_when_multiple_exist()
    {
        $voter = $this->makeVoterUser();

        $election1 = Election::factory()->create();
        Voter::create([
            'election_id' => $election1->id,
            'user_id'     => $voter->id,
            'class_id'    => $voter->class_id,
            'qr_token'    => 'PLK-FIRST-TOKEN',
        ]);

        $election2 = Election::factory()->create();
        Voter::create([
            'election_id' => $election2->id,
            'user_id'     => $voter->id,
            'class_id'    => $voter->class_id,
            'qr_token'    => 'PLK-SECOND-TOKEN',
        ]);

        $result = $this->resolveQrContent($voter);

        // ✅ Controller pakai orderByDesc('id') → record terbaru
        $this->assertSame('PLK-SECOND-TOKEN', $result);
    }

    // =========================================================
    // INTEGRATION — FULL HTTP
    // =========================================================

    public function test_download_pdf_succeeds_with_voter_qr_token()
    {
        $voter = $this->makeVoterUser();
        $this->makeVoterRecord($voter, ['qr_token' => 'PLK-INTEGRATION-TEST']);

        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->post(route('admin.voter-cards.download'), [
                'class_id' => $voter->class_id,
            ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_download_pdf_succeeds_without_voter_record()
    {
        $voter = $this->makeVoterUser(['nis' => '90999999']);

        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->post(route('admin.voter-cards.download'), [
                'class_id' => $voter->class_id,
            ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
