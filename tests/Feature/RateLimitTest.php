<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
        Storage::fake('public');
    }

    // =========================================================
    // LOGIN — 5 attempts per minute
    // =========================================================

    public function test_login_allows_5_attempts_per_minute()
    {
        $admin = $this->createAdmin();

        // 5 kali dengan password BENAR → semua sukses
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email'    => $admin->email,
                'password' => 'password',
            ]);

            $response->assertRedirect(); // sukses redirect
            $this->post('/logout');
        }
    }

    public function test_login_blocks_6th_attempt()
    {
        $admin = $this->createAdmin();

        // 5 kali GAGAL (password salah)
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email'    => $admin->email,
                'password' => 'salah',
            ]);
        }

        // Attempt ke-6 → 429 Too Many Requests
        $response = $this->post('/login', [
            'email'    => $admin->email,
            'password' => 'salah',
        ]);

        $response->assertStatus(429);
    }

    public function test_login_blocks_even_with_correct_password()
    {
        $admin = $this->createAdmin();

        // 5 kali gagal dulu
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email'    => $admin->email,
                'password' => 'salah',
            ]);
        }

        // Attempt ke-6 dengan password BENAR → tetap 429
        $response = $this->post('/login', [
            'email'    => $admin->email,
            'password' => 'password',
        ]);

        $response->assertStatus(429);
        $this->assertGuest();
    }

    // =========================================================
    // REGISTER — 3 per hour
    // =========================================================

    public function test_register_allows_3_attempts_per_hour()
    {
        $class = ClassRoom::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $response = $this->post('/register', [
                'nis'                   => '90100' . $i,
                'name'                  => 'Siswa ' . $i,
                'class_id'              => $class->id,
                'email'                 => "siswa-{$i}@test.com",
                'password'              => 'password123',
                'password_confirmation' => 'password123',
                'kartu_pelajar'         => UploadedFile::fake()->image('k.jpg'),
            ]);

            // Sukses redirect ke register.success
            $response->assertRedirect(route('register.success'));
        }
    }

    public function test_register_blocks_4th_attempt()
    {
        $class = ClassRoom::factory()->create();

        // 3 kali dulu
        for ($i = 0; $i < 3; $i++) {
            $this->post('/register', [
                'nis'                   => '90100' . $i,
                'name'                  => 'Siswa ' . $i,
                'class_id'              => $class->id,
                'email'                 => "siswa-{$i}@test.com",
                'password'              => 'password123',
                'password_confirmation' => 'password123',
                'kartu_pelajar'         => UploadedFile::fake()->image('k.jpg'),
            ]);
        }

        // Attempt ke-4 → 429
        $response = $this->post('/register', [
            'nis'                   => '901099',
            'name'                  => 'Siswa Baru',
            'class_id'              => $class->id,
            'email'                 => 'baru@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'kartu_pelajar'         => UploadedFile::fake()->image('k.jpg'),
        ]);

        $response->assertStatus(429);
    }

    // =========================================================
    // CEK STATUS — 10 per minute
    // =========================================================

    public function test_cek_status_allows_10_attempts_per_minute()
    {
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post(route('cek-status.check'), [
                'nis' => '999999',
            ]);

            // Response normal (200, bukan 429)
            $response->assertOk();
        }
    }

    public function test_cek_status_blocks_11th_attempt()
    {
        // 10 kali dulu
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('cek-status.check'), [
                'nis' => '999999',
            ]);
        }

        // Attempt ke-11 → 429
        $response = $this->post(route('cek-status.check'), [
            'nis' => '999999',
        ]);

        $response->assertStatus(429);
    }

    // =========================================================
    // DEVICE VOTING SUBMIT — 10 per minute
    // =========================================================

    public function test_device_voting_submit_allows_10_per_minute()
    {
        // ✅ Route di-guard role:operator,admin → harus login dulu
        $operator = $this->createOperator();

        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($operator)
                ->postJson(route('device.voting.submit'), [
                    'device_id'    => 99999,
                    'candidate_id' => 99999,
                ]);

            // 422 karena validasi, bukan 429
            $response->assertStatus(422);
        }
    }

    public function test_device_voting_submit_blocks_11th_attempt()
    {
        $operator = $this->createOperator();

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($operator)->postJson(route('device.voting.submit'), [
                'device_id'    => 99999,
                'candidate_id' => 99999,
            ]);
        }

        // Attempt ke-11 → 429
        $response = $this->actingAs($operator)->postJson(route('device.voting.submit'), [
            'device_id'    => 99999,
            'candidate_id' => 99999,
        ]);

        $response->assertStatus(429);
    }

    // =========================================================
    // DEVICE STATUS POLLING — 120 per minute
    // =========================================================

    public function test_device_status_highly_permissive()
    {
        // Endpoint ini di-polling tiap 10 detik → limit tinggi (120/menit).
        // Test 50 kali pertama, tidak boleh kena 429.
        $operator = $this->createOperator();

        for ($i = 0; $i < 50; $i++) {
            $response = $this->actingAs($operator)
                ->getJson(route('device.checkin.status'));

            $response->assertStatus(200);
        }
    }

    // =========================================================
    // RATE LIMIT RESET VIA TRAVEL
    // =========================================================

    public function test_rate_limit_resets_after_time_window()
    {
        $admin = $this->createAdmin();

        // 5 kali gagal → kena limit
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email'    => $admin->email,
                'password' => 'salah',
            ]);
        }

        // Attempt ke-6 → 429
        $this->post('/login', [
            'email'    => $admin->email,
            'password' => 'salah',
        ])->assertStatus(429);

        // ✅ Travel 61 detik — keluar dari window throttle:5,1
        $this->travel(61)->seconds();

        // Sekarang bisa login lagi
        $this->post('/login', [
            'email'    => $admin->email,
            'password' => 'password',
        ])->assertRedirect();
    }
}
