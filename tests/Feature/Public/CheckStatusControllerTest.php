<?php

namespace Tests\Feature\Public;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckStatusControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_index_page_loads()
    {
        $response = $this->get(route('cek-status.index'));
        $response->assertStatus(200);
        $response->assertViewIs('public.check-status.index');
    }

    public function test_check_finds_voter_by_nis()
    {
        $voter = User::factory()->voter()->create([
            'nis'   => '901001',
            'name'  => 'Ahmad Fauzi',
            'role'  => UserRole::VOTER,
            'status' => VoterStatus::VERIFIED,
        ]);
        $voter->assignRole('voter');

        $response = $this->post(route('cek-status.check'), ['nis' => '901001']);

        $response->assertStatus(200);
        $response->assertViewIs('public.check-status.result');
        $response->assertViewHas('voter', function ($v) use ($voter) {
            return $v && $v->id === $voter->id;
        });
    }

    public function test_check_returns_null_for_unknown_nis()
    {
        $response = $this->post(route('cek-status.check'), ['nis' => '999999']);

        $response->assertStatus(200);
        $response->assertViewHas('voter', null);
    }

    public function test_check_returns_null_for_non_voter_user()
    {
        $admin = $this->createAdmin();
        $admin->update(['nis' => '901001']);

        $response = $this->post(route('cek-status.check'), ['nis' => '901001']);

        $response->assertStatus(200);
        $response->assertViewHas('voter', null);
    }

    public function test_nis_is_required()
    {
        $response = $this->post(route('cek-status.check'), []);

        $response->assertSessionHasErrors('nis');
    }

    public function test_check_shows_pending_voter_status()
    {
        $voter = User::factory()->pending()->create([
            'nis'  => '901002',
            'role' => UserRole::VOTER,
        ]);
        $voter->assignRole('voter');

        $response = $this->post(route('cek-status.check'), ['nis' => '901002']);

        $response->assertStatus(200);
        $response->assertViewHas('voter', function ($v) {
            return $v && $v->status === VoterStatus::PENDING;
        });
    }
}
