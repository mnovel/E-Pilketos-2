<?php

namespace Tests\Feature\Auth;

use App\Enums\VoterStatus;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_voter_can_register_with_valid_data()
    {
        $class = ClassRoom::factory()->create();

        $response = $this->post('/register', [
            'nis'           => '901001',
            'name'          => 'Ahmad Fauzi',
            'class_id'      => $class->id,
            'email'         => 'ahmad@test.com',
            'password'      => 'password123',
            'password_confirmation' => 'password123',
            'kartu_pelajar' => UploadedFile::fake()->image('kartu.jpg', 800, 600),
        ]);

        $response->assertRedirect(route('register.success'));

        $this->assertDatabaseHas('users', [
            'nis'    => '901001',
            'email'  => 'ahmad@test.com',
            'status' => VoterStatus::PENDING->value,
        ]);

        $user = User::where('nis', '901001')->first();
        $this->assertTrue($user->hasRole('voter'));
    }

    public function test_register_logs_activity()
    {
        $class = ClassRoom::factory()->create();

        $this->post('/register', [
            'nis'           => '901001',
            'name'          => 'Ahmad Fauzi',
            'class_id'      => $class->id,
            'email'         => 'ahmad@test.com',
            'password'      => 'password123',
            'password_confirmation' => 'password123',
            'kartu_pelajar' => UploadedFile::fake()->image('kartu.jpg'),
        ]);

        $user = User::where('nis', '901001')->first();

        $this->assertDatabaseHas('activity_logs', [
            'action'   => 'voter.register',
            'user_id'  => $user->id,
        ]);
    }

    public function test_register_fails_with_duplicate_nis()
    {
        $class = ClassRoom::factory()->create();
        User::factory()->create(['nis' => '901001']);

        $response = $this->post('/register', [
            'nis'           => '901001',
            'name'          => 'Ahmad Fauzi',
            'class_id'      => $class->id,
            'email'         => 'ahmad@test.com',
            'password'      => 'password123',
            'password_confirmation' => 'password123',
            'kartu_pelajar' => UploadedFile::fake()->image('kartu.jpg'),
        ]);

        $response->assertSessionHasErrors('nis');
    }

    public function test_register_fails_without_kartu_pelajar()
    {
        $class = ClassRoom::factory()->create();

        $response = $this->post('/register', [
            'nis'           => '901001',
            'name'          => 'Ahmad Fauzi',
            'class_id'      => $class->id,
            'email'         => 'ahmad@test.com',
            'password'      => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('kartu_pelajar');
    }
}
