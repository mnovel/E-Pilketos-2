<?php

namespace Tests\Feature\Admin;

use App\Enums\ElectionStatus;
use App\Models\Candidate;
use App\Models\ClassRoom;
use App\Models\Election;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CandidateManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // ✅ Laravel 11+ CSRF middleware
        $this->withoutMiddleware(PreventRequestForgery::class);

        Storage::fake('public');
    }

    protected function candidateData(Election $election, ClassRoom $class, array $override = []): array
    {
        return array_merge([
            'election_id'   => $election->id,
            'no_urut'       => 1,
            'nama'          => 'Ahmad Fauzi',
            'class_id'      => $class->id,
            'visi'          => 'Mewujudkan OSIS aktif',
            'misi'          => 'Misi lengkap',
            'program_kerja' => 'Program kerja',
        ], $override);
    }

    // ==========================================
    // STORE
    // ==========================================

    public function test_admin_can_create_candidate_in_draft_election()
    {
        $admin = $this->createAdmin();
        $election = Election::factory()->draft()->create();
        $class = ClassRoom::factory()->create();

        $response = $this->actingAs($admin)->post(
            route('admin.candidates.store'),
            $this->candidateData($election, $class)
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('candidates', [
            'election_id' => $election->id,
            'nama'        => 'Ahmad Fauzi',
        ]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'candidate.created']);
    }

    public function test_cannot_create_candidate_in_active_election()
    {
        $admin = $this->createAdmin();
        $election = Election::factory()->active()->create();
        $class = ClassRoom::factory()->create();

        $response = $this->actingAs($admin)->post(
            route('admin.candidates.store'),
            $this->candidateData($election, $class)
        );

        $response->assertSessionHas('error');
        $this->assertEquals(0, Candidate::count());
    }

    public function test_no_urut_must_be_unique_per_election()
    {
        $admin = $this->createAdmin();
        $election = Election::factory()->draft()->create();
        $class = ClassRoom::factory()->create();

        Candidate::factory()->create([
            'election_id' => $election->id,
            'no_urut'     => 1,
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.candidates.store'),
            $this->candidateData($election, $class, ['no_urut' => 1])
        );

        $response->assertSessionHasErrors('no_urut');
    }

    public function test_can_create_candidate_with_photo()
    {
        $admin = $this->createAdmin();
        $election = Election::factory()->draft()->create();
        $class = ClassRoom::factory()->create();

        $response = $this->actingAs($admin)->post(
            route('admin.candidates.store'),
            $this->candidateData($election, $class, [
                'foto' => UploadedFile::fake()->image('foto.jpg', 400, 400),
            ])
        );

        $response->assertRedirect();

        $candidate = Candidate::first();
        $this->assertNotNull($candidate->foto);
        Storage::disk('public')->assertExists($candidate->foto);
    }

    // ==========================================
    // UPDATE
    // ==========================================

    public function test_admin_can_update_candidate_in_draft()
    {
        $admin = $this->createAdmin();
        $election = Election::factory()->draft()->create();
        $class = ClassRoom::factory()->create();
        $candidate = Candidate::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
            'nama'        => 'Nama Lama',
        ]);

        $response = $this->actingAs($admin)->put(
            route('admin.candidates.update', $candidate),
            [
                'no_urut'       => $candidate->no_urut,
                'nama'          => 'Nama Baru',
                'class_id'      => $class->id,
                'visi'          => 'Visi baru',
                'misi'          => 'Misi baru',
                'program_kerja' => 'Program baru',
            ]
        );

        $response->assertRedirect();
        $this->assertEquals('Nama Baru', $candidate->fresh()->nama);
        $this->assertDatabaseHas('activity_logs', ['action' => 'candidate.updated']);
    }

    public function test_cannot_update_candidate_in_active_election()
    {
        $admin = $this->createAdmin();
        $election = Election::factory()->active()->create();
        $candidate = Candidate::factory()->create(['election_id' => $election->id]);

        $response = $this->actingAs($admin)->put(
            route('admin.candidates.update', $candidate),
            [
                'no_urut'       => $candidate->no_urut,
                'nama'          => 'Nama Baru',
                'class_id'      => $candidate->class_id,
                'visi'          => 'Visi',
                'misi'          => 'Misi',
            ]
        );

        $response->assertSessionHas('error');
    }

    // ==========================================
    // DESTROY
    // ==========================================

    public function test_admin_can_delete_candidate_in_draft()
    {
        $admin = $this->createAdmin();
        $election = Election::factory()->draft()->create();
        $candidate = Candidate::factory()->create(['election_id' => $election->id]);
        $candidateId = $candidate->id;

        $response = $this->actingAs($admin)
            ->delete(route('admin.candidates.destroy', $candidate));

        $response->assertRedirect();
        $this->assertSoftDeleted('candidates', ['id' => $candidateId]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'candidate.deleted']);
    }

    public function test_cannot_delete_candidate_in_active_election()
    {
        $admin = $this->createAdmin();
        $election = Election::factory()->active()->create();
        $candidate = Candidate::factory()->create(['election_id' => $election->id]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.candidates.destroy', $candidate));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('candidates', ['id' => $candidate->id]);
    }

    public function test_deleted_candidate_still_in_database_with_deleted_at()
    {
        $admin = $this->createAdmin();
        $election = Election::factory()->draft()->create();
        $candidate = Candidate::factory()->create(['election_id' => $election->id]);

        $this->actingAs($admin)
            ->delete(route('admin.candidates.destroy', $candidate));

        $this->assertDatabaseHas('candidates', ['id' => $candidate->id]);
        $this->assertNotNull($candidate->fresh()->deleted_at);
    }

    // ==========================================
    // AUTHORIZATION
    // ==========================================

    public function test_operator_cannot_create_candidate()
    {
        $operator = $this->createOperator();
        $election = Election::factory()->draft()->create();
        $class = ClassRoom::factory()->create();

        $response = $this->actingAs($operator)->post(
            route('admin.candidates.store'),
            $this->candidateData($election, $class)
        );

        $response->assertForbidden();
    }
}
