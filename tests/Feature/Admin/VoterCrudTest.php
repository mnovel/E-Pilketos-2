<?php

namespace Tests\Feature\Admin;

use App\Enums\ElectionStatus;
use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Models\ActivityLog;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\ElectionSession;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VoterCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
        Storage::fake('public');
    }

    // =========================================================
    // HELPERS LOKAL (prefix make* biar tidak bentrok parent)
    // =========================================================

    protected function makeClass(array $overrides = []): ClassRoom
    {
        static $counter = 0;
        $counter++;

        return ClassRoom::create(array_merge([
            'name'      => 'KELAS-' . $counter,
            'tingkat'   => 'X',
            'jurusan'   => 'IPA',
            'rombel'    => (string) $counter,
            'is_active' => true,
        ], $overrides));
    }

    protected function makeVoter(array $overrides = []): User
    {
        static $counter = 0;
        $counter++;

        if (!isset($overrides['class_id'])) {
            $overrides['class_id'] = $this->makeClass()->id;
        }

        if (!isset($overrides['nis'])) {
            $overrides['nis'] = '100000' . str_pad((string) $counter, 3, '0', STR_PAD_LEFT);
        }

        if (!isset($overrides['email'])) {
            $overrides['email'] = "voter-{$counter}@pilketos.test";
        }

        $voter = User::factory()->create(array_merge([
            'name'     => 'Voter Lama',
            'password' => Hash::make('password'),
            'role'     => UserRole::VOTER,
            'status'   => VoterStatus::PENDING,
        ], $overrides));

        $voter->assignRole('voter');

        return $voter;
    }

    protected function makeCreatePayload(array $overrides = []): array
    {
        if (!isset($overrides['class_id'])) {
            $overrides['class_id'] = $this->makeClass()->id;
        }

        return array_merge([
            'nis'      => '12345678',
            'name'     => 'Ahmad Fauzi',
            'email'    => 'ahmad@pilketos.test',
            'password' => 'rahasia123',
        ], $overrides);
    }

    protected function makeElection(array $overrides = []): Election
    {
        static $counter = 0;
        $counter++;

        return Election::create(array_merge([
            'title'        => 'Pilketos ' . $counter,
            'tahun_ajaran' => '2024/2025',
            'start_at'     => now()->addDay(),
            'end_at'       => now()->addDays(7),
            'status'       => ElectionStatus::DRAFT,
            'created_by'   => $this->createAdmin()->id,
        ], $overrides));
    }

    protected function makeSession(Election $election, ClassRoom $class, array $overrides = []): ElectionSession
    {
        return ElectionSession::create(array_merge([
            'election_id'   => $election->id,
            'class_id'      => $class->id,
            'tanggal'       => now()->addDay()->toDateString(),
            'waktu_mulai'   => '08:00',
            'waktu_selesai' => '12:00',
            'status'        => SessionStatus::SCHEDULED,
        ], $overrides));
    }

    protected function makeVoterRecord(User $voter, Election $election, array $overrides = []): Voter
    {
        return Voter::create(array_merge([
            'election_id' => $election->id,
            'user_id'     => $voter->id,
            'class_id'    => $voter->class_id,
        ], $overrides));
    }

    // =========================================================
    // CREATE — ACCESS
    // =========================================================

    public function test_admin_can_view_create_form()
    {
        $this->makeClass();

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.voters.create'));

        $response->assertOk();
        $response->assertViewIs('admin.voters.create');
        $response->assertViewHas('classes');
    }

    public function test_guest_cannot_view_create_form()
    {
        $response = $this->get(route('admin.voters.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_view_create_form()
    {
        $response = $this->actingAs($this->createOperator())
            ->get(route('admin.voters.create'));

        $response->assertForbidden();
    }

    // =========================================================
    // CREATE — STORE (HAPPY PATH)
    // =========================================================

    public function test_admin_can_create_voter()
    {
        $admin   = $this->createAdmin();
        $payload = $this->makeCreatePayload();

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.store'), $payload);

        $response->assertRedirect(route('admin.voters.index', ['status' => 'pending']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'nis'      => '12345678',
            'name'     => 'Ahmad Fauzi',
            'email'    => 'ahmad@pilketos.test',
            'role'     => UserRole::VOTER->value,
            'status'   => VoterStatus::PENDING->value,
        ]);
    }

    public function test_create_hashes_password()
    {
        $this->actingAs($this->createAdmin())
            ->post(route('admin.voters.store'), $this->makeCreatePayload([
                'password' => 'rahasia123',
            ]));

        $voter = User::where('nis', '12345678')->first();

        $this->assertNotNull($voter);
        $this->assertNotSame('rahasia123', $voter->password);
        $this->assertTrue(Hash::check('rahasia123', $voter->password));
    }

    public function test_create_uses_default_password_when_empty()
    {
        $this->actingAs($this->createAdmin())
            ->post(route('admin.voters.store'), $this->makeCreatePayload([
                'password' => null,
            ]));

        $voter = User::where('nis', '12345678')->first();

        $this->assertNotNull($voter);
        $this->assertTrue(Hash::check('password', $voter->password));
    }

    public function test_create_auto_generates_email_when_empty()
    {
        $this->actingAs($this->createAdmin())
            ->post(route('admin.voters.store'), $this->makeCreatePayload([
                'email' => null,
            ]));

        $voter = User::where('nis', '12345678')->first();

        $this->assertNotNull($voter);
        $this->assertNotEmpty($voter->email);

        $this->assertMatchesRegularExpression(
            '/^siswa\.[a-z0-9]{3}@pilketos\.test$/',
            $voter->email
        );
    }

    public function test_create_assigns_voter_spatie_role()
    {
        $this->actingAs($this->createAdmin())
            ->post(route('admin.voters.store'), $this->makeCreatePayload());

        $voter = User::where('nis', '12345678')->first();

        $this->assertNotNull($voter);
        $this->assertTrue($voter->hasRole('voter'));
    }

    public function test_create_uploads_kartu_pelajar()
    {
        $file = UploadedFile::fake()->image('kartu.jpg', 800, 600);

        $this->actingAs($this->createAdmin())
            ->post(route('admin.voters.store'), $this->makeCreatePayload([
                'kartu_pelajar' => $file,
            ]));

        $voter = User::where('nis', '12345678')->first();

        $this->assertNotNull($voter);
        $this->assertNotNull($voter->kartu_pelajar);
        Storage::disk('public')->assertExists($voter->kartu_pelajar);
    }

    public function test_create_logs_activity()
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post(route('admin.voters.store'), $this->makeCreatePayload());

        $this->assertDatabaseHas('activity_logs', [
            'action'  => 'voter.created',
            'user_id' => $admin->id,
        ]);
    }

    // =========================================================
    // CREATE — VALIDATION
    // =========================================================

    public function test_create_requires_nis()
    {
        $this->actingAs($this->createAdmin())
            ->post(route('admin.voters.store'), $this->makeCreatePayload(['nis' => '']))
            ->assertSessionHasErrors('nis');
    }

    public function test_create_requires_name()
    {
        $this->actingAs($this->createAdmin())
            ->post(route('admin.voters.store'), $this->makeCreatePayload(['name' => '']))
            ->assertSessionHasErrors('name');
    }

    public function test_create_requires_class_id()
    {
        $this->actingAs($this->createAdmin())
            ->post(route('admin.voters.store'), $this->makeCreatePayload(['class_id' => '']))
            ->assertSessionHasErrors('class_id');
    }

    public function test_create_rejects_invalid_class_id()
    {
        $this->actingAs($this->createAdmin())
            ->post(route('admin.voters.store'), $this->makeCreatePayload(['class_id' => 99999]))
            ->assertSessionHasErrors('class_id');
    }

    public function test_create_rejects_duplicate_nis()
    {
        $this->makeVoter(['nis' => '12345678']);

        $this->actingAs($this->createAdmin())
            ->post(route('admin.voters.store'), $this->makeCreatePayload(['nis' => '12345678']))
            ->assertSessionHasErrors('nis');
    }

    public function test_create_rejects_duplicate_email()
    {
        $this->makeVoter(['email' => 'ahmad@pilketos.test']);

        $this->actingAs($this->createAdmin())
            ->post(route('admin.voters.store'), $this->makeCreatePayload([
                'email' => 'ahmad@pilketos.test',
            ]))
            ->assertSessionHasErrors('email');
    }

    public function test_create_rejects_invalid_email_format()
    {
        $this->actingAs($this->createAdmin())
            ->post(route('admin.voters.store'), $this->makeCreatePayload([
                'email' => 'bukan-email',
            ]))
            ->assertSessionHasErrors('email');
    }

    public function test_create_rejects_short_password()
    {
        $this->actingAs($this->createAdmin())
            ->post(route('admin.voters.store'), $this->makeCreatePayload([
                'password' => '123',
            ]))
            ->assertSessionHasErrors('password');
    }

    public function test_create_rejects_non_image_kartu()
    {
        $file = UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf');

        $this->actingAs($this->createAdmin())
            ->post(route('admin.voters.store'), $this->makeCreatePayload([
                'kartu_pelajar' => $file,
            ]))
            ->assertSessionHasErrors('kartu_pelajar');
    }

    public function test_non_admin_cannot_create_voter()
    {
        $this->actingAs($this->createOperator())
            ->post(route('admin.voters.store'), $this->makeCreatePayload())
            ->assertForbidden();
    }

    // =========================================================
    // UPDATE — ACCESS
    // =========================================================

    public function test_admin_can_view_edit_form()
    {
        $voter = $this->makeVoter();

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.voters.edit', $voter));

        $response->assertOk();
        $response->assertViewIs('admin.voters.edit');
        $response->assertViewHas('voter');
        $response->assertViewHas('classes');
    }

    public function test_guest_cannot_view_edit_form()
    {
        $voter = $this->makeVoter();

        $this->get(route('admin.voters.edit', $voter))
            ->assertRedirect(route('login'));
    }

    public function test_edit_returns_404_for_non_voter_user()
    {
        $operator = $this->createOperator();

        $this->actingAs($this->createAdmin())
            ->get(route('admin.voters.edit', $operator))
            ->assertNotFound();
    }

    // =========================================================
    // UPDATE — HAPPY PATH
    // =========================================================

    public function test_admin_can_update_voter()
    {
        $class = $this->makeClass();

        $voter = $this->makeVoter([
            'nis'      => '10000001',
            'name'     => 'Nama Lama',
            'class_id' => $class->id,
            'email'    => 'lama@pilketos.test',
        ]);

        $response = $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'      => '10000002',
                'name'     => 'Nama Baru',
                'class_id' => $class->id,
                'email'    => 'baru@pilketos.test',
            ]);

        $response->assertRedirect(route('admin.voters.show', $voter));
        $response->assertSessionHas('success');

        $voter->refresh();
        $this->assertSame('10000002', $voter->nis);
        $this->assertSame('Nama Baru', $voter->name);
        $this->assertSame('baru@pilketos.test', $voter->email);
    }

    public function test_update_allows_same_nis_for_same_voter()
    {
        $voter = $this->makeVoter(['nis' => '10000001']);

        $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'      => '10000001',
                'name'     => 'Nama Baru',
                'class_id' => $voter->class_id,
                'email'    => $voter->email,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Nama Baru', $voter->fresh()->name);
    }

    public function test_update_allows_same_email_for_same_voter()
    {
        $voter = $this->makeVoter(['email' => 'sama@pilketos.test']);

        $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'      => $voter->nis,
                'name'     => 'Nama Baru',
                'class_id' => $voter->class_id,
                'email'    => 'sama@pilketos.test',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_update_replaces_kartu_pelajar()
    {
        $oldPath = UploadedFile::fake()->image('old.jpg')
            ->store('kartu-pelajar', 'public');

        $voter = $this->makeVoter(['kartu_pelajar' => $oldPath]);

        Storage::disk('public')->assertExists($oldPath);

        $newFile = UploadedFile::fake()->image('new.jpg');

        $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'           => $voter->nis,
                'name'          => $voter->name,
                'class_id'      => $voter->class_id,
                'email'         => $voter->email,
                'kartu_pelajar' => $newFile,
            ])
            ->assertSessionHasNoErrors();

        $voter->refresh();

        Storage::disk('public')->assertMissing($oldPath);
        $this->assertNotSame($oldPath, $voter->kartu_pelajar);
        Storage::disk('public')->assertExists($voter->kartu_pelajar);
    }

    public function test_update_can_delete_kartu_without_upload()
    {
        $path = UploadedFile::fake()->image('kartu.jpg')
            ->store('kartu-pelajar', 'public');

        $voter = $this->makeVoter(['kartu_pelajar' => $path]);

        $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'         => $voter->nis,
                'name'        => $voter->name,
                'class_id'    => $voter->class_id,
                'email'       => $voter->email,
                'hapus_kartu' => 1,
            ])
            ->assertSessionHasNoErrors();

        $voter->refresh();

        $this->assertNull($voter->kartu_pelajar);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_update_does_not_change_password()
    {
        $originalPassword = Hash::make('password-lama');
        $voter = $this->makeVoter(['password' => $originalPassword]);

        $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'      => $voter->nis,
                'name'     => 'Nama Baru',
                'class_id' => $voter->class_id,
                'email'    => $voter->email,
            ]);

        $voter->refresh();

        $this->assertSame($originalPassword, $voter->password);
        $this->assertTrue(Hash::check('password-lama', $voter->password));
    }

    public function test_update_does_not_change_status()
    {
        $voter = $this->makeVoter(['status' => VoterStatus::REJECTED]);

        $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'      => $voter->nis,
                'name'     => 'Nama Baru',
                'class_id' => $voter->class_id,
                'email'    => $voter->email,
            ]);

        $this->assertSame(VoterStatus::REJECTED, $voter->fresh()->status);
    }

    public function test_update_logs_activity_when_changed()
    {
        $admin = $this->createAdmin();
        $voter = $this->makeVoter(['name' => 'Nama Lama']);

        $this->actingAs($admin)
            ->put(route('admin.voters.update', $voter), [
                'nis'      => $voter->nis,
                'name'     => 'Nama Baru',
                'class_id' => $voter->class_id,
                'email'    => $voter->email,
            ]);

        $log = ActivityLog::where('action', 'voter.updated')->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertArrayHasKey('name', $log->meta['changes']);
        $this->assertSame('Nama Lama', $log->meta['changes']['name']['from']);
        $this->assertSame('Nama Baru', $log->meta['changes']['name']['to']);
    }

    public function test_update_does_not_log_when_no_changes()
    {
        $voter = $this->makeVoter();

        $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'      => $voter->nis,
                'name'     => $voter->name,
                'class_id' => $voter->class_id,
                'email'    => $voter->email,
            ]);

        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'voter.updated',
        ]);
    }

    // =========================================================
    // UPDATE — VALIDATION
    // =========================================================

    public function test_update_rejects_duplicate_nis_of_other_voter()
    {
        $class = $this->makeClass();

        $this->makeVoter([
            'nis'      => '11111111',
            'class_id' => $class->id,
        ]);

        $voter = $this->makeVoter([
            'nis'      => '22222222',
            'class_id' => $class->id,
        ]);

        $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'      => '11111111',
                'name'     => $voter->name,
                'class_id' => $voter->class_id,
                'email'    => $voter->email,
            ])
            ->assertSessionHasErrors('nis');
    }

    public function test_update_rejects_duplicate_email_of_other_user()
    {
        $class = $this->makeClass();

        $this->makeVoter([
            'nis'      => '11111111',
            'email'    => 'lain@pilketos.test',
            'class_id' => $class->id,
        ]);

        $voter = $this->makeVoter([
            'nis'      => '22222222',
            'email'    => 'punya@pilketos.test',
            'class_id' => $class->id,
        ]);

        $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'      => $voter->nis,
                'name'     => $voter->name,
                'class_id' => $voter->class_id,
                'email'    => 'lain@pilketos.test',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_update_requires_email()
    {
        $voter = $this->makeVoter();

        $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'      => $voter->nis,
                'name'     => $voter->name,
                'class_id' => $voter->class_id,
                'email'    => '',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_update_rejects_invalid_class()
    {
        $voter = $this->makeVoter();

        $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'      => $voter->nis,
                'name'     => $voter->name,
                'class_id' => 99999,
                'email'    => $voter->email,
            ])
            ->assertSessionHasErrors('class_id');
    }

    public function test_update_returns_404_for_non_voter_user()
    {
        $operator = $this->createOperator();

        $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $operator), [
                'nis'      => '99999999',
                'name'     => 'Coba Edit',
                'class_id' => $this->makeClass()->id,
                'email'    => 'coba@pilketos.test',
            ])
            ->assertNotFound();
    }

    public function test_non_admin_cannot_update_voter()
    {
        $voter = $this->makeVoter();

        $this->actingAs($this->createOperator())
            ->put(route('admin.voters.update', $voter), [
                'nis'      => $voter->nis,
                'name'     => 'Coba Update',
                'class_id' => $voter->class_id,
                'email'    => $voter->email,
            ])
            ->assertForbidden();
    }

    // =========================================================
    // LOCK — EDIT BLOCKED
    // =========================================================

    public function test_edit_redirects_when_election_is_active()
    {
        $class    = $this->makeClass();
        $voter    = $this->makeVoter(['class_id' => $class->id]);
        $election = $this->makeElection([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHour(),
            'end_at'   => now()->addHours(5),
        ]);

        $this->makeVoterRecord($voter, $election, ['class_id' => $class->id]);

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.voters.edit', $voter));

        $response->assertRedirect(route('admin.voters.show', $voter));
        $response->assertSessionHas('warning');
    }

    public function test_edit_redirects_when_session_is_active()
    {
        $class    = $this->makeClass();
        $voter    = $this->makeVoter(['class_id' => $class->id]);
        $election = $this->makeElection();
        $session  = $this->makeSession($election, $class, [
            'status'    => SessionStatus::ACTIVE,
            'tanggal'   => now()->toDateString(),
            'waktu_mulai' => now()->subHour()->format('H:i'),
            'waktu_selesai' => now()->addHours(2)->format('H:i'),
        ]);

        $this->makeVoterRecord($voter, $election, [
            'class_id'   => $class->id,
            'session_id' => $session->id,
        ]);

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.voters.edit', $voter));

        $response->assertRedirect(route('admin.voters.show', $voter));
        $response->assertSessionHas('warning');
    }

    public function test_edit_redirects_when_voter_has_checked_in()
    {
        $class    = $this->makeClass();
        $voter    = $this->makeVoter(['class_id' => $class->id]);
        $election = $this->makeElection();

        $this->makeVoterRecord($voter, $election, [
            'class_id'   => $class->id,
            'checked_in' => true,
            'checked_in_at' => now(),
        ]);

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.voters.edit', $voter));

        $response->assertRedirect(route('admin.voters.show', $voter));
        $response->assertSessionHas('warning');
    }

    public function test_edit_redirects_when_voter_has_voted()
    {
        $class    = $this->makeClass();
        $voter    = $this->makeVoter(['class_id' => $class->id]);
        $election = $this->makeElection();

        $this->makeVoterRecord($voter, $election, [
            'class_id'  => $class->id,
            'has_voted' => true,
            'voted_at'  => now(),
        ]);

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.voters.edit', $voter));

        $response->assertRedirect(route('admin.voters.show', $voter));
        $response->assertSessionHas('warning');
    }

    public function test_edit_allowed_when_election_draft_and_no_session_active()
    {
        $class    = $this->makeClass();
        $voter    = $this->makeVoter(['class_id' => $class->id]);
        $election = $this->makeElection(); // DRAFT

        $session = $this->makeSession($election, $class); // SCHEDULED

        $this->makeVoterRecord($voter, $election, [
            'class_id'   => $class->id,
            'session_id' => $session->id,
        ]);

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.voters.edit', $voter));

        $response->assertOk();
    }

    // =========================================================
    // LOCK — UPDATE BLOCKED
    // =========================================================

    public function test_update_redirects_when_election_is_active()
    {
        $class    = $this->makeClass();
        $newClass = $this->makeClass();
        $voter    = $this->makeVoter(['class_id' => $class->id]);

        $election = $this->makeElection([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHour(),
            'end_at'   => now()->addHours(5),
        ]);

        $this->makeVoterRecord($voter, $election, ['class_id' => $class->id]);

        $response = $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'      => $voter->nis,
                'name'     => $voter->name,
                'class_id' => $newClass->id,
                'email'    => $voter->email,
            ]);

        $response->assertRedirect(route('admin.voters.show', $voter));
        $response->assertSessionHas('warning');

        // Kelas tidak berubah
        $this->assertSame($class->id, $voter->fresh()->class_id);
    }

    public function test_update_redirects_when_session_is_active()
    {
        $class    = $this->makeClass();
        $newClass = $this->makeClass();
        $voter    = $this->makeVoter(['class_id' => $class->id]);
        $election = $this->makeElection();

        $session = $this->makeSession($election, $class, [
            'status'        => SessionStatus::ACTIVE,
            'tanggal'       => now()->toDateString(),
            'waktu_mulai'   => now()->subHour()->format('H:i'),
            'waktu_selesai' => now()->addHours(2)->format('H:i'),
        ]);

        $this->makeVoterRecord($voter, $election, [
            'class_id'   => $class->id,
            'session_id' => $session->id,
        ]);

        $response = $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'      => $voter->nis,
                'name'     => $voter->name,
                'class_id' => $newClass->id,
                'email'    => $voter->email,
            ]);

        $response->assertRedirect(route('admin.voters.show', $voter));
        $response->assertSessionHas('warning');
    }

    public function test_update_redirects_when_voter_already_voted()
    {
        $class    = $this->makeClass();
        $newClass = $this->makeClass();
        $voter    = $this->makeVoter(['class_id' => $class->id]);
        $election = $this->makeElection();

        $this->makeVoterRecord($voter, $election, [
            'class_id'  => $class->id,
            'has_voted' => true,
            'voted_at'  => now(),
        ]);

        $response = $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'      => $voter->nis,
                'name'     => $voter->name,
                'class_id' => $newClass->id,
                'email'    => $voter->email,
            ]);

        $response->assertRedirect(route('admin.voters.show', $voter));
        $response->assertSessionHas('warning');
    }

    // =========================================================
    // CLASS CHANGE — AUTO ASSIGN
    // =========================================================

    public function test_update_auto_assigns_to_new_session_when_exactly_one_matches()
    {
        $class    = $this->makeClass(['name' => 'X-IPA-1']);
        $newClass = $this->makeClass(['name' => 'X-IPA-2']);
        $voter    = $this->makeVoter(['class_id' => $class->id]);

        $election = $this->makeElection(); // DRAFT

        $oldSession = $this->makeSession($election, $class);
        $newSession = $this->makeSession($election, $newClass);

        $record = $this->makeVoterRecord($voter, $election, [
            'class_id'   => $class->id,
            'session_id' => $oldSession->id,
        ]);

        $response = $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'      => $voter->nis,
                'name'     => $voter->name,
                'class_id' => $newClass->id,
                'email'    => $voter->email,
            ]);

        $response->assertRedirect(route('admin.voters.show', $voter));
        $response->assertSessionHas('success');

        $record->refresh();

        $this->assertSame($newSession->id, $record->session_id);
        $this->assertSame($newClass->id, $record->class_id);
        $this->assertFalse($record->checked_in);
        $this->assertFalse($record->has_voted);

        // users.class_id juga terupdate
        $this->assertSame($newClass->id, $voter->fresh()->class_id);
    }

    public function test_update_detaches_when_no_matching_session()
    {
        $class    = $this->makeClass();
        $newClass = $this->makeClass(); // tidak ada sesi untuk kelas ini
        $voter    = $this->makeVoter(['class_id' => $class->id]);

        $election = $this->makeElection();
        $oldSession = $this->makeSession($election, $class);

        $record = $this->makeVoterRecord($voter, $election, [
            'class_id'   => $class->id,
            'session_id' => $oldSession->id,
        ]);

        $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'      => $voter->nis,
                'name'     => $voter->name,
                'class_id' => $newClass->id,
                'email'    => $voter->email,
            ]);

        $record->refresh();

        $this->assertNull($record->session_id);
        $this->assertSame($newClass->id, $record->class_id);
    }

    public function test_update_detaches_when_multiple_sessions_match()
    {
        $class    = $this->makeClass();
        $newClass = $this->makeClass();
        $voter    = $this->makeVoter(['class_id' => $class->id]);

        // Dua election DRAFT berbeda — masing-masing punya sesi untuk kelas baru
        $election1 = $this->makeElection();
        $election2 = $this->makeElection();

        $oldSession = $this->makeSession($election1, $class);

        // Dua sesi untuk kelas baru (ambigu)
        $this->makeSession($election1, $newClass);
        $this->makeSession($election2, $newClass);

        $record = $this->makeVoterRecord($voter, $election1, [
            'class_id'   => $class->id,
            'session_id' => $oldSession->id,
        ]);

        $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'      => $voter->nis,
                'name'     => $voter->name,
                'class_id' => $newClass->id,
                'email'    => $voter->email,
            ]);

        $record->refresh();

        $this->assertNull($record->session_id);
        $this->assertSame($newClass->id, $record->class_id);
    }

    public function test_update_skips_auto_assign_when_voter_not_in_any_session()
    {
        $class    = $this->makeClass();
        $newClass = $this->makeClass();
        $voter    = $this->makeVoter(['class_id' => $class->id]);

        $election = $this->makeElection();
        // Ada sesi untuk kelas baru, tapi voter belum pernah di-assign
        $this->makeSession($election, $newClass);

        $record = $this->makeVoterRecord($voter, $election, [
            'class_id'   => $class->id,
            'session_id' => null, // belum di-assign
        ]);

        $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'      => $voter->nis,
                'name'     => $voter->name,
                'class_id' => $newClass->id,
                'email'    => $voter->email,
            ]);

        $record->refresh();

        // Tidak auto-assign ke sesi manapun
        $this->assertNull($record->session_id);
        // Tapi class_id tetap diupdate
        $this->assertSame($newClass->id, $record->class_id);
    }

    public function test_update_skips_session_logic_when_no_voter_record_exists()
    {
        $class    = $this->makeClass();
        $newClass = $this->makeClass();
        $voter    = $this->makeVoter(['class_id' => $class->id]);

        // Tidak ada Voter record sama sekali

        $response = $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'      => $voter->nis,
                'name'     => $voter->name,
                'class_id' => $newClass->id,
                'email'    => $voter->email,
            ]);

        $response->assertRedirect(route('admin.voters.show', $voter));

        $this->assertSame($newClass->id, $voter->fresh()->class_id);
    }

    public function test_update_logs_auto_assigned_flag()
    {
        $class    = $this->makeClass();
        $newClass = $this->makeClass();
        $voter    = $this->makeVoter(['class_id' => $class->id]);

        $election = $this->makeElection();
        $oldSession = $this->makeSession($election, $class);
        $this->makeSession($election, $newClass);

        $this->makeVoterRecord($voter, $election, [
            'class_id'   => $class->id,
            'session_id' => $oldSession->id,
        ]);

        $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'      => $voter->nis,
                'name'     => 'Nama Baru',
                'class_id' => $newClass->id,
                'email'    => $voter->email,
            ]);

        $log = ActivityLog::where('action', 'voter.updated')->first();

        $this->assertNotNull($log);
        $this->assertTrue($log->meta['auto_assigned']);
        $this->assertFalse($log->meta['detached']);
        $this->assertTrue($log->meta['class_changed']);
    }

    public function test_update_logs_detached_flag()
    {
        $class    = $this->makeClass();
        $newClass = $this->makeClass();
        $voter    = $this->makeVoter(['class_id' => $class->id]);

        $election = $this->makeElection();
        $oldSession = $this->makeSession($election, $class);
        // Tidak ada sesi untuk kelas baru

        $this->makeVoterRecord($voter, $election, [
            'class_id'   => $class->id,
            'session_id' => $oldSession->id,
        ]);

        $this->actingAs($this->createAdmin())
            ->put(route('admin.voters.update', $voter), [
                'nis'      => $voter->nis,
                'name'     => 'Nama Baru',
                'class_id' => $newClass->id,
                'email'    => $voter->email,
            ]);

        $log = ActivityLog::where('action', 'voter.updated')->first();

        $this->assertNotNull($log);
        $this->assertTrue($log->meta['detached']);
        $this->assertFalse($log->meta['auto_assigned']);
        $this->assertTrue($log->meta['class_changed']);
    }

    // =========================================================
    // USER MODEL — LOCK HELPERS
    // =========================================================

    public function test_can_edit_voter_data_returns_true_when_no_voter_records()
    {
        $voter = $this->makeVoter();

        $this->assertTrue($voter->canEditVoterData());
        $this->assertNull($voter->getEditLockReason());
    }

    public function test_can_edit_voter_data_returns_false_when_election_active()
    {
        $class    = $this->makeClass();
        $voter    = $this->makeVoter(['class_id' => $class->id]);
        $election = $this->makeElection([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHour(),
            'end_at'   => now()->addHours(5),
        ]);

        $this->makeVoterRecord($voter, $election, ['class_id' => $class->id]);

        $voter->refresh()->load(['voterRecords.election', 'voterRecords.session']);

        $this->assertFalse($voter->canEditVoterData());
        $this->assertSame('Pemilihan sedang aktif', $voter->getEditLockReason());
    }

    public function test_can_edit_voter_data_returns_false_when_already_voted()
    {
        $class    = $this->makeClass();
        $voter    = $this->makeVoter(['class_id' => $class->id]);
        $election = $this->makeElection();

        $this->makeVoterRecord($voter, $election, [
            'class_id'  => $class->id,
            'has_voted' => true,
            'voted_at'  => now(),
        ]);

        $voter->refresh()->load(['voterRecords.election', 'voterRecords.session']);

        $this->assertFalse($voter->canEditVoterData());
        $this->assertSame('Voter sudah melakukan voting', $voter->getEditLockReason());
    }

    public function test_can_edit_voter_data_returns_true_when_election_closed()
    {
        $class    = $this->makeClass();
        $voter    = $this->makeVoter(['class_id' => $class->id]);
        $election = $this->makeElection([
            'status'   => ElectionStatus::CLOSED,
            'start_at' => now()->subDays(2),
            'end_at'   => now()->subDay(),
        ]);

        $this->makeVoterRecord($voter, $election, ['class_id' => $class->id]);

        $voter->refresh()->load(['voterRecords.election', 'voterRecords.session']);

        $this->assertTrue($voter->canEditVoterData());
    }

    public function test_can_edit_voter_data_returns_true_when_election_published()
    {
        $class    = $this->makeClass();
        $voter    = $this->makeVoter(['class_id' => $class->id]);
        $election = $this->makeElection([
            'status'             => ElectionStatus::PUBLISHED,
            'start_at'           => now()->subDays(2),
            'end_at'             => now()->subDay(),
            'hasil_published_at' => now(),
        ]);

        $this->makeVoterRecord($voter, $election, ['class_id' => $class->id]);

        $voter->refresh()->load(['voterRecords.election', 'voterRecords.session']);

        $this->assertTrue($voter->canEditVoterData());
    }
}
