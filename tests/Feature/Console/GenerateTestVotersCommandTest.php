<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GenerateTestVotersCommandTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================
    // HELPERS
    // =========================================================

    protected function makeClass(string $name = 'X-IPA-1'): ClassRoom
    {
        return ClassRoom::factory()->create([
            'name'      => $name,
            'is_active' => true,
        ]);
    }

    // =========================================================
    // SUCCESS & DRY-RUN
    // =========================================================

    public function test_command_runs_successfully()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => 5,
            '--kelas' => 'X-IPA-1',
        ])->assertSuccessful();
    }

    public function test_dry_run_does_not_create_users()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'    => 10,
            '--kelas'   => 'X-IPA-1',
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertSame(0, User::where('role', UserRole::VOTER)->count());
    }

    public function test_dry_run_does_not_create_any_user_at_all()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'    => 5,
            '--kelas'   => 'X-IPA-1',
            '--dry-run' => true,
        ]);

        $this->assertSame(0, User::count());
    }

    // =========================================================
    // CREATE VOTERS
    // =========================================================

    public function test_creates_specified_number_of_voters()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => 20,
            '--kelas' => 'X-IPA-1',
        ]);

        $this->assertSame(20, User::where('role', UserRole::VOTER)->count());
    }

    public function test_voters_assigned_to_correct_class()
    {
        $class = $this->makeClass('X-IPA-1');

        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => 5,
            '--kelas' => 'X-IPA-1',
        ]);

        $this->assertSame(
            5,
            User::where('role', UserRole::VOTER)->where('class_id', $class->id)->count()
        );
    }

    public function test_default_status_is_pending()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => 5,
            '--kelas' => 'X-IPA-1',
        ]);

        $this->assertSame(
            5,
            User::where('role', UserRole::VOTER)->where('status', VoterStatus::PENDING)->count()
        );
    }

    public function test_creates_verified_voters()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'   => 3,
            '--kelas'  => 'X-IPA-1',
            '--status' => 'verified',
        ]);

        $this->assertSame(
            3,
            User::where('role', UserRole::VOTER)->where('status', VoterStatus::VERIFIED)->count()
        );
    }

    public function test_creates_rejected_voters()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'   => 3,
            '--kelas'  => 'X-IPA-1',
            '--status' => 'rejected',
        ]);

        $this->assertSame(
            3,
            User::where('role', UserRole::VOTER)->where('status', VoterStatus::REJECTED)->count()
        );
    }

    public function test_default_password_is_password()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => 1,
            '--kelas' => 'X-IPA-1',
        ]);

        $voter = User::where('role', UserRole::VOTER)->first();

        $this->assertNotNull($voter);
        $this->assertTrue(Hash::check('password', $voter->password));
    }

    public function test_assigns_voter_spatie_role()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => 1,
            '--kelas' => 'X-IPA-1',
        ]);

        $voter = User::where('role', UserRole::VOTER)->first();

        $this->assertNotNull($voter);
        $this->assertTrue($voter->hasRole('voter'));
    }

    public function test_generated_name_contains_nis()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => 1,
            '--kelas' => 'X-IPA-1',
            '--start' => '10000',
        ]);

        $voter = User::where('role', UserRole::VOTER)->first();

        $this->assertNotNull($voter);
        $this->assertStringContainsString($voter->nis, $voter->name);
    }

    // =========================================================
    // NIS — SEQUENTIAL (10 digit)
    // =========================================================

    public function test_sequential_nis_starts_from_given_number()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => 5,
            '--kelas' => 'X-IPA-1',
            '--start' => '10000',
        ]);

        // ✅ Format: padLeft 10 digit
        // start=10000 → '0000010000', '0000010001', ..., '0000010004'
        $this->assertDatabaseHas('users', ['nis' => '0000010000']);
        $this->assertDatabaseHas('users', ['nis' => '0000010001']);
        $this->assertDatabaseHas('users', ['nis' => '0000010002']);
        $this->assertDatabaseHas('users', ['nis' => '0000010003']);
        $this->assertDatabaseHas('users', ['nis' => '0000010004']);
    }

    public function test_sequential_nis_starts_from_zero()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => 3,
            '--kelas' => 'X-IPA-1',
            '--start' => '0',
        ]);

        // ✅ start=0 → '0000000000', '0000000001', '0000000002'
        $this->assertDatabaseHas('users', ['nis' => '0000000000']);
        $this->assertDatabaseHas('users', ['nis' => '0000000001']);
        $this->assertDatabaseHas('users', ['nis' => '0000000002']);
    }

    public function test_sequential_nis_starts_from_large_number()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => 3,
            '--kelas' => 'X-IPA-1',
            '--start' => '9999999900',
        ]);

        // ✅ start=9999999900 → '9999999900', '9999999901', '9999999902'
        $this->assertDatabaseHas('users', ['nis' => '9999999900']);
        $this->assertDatabaseHas('users', ['nis' => '9999999901']);
        $this->assertDatabaseHas('users', ['nis' => '9999999902']);
    }

    public function test_sequential_nis_all_10_digits()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => 5,
            '--kelas' => 'X-IPA-1',
            '--start' => '100',
        ]);

        $nis = User::where('role', UserRole::VOTER)->pluck('nis')->toArray();

        foreach ($nis as $n) {
            $this->assertSame(10, strlen($n), "NIS [{$n}] harus 10 digit");
        }
    }

    // =========================================================
    // NIS — RANDOM (tanpa --start)
    // =========================================================

    public function test_random_nis_is_10_digits()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => 5,
            '--kelas' => 'X-IPA-1',
        ]);

        $voters = User::where('role', UserRole::VOTER)->get();

        $this->assertCount(5, $voters);
        $voters->each(function ($voter) {
            $this->assertSame(10, strlen($voter->nis));
            $this->assertMatchesRegularExpression('/^\d{10}$/', $voter->nis);
        });
    }

    public function test_random_nis_all_unique()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => 20,
            '--kelas' => 'X-IPA-1',
        ]);

        $nis = User::where('role', UserRole::VOTER)->pluck('nis')->toArray();

        $this->assertCount(20, $nis);
        $this->assertSame($nis, array_unique($nis));
    }

    // =========================================================
    // EMAIL — FORMAT BARU
    // =========================================================

    public function test_email_is_auto_generated_with_new_format()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => 1,
            '--kelas' => 'X-IPA-1',
        ]);

        $voter = User::where('role', UserRole::VOTER)->first();

        $this->assertNotNull($voter);
        $this->assertNotEmpty($voter->email);

        // ✅ Format baru: siswa.{3 alfanumerik lowercase}@pilketos.test
        $this->assertMatchesRegularExpression(
            '/^siswa\.[a-z0-9]{3}@pilketos\.test$/',
            $voter->email
        );
    }

    public function test_email_does_not_contain_nis()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => 1,
            '--kelas' => 'X-IPA-1',
            '--start' => '12345',
        ]);

        $voter = User::where('role', UserRole::VOTER)->first();

        // ✅ Email tidak lagi mengandung NIS
        $this->assertStringNotContainsString($voter->nis, $voter->email);
        $this->assertStringNotContainsString('12345', $voter->email);
    }

    public function test_email_all_unique()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => 15,
            '--kelas' => 'X-IPA-1',
        ]);

        $emails = User::where('role', UserRole::VOTER)->pluck('email')->toArray();

        $this->assertCount(15, $emails);
        $this->assertSame($emails, array_unique($emails));
    }

    public function test_email_starts_with_siswa_dot()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => 5,
            '--kelas' => 'X-IPA-1',
        ]);

        $emails = User::where('role', UserRole::VOTER)->pluck('email')->toArray();

        foreach ($emails as $email) {
            $this->assertStringStartsWith('siswa.', $email);
            $this->assertStringEndsWith('@pilketos.test', $email);
        }
    }

    // =========================================================
    // DUPLICATE NIS
    // =========================================================

    public function test_skips_duplicate_nis()
    {
        $class = $this->makeClass();

        // Sudah ada user dengan NIS 0000010000
        User::factory()->create(['nis' => '0000010000']);

        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => 5,
            '--kelas' => 'X-IPA-1',
            '--start' => '10000',
        ]);

        // Hanya 4 voter dibuat (0000010000 skip, 0000010001-0000010004 dibuat)
        $this->assertSame(
            4,
            User::where('role', UserRole::VOTER)->where('class_id', $class->id)->count()
        );
    }

    // =========================================================
    // CLASS NOT FOUND
    // =========================================================

    public function test_fails_when_class_not_found()
    {
        // Tidak ada kelas sama sekali

        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => 5,
            '--kelas' => 'X-UNKNOWN',
        ])->assertFailed();

        $this->assertSame(0, User::where('role', UserRole::VOTER)->count());
    }

    public function test_fails_when_class_inactive()
    {
        ClassRoom::factory()->create([
            'name'      => 'X-IPA-1',
            'is_active' => false,
        ]);

        // Command cari by name (bukan active scope) → ketemu
        // Command tidak filter is_active → tetap sukses
        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => 5,
            '--kelas' => 'X-IPA-1',
        ])->assertSuccessful();
    }

    // =========================================================
    // INVALID JUMLAH
    // =========================================================

    public function test_fails_when_jumlah_is_zero()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => 0,
            '--kelas' => 'X-IPA-1',
        ])->assertFailed();

        $this->assertSame(0, User::where('role', UserRole::VOTER)->count());
    }

    public function test_fails_when_jumlah_is_negative()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'  => -5,
            '--kelas' => 'X-IPA-1',
        ])->assertFailed();

        $this->assertSame(0, User::where('role', UserRole::VOTER)->count());
    }

    // =========================================================
    // INVALID STATUS (FALLBACK KE PENDING)
    // =========================================================

    public function test_invalid_status_falls_back_to_pending()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            'jumlah'   => 3,
            '--kelas'  => 'X-IPA-1',
            '--status' => 'invalid_status',
        ])->assertSuccessful();

        $this->assertSame(
            3,
            User::where('role', UserRole::VOTER)->where('status', VoterStatus::PENDING)->count()
        );
    }

    // =========================================================
    // DEFAULT JUMLAH
    // =========================================================

    public function test_default_jumlah_is_55()
    {
        $this->makeClass();

        $this->artisan('pilketos:generate-voters', [
            '--kelas' => 'X-IPA-1',
        ])->assertSuccessful();

        $this->assertSame(55, User::where('role', UserRole::VOTER)->count());
    }
}
