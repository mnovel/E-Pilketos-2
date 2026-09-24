<?php

namespace Tests\Feature\Device;

use App\Enums\ElectionStatus;
use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Models\CheckinDevice;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\ElectionSession;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckinDeviceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // ✅ Disable CSRF + EncryptCookies biar bisa test cookie plain
        $this->withoutMiddleware([
            PreventRequestForgery::class,
            EncryptCookies::class,
        ]);
    }

    // =========================================================
    // HELPERS
    // =========================================================

    protected function makeActiveElection(): Election
    {
        return Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHour(),
            'end_at'   => now()->addHours(2),
        ]);
    }

    // =========================================================
    // INDEX — ACCESS
    // =========================================================

    public function test_guest_redirected_from_index()
    {
        $this->get(route('device.checkin.index'))
            ->assertRedirect(route('login'));
    }

    public function test_voter_cannot_access_index()
    {
        $this->actingAs($this->createVoter())
            ->get(route('device.checkin.index'))
            ->assertForbidden();
    }

    public function test_admin_can_access_index()
    {
        $this->actingAs($this->createAdmin())
            ->get(route('device.checkin.index'))
            ->assertOk();
    }

    public function test_operator_can_access_index()
    {
        $this->actingAs($this->createOperator())
            ->get(route('device.checkin.index'))
            ->assertOk();
    }

    // =========================================================
    // KIOSK — NO ACTIVE ELECTION
    // =========================================================

    public function test_kiosk_shows_waiting_when_no_active_election()
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)
            ->get(route('device.checkin.index'));

        $response->assertOk();
        $response->assertSessionMissing('checkin_device_id');
        $response->assertSessionMissing('checkin_election_id');
    }

    public function test_kiosk_does_not_pick_draft_election()
    {
        Election::factory()->create([
            'status'   => ElectionStatus::DRAFT,
            'start_at' => now()->addHour(),
            'end_at'   => now()->addHours(2),
        ]);

        $this->actingAs($this->createOperator())
            ->get(route('device.checkin.index'))
            ->assertSessionMissing('checkin_device_id');
    }

    public function test_kiosk_does_not_pick_election_not_yet_started()
    {
        Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->addHour(),
            'end_at'   => now()->addHours(3),
        ]);

        $this->actingAs($this->createOperator())
            ->get(route('device.checkin.index'))
            ->assertSessionMissing('checkin_device_id');
    }

    // =========================================================
    // KIOSK — ENDED ELECTION
    // =========================================================

    public function test_kiosk_does_not_pick_ended_election()
    {
        $election = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHours(3),
            'end_at'   => now()->subHour(),
        ]);

        $this->actingAs($this->createOperator())
            ->get(route('device.checkin.index'))
            ->assertSessionMissing('checkin_device_id');

        $this->assertSame(ElectionStatus::ACTIVE, $election->fresh()->status);
        $this->assertSame(0, CheckinDevice::count());
    }

    // =========================================================
    // KIOSK — WITH ACTIVE ELECTION
    // =========================================================

    public function test_kiosk_creates_device_for_active_election()
    {
        $election = $this->makeActiveElection();
        $operator = $this->createOperator();

        $this->actingAs($operator)
            ->get(route('device.checkin.index'))
            ->assertSessionHas('checkin_device_id')
            ->assertSessionHas('checkin_election_id', $election->id);

        $this->assertSame(1, CheckinDevice::where('election_id', $election->id)->count());
    }

    public function test_kiosk_device_has_valid_token()
    {
        $election = $this->makeActiveElection();

        $this->actingAs($this->createOperator())
            ->get(route('device.checkin.index'));

        $device = CheckinDevice::where('election_id', $election->id)->first();

        $this->assertNotNull($device);
        $this->assertNotEmpty($device->device_token);
        $this->assertTrue($device->token_expired_at->isFuture());
    }

    // =========================================================
    // ✅ COOKIE — DEVICE LABEL PERSISTENCE
    // =========================================================

    public function test_kiosk_sets_device_label_cookie()
    {
        $this->makeActiveElection();

        $response = $this->actingAs($this->createOperator())
            ->get(route('device.checkin.index'));

        // ✅ Cookie di-set di response
        $response->assertCookie('checkin_device_label');
    }

    public function test_kiosk_cookie_label_matches_device_label()
    {
        $election = $this->makeActiveElection();

        $response = $this->actingAs($this->createOperator())
            ->get(route('device.checkin.index'));

        $device = CheckinDevice::where('election_id', $election->id)->first();
        $cookieValue = $response->getCookie('checkin_device_label', false)->getValue();

        $this->assertSame($device->device_label, $cookieValue);
    }

    public function test_kiosk_reuses_existing_device_across_requests()
    {
        $election = $this->makeActiveElection();
        $operator = $this->createOperator();

        // Request pertama — device dibuat
        $this->actingAs($operator)
            ->get(route('device.checkin.index'));

        $device = CheckinDevice::where('election_id', $election->id)->first();
        $this->assertNotNull($device);

        $deviceCount = CheckinDevice::where('election_id', $election->id)->count();

        // Request kedua — kirim cookie dari request pertama
        $this->actingAs($operator)
            ->withUnencryptedCookie('checkin_device_label', $device->device_label)
            ->get(route('device.checkin.index'));

        // ✅ Device count tidak bertambah — reuse device existing
        $this->assertSame($deviceCount, CheckinDevice::where('election_id', $election->id)->count());
    }

    public function test_kiosk_reuses_device_when_session_expired()
    {
        // ✅ Skenario utama BUG #7:
        // Session expired tapi cookie masih valid → device di-reuse, tidak orphan
        $election = $this->makeActiveElection();

        // Pre-create device dengan label known (simulasi device lama yang masih ada)
        $existingDevice = CheckinDevice::create([
            'election_id'      => $election->id,
            'device_label'     => 'Kiosk-EXISTING',
            'device_token'     => 'TOKEN-EXISTING',
            'token_expired_at' => now()->addMinutes(5),
            'last_ping_at'     => now(),
        ]);

        // Request dengan cookie label known + session KOSONG (simulasi expired)
        $this->actingAs($this->createOperator())
            ->withUnencryptedCookie('checkin_device_label', 'Kiosk-EXISTING')
            ->get(route('device.checkin.index'));

        // ✅ Reuse device existing — tidak ada duplikat
        $this->assertSame(1, CheckinDevice::where('election_id', $election->id)->count());
        $this->assertSame($existingDevice->id, CheckinDevice::first()->id);
    }

    public function test_kiosk_generates_new_label_when_cookie_missing()
    {
        $election = $this->makeActiveElection();

        // Request tanpa cookie
        $this->actingAs($this->createOperator())
            ->get(route('device.checkin.index'));

        $device = CheckinDevice::where('election_id', $election->id)->first();

        $this->assertNotNull($device);
        $this->assertStringStartsWith('Kiosk-', $device->device_label);
    }

    public function test_kiosk_falls_back_to_session_when_cookie_missing()
    {
        // ✅ Backward compat — kalau user upgrade dari versi session-based
        $election = $this->makeActiveElection();

        $this->actingAs($this->createOperator())
            ->withSession(['checkin_device_label' => 'Kiosk-OLD-SESSION'])
            ->get(route('device.checkin.index'));

        // Device dibuat dengan label dari session (fallback)
        $device = CheckinDevice::where('election_id', $election->id)->first();
        $this->assertSame('Kiosk-OLD-SESSION', $device->device_label);

        // Cookie tetap di-set (untuk request berikutnya)
        // tidak assert di sini karena cookie sudah di-set
    }

    // =========================================================
    // CLOSED SESSION FLAG
    // =========================================================

    public function test_index_shows_closed_view_when_manually_closed()
    {
        $this->actingAs($this->createOperator())
            ->withSession(['checkin_manually_closed' => true])
            ->get(route('device.checkin.index'))
            ->assertOk()
            ->assertViewIs('device.checkin.closed');
    }

    // =========================================================
    // STATUS — NO DEVICE
    // =========================================================

    public function test_status_returns_waiting_when_no_device_and_no_election()
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)
            ->getJson(route('device.checkin.status'));

        $response->assertOk();
        $response->assertJson([
            'status' => 'waiting',
            'action' => 'none',
        ]);
    }

    public function test_status_returns_new_election_when_election_starts()
    {
        $operator = $this->createOperator();

        $this->makeActiveElection();

        $response = $this->actingAs($operator)
            ->getJson(route('device.checkin.status'));

        $response->assertOk();
        $response->assertJson([
            'status' => 'new_election',
            'action' => 'reload',
        ]);
    }

    // =========================================================
    // STATUS — WITH DEVICE
    // =========================================================

    public function test_status_returns_active_when_device_valid()
    {
        $operator = $this->createOperator();
        $election = $this->makeActiveElection();

        $this->actingAs($operator)->get(route('device.checkin.index'));

        $device = CheckinDevice::where('election_id', $election->id)->first();
        $device->update(['last_ping_at' => now()]);

        $response = $this->actingAs($operator)
            ->getJson(route('device.checkin.status'));

        $response->assertOk();
        $response->assertJson(['status' => 'active']);

        $json = $response->json();
        $this->assertArrayHasKey('token', $json);
        $this->assertArrayHasKey('expires_in', $json);
        $this->assertArrayHasKey('total_checked', $json);
        $this->assertArrayHasKey('total_voters', $json);
    }

    public function test_status_pings_device()
    {
        $operator = $this->createOperator();
        $election = $this->makeActiveElection();

        $this->actingAs($operator)->get(route('device.checkin.index'));

        $device = CheckinDevice::where('election_id', $election->id)->first();
        $device->update(['last_ping_at' => now()->subMinutes(10)]);

        $this->actingAs($operator)->getJson(route('device.checkin.status'));

        $device->refresh();
        $this->assertTrue($device->last_ping_at->isAfter(now()->subMinute()));
    }

    public function test_status_rotates_token_when_expired()
    {
        $operator = $this->createOperator();
        $election = $this->makeActiveElection();

        $this->actingAs($operator)->get(route('device.checkin.index'));

        $device = CheckinDevice::where('election_id', $election->id)->first();
        $oldToken = $device->device_token;

        $device->update(['token_expired_at' => now()->subMinute()]);

        $this->actingAs($operator)->getJson(route('device.checkin.status'));

        $device->refresh();
        $this->assertNotSame($oldToken, $device->device_token);
        $this->assertTrue($device->token_expired_at->isFuture());
    }

    public function test_status_returns_scanned_with_voter_info()
    {
        $operator = $this->createOperator();
        $election = $this->makeActiveElection();

        $this->actingAs($operator)->get(route('device.checkin.index'));
        $device = CheckinDevice::where('election_id', $election->id)->first();

        cache()->put("checkin:last:{$device->id}", [
            'nama'  => 'Ahmad Fauzi',
            'kelas' => 'X-IPA-1',
            'time'  => '10:30:00',
        ], now()->addSeconds(30));

        $response = $this->actingAs($operator)
            ->getJson(route('device.checkin.status'));

        $response->assertOk();
        $response->assertJson([
            'status' => 'scanned',
            'voter'  => [
                'nama'  => 'Ahmad Fauzi',
                'kelas' => 'X-IPA-1',
            ],
        ]);

        $this->assertNull(cache("checkin:last:{$device->id}"));
    }

    public function test_status_returns_device_lost_when_device_deleted()
    {
        $operator = $this->createOperator();
        $election = $this->makeActiveElection();

        $this->actingAs($operator)->get(route('device.checkin.index'));
        $device = CheckinDevice::where('election_id', $election->id)->first();

        $device->delete();

        $response = $this->actingAs($operator)
            ->getJson(route('device.checkin.status'));

        $response->assertJson([
            'status' => 'device_lost',
            'action' => 'reload',
        ]);

        $this->assertNull(session('checkin_device_id'));
    }

    public function test_status_returns_election_ended_when_time_passes()
    {
        $operator = $this->createOperator();
        $election = $this->makeActiveElection();

        $this->actingAs($operator)->get(route('device.checkin.index'));

        $election->update(['end_at' => now()->subMinute()]);

        $response = $this->actingAs($operator)
            ->getJson(route('device.checkin.status'));

        $response->assertJson([
            'status' => 'election_ended',
            'action' => 'reload',
        ]);

        $this->assertSame(ElectionStatus::CLOSED, $election->fresh()->status);
    }

    public function test_status_returns_election_not_active_when_status_changes()
    {
        $operator = $this->createOperator();
        $election = $this->makeActiveElection();

        $this->actingAs($operator)->get(route('device.checkin.index'));

        $election->update(['status' => ElectionStatus::CLOSED]);

        $response = $this->actingAs($operator)
            ->getJson(route('device.checkin.status'));

        $response->assertJson([
            'status' => 'election_not_active',
            'action' => 'reload',
        ]);
    }

    public function test_status_returns_out_of_window_when_time_not_match()
    {
        $operator = $this->createOperator();
        $election = $this->makeActiveElection();

        $this->actingAs($operator)->get(route('device.checkin.index'));

        $election->update(['start_at' => now()->addHour()]);

        $response = $this->actingAs($operator)
            ->getJson(route('device.checkin.status'));

        $response->assertJson([
            'status' => 'election_out_of_window',
            'action' => 'reload',
        ]);
    }

    // =========================================================
    // STATUS — CHECKIN STATS
    // =========================================================

    public function test_status_returns_checkin_stats()
    {
        $operator = $this->createOperator();
        $election = $this->makeActiveElection();
        $class = ClassRoom::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $user = $this->createVoter($class);

            Voter::create([
                'election_id' => $election->id,
                'user_id'     => $user->id,
                'class_id'    => $class->id,
                'checked_in'  => $i < 3,
            ]);
        }

        $this->actingAs($operator)->get(route('device.checkin.index'));

        $response = $this->actingAs($operator)
            ->getJson(route('device.checkin.status'));

        $response->assertJson([
            'total_checked' => 3,
            'total_voters'  => 5,
        ]);
    }

    // =========================================================
    // CLOSE — DEVICE
    // =========================================================

    public function test_close_deletes_device_and_sets_flag()
    {
        $operator = $this->createOperator();
        $election = $this->makeActiveElection();

        $this->actingAs($operator)->get(route('device.checkin.index'));
        $device = CheckinDevice::where('election_id', $election->id)->first();
        $this->assertNotNull($device);

        $response = $this->actingAs($operator)
            ->post(route('device.checkin.close'));

        $response->assertRedirect(route('device.checkin.index'));
        $response->assertSessionHas('success');

        $this->assertNull(CheckinDevice::find($device->id));

        $this->assertTrue(session('checkin_manually_closed'));
        $this->assertNull(session('checkin_device_id'));
        $this->assertNull(session('checkin_election_id'));
    }

    public function test_close_forgets_device_label_cookie()
    {
        // ✅ Close harus hapus cookie biar tidak reuse device lama
        $operator = $this->createOperator();
        $this->makeActiveElection();

        $response = $this->actingAs($operator)
            ->withUnencryptedCookie('checkin_device_label', 'Kiosk-EXISTING')
            ->post(route('device.checkin.close'));

        $response->assertRedirect(route('device.checkin.index'));
        $response->assertCookieExpired('checkin_device_label');
    }

    public function test_close_without_device_still_sets_flag()
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)
            ->post(route('device.checkin.close'));

        $response->assertRedirect(route('device.checkin.index'));
        $this->assertTrue(session('checkin_manually_closed'));
    }

    // =========================================================
    // REOPEN — DEVICE
    // =========================================================

    public function test_reopen_clears_closed_flag()
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)
            ->withSession(['checkin_manually_closed' => true])
            ->post(route('device.checkin.reopen'));

        $response->assertRedirect(route('device.checkin.index'));
        $this->assertNull(session('checkin_manually_closed'));
    }

    public function test_reopen_allows_kiosk_to_start_again()
    {
        $operator = $this->createOperator();
        $this->makeActiveElection();

        $this->actingAs($operator)
            ->withSession(['checkin_manually_closed' => true])
            ->post(route('device.checkin.reopen'));

        $this->actingAs($operator)->get(route('device.checkin.index'));

        $this->assertNotNull(session('checkin_device_id'));
        $this->assertSame(1, CheckinDevice::count());
    }

    // =========================================================
    // INTEGRATION
    // =========================================================

    public function test_full_lifecycle_open_close_reopen()
    {
        $operator = $this->createOperator();
        $election = $this->makeActiveElection();

        // 1. Buka kiosk — device dibuat
        $this->actingAs($operator)->get(route('device.checkin.index'));
        $this->assertSame(1, CheckinDevice::count());
        $device1 = CheckinDevice::first();

        // 2. Close — device dihapus
        $this->actingAs($operator)->post(route('device.checkin.close'));
        $this->assertSame(0, CheckinDevice::count());
        $this->assertTrue(session('checkin_manually_closed'));

        // 3. Reopen
        $this->actingAs($operator)->post(route('device.checkin.reopen'));
        $this->assertNull(session('checkin_manually_closed'));

        // 4. Buka kiosk lagi — device baru (karena cookie sudah di-forget saat close)
        $this->actingAs($operator)->get(route('device.checkin.index'));
        $this->assertSame(1, CheckinDevice::count());
        $device2 = CheckinDevice::first();

        // Device beda (karena cookie dihapus + session dihapus → label baru)
        $this->assertNotSame($device1->id, $device2->id);
    }

    // =========================================================
    // ✅ REGRESSION — BUG #7 (DEVICE ORPHAN)
    // =========================================================

    public function test_session_expiry_does_not_create_orphan_device()
    {
        // ✅ Test regression untuk BUG #7:
        // Skenario: operator buka kiosk → session expired → refresh
        // Harusnya reuse device existing (cookie), bukan bikin device baru
        $election = $this->makeActiveElection();
        $operator = $this->createOperator();

        // Sesi 1: buka kiosk pertama kali
        $this->actingAs($operator)->get(route('device.checkin.index'));

        $device1 = CheckinDevice::where('election_id', $election->id)->first();
        $this->assertNotNull($device1);
        $this->assertSame(1, CheckinDevice::count());

        // Simulasi session expired — session cookie hilang, tapi device label cookie masih ada
        // Kirim cookie label + session kosong (tidak ada checkin_device_id)
        $this->actingAs($operator)
            ->withUnencryptedCookie('checkin_device_label', $device1->device_label)
            ->get(route('device.checkin.index'));

        // ✅ Device count tetap 1 — tidak ada orphan
        $this->assertSame(1, CheckinDevice::count());
        $this->assertSame($device1->id, CheckinDevice::first()->id);
    }

    public function test_multiple_session_expiries_keep_device_single()
    {
        // ✅ Simulasi 5x session expired — device tetap 1
        $election = $this->makeActiveElection();
        $operator = $this->createOperator();

        // Buka pertama
        $this->actingAs($operator)->get(route('device.checkin.index'));
        $device = CheckinDevice::where('election_id', $election->id)->first();

        // 5x refresh dengan cookie (simulasi session expired berkali-kali)
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($operator)
                ->withUnencryptedCookie('checkin_device_label', $device->device_label)
                ->get(route('device.checkin.index'));
        }

        // ✅ Tetap 1 device
        $this->assertSame(1, CheckinDevice::count());
    }
}
