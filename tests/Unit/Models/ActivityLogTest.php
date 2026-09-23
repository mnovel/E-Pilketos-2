<?php

namespace Tests\Unit\Models;

use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // LABEL
    // ==========================================

    #[DataProvider('actionLabelProvider')]
    public function test_get_action_label_returns_correct_label(string $action, string $expectedLabel)
    {
        $log = new ActivityLog(['action' => $action]);
        $this->assertEquals($expectedLabel, $log->getActionLabel());
    }

    public static function actionLabelProvider(): array
    {
        return [
            ['auth.login',              'Login'],
            ['auth.logout',             'Logout'],
            ['auth.login_rejected',     'Login Ditolak'],
            ['voter.register',          'Daftar Voter'],
            ['voter.verified',          'Verifikasi Voter'],
            ['voter.rejected',          'Tolak Voter'],
            ['voter.bulk_verified',     'Verifikasi Voter (Bulk)'],
            ['voter.bulk_rejected',     'Tolak Voter (Bulk)'],
            ['voter.imported',          'Import Voter'],
            ['voter.password_reset',    'Reset Password Voter'],
            ['voter.password_generated', 'Generate Password Voter'],
            ['voter.voting_scanned',    'Scan QR Voting'],
            ['checkin.success',         'Check-in'],
            ['election.created',        'Buat Pemilihan'],
            ['election.updated',        'Edit Pemilihan'],
            ['election.deleted',        'Hapus Pemilihan'],
            ['election.activated',      'Aktifkan Pemilihan'],
            ['election.closed',         'Tutup Pemilihan'],
            ['election.published',      'Publikasi Hasil'],
            ['candidate.created',       'Tambah Kandidat'],
            ['candidate.updated',       'Edit Kandidat'],
            ['candidate.deleted',       'Hapus Kandidat'],
            ['session.created',         'Buat Sesi'],
            ['session.updated',         'Edit Sesi'],
            ['session.deleted',         'Hapus Sesi'],
            ['session.activated',       'Aktifkan Sesi'],
            ['session.closed',          'Tutup Sesi'],
            ['session.assigned',        'Assign Pemilih'],
            ['class.created',           'Tambah Kelas'],
            ['class.updated',           'Edit Kelas'],
            ['class.deleted',           'Hapus Kelas'],
            ['class.toggled',           'Toggle Kelas'],
            ['operator.created',        'Tambah Operator'],
            ['operator.updated',        'Edit Operator'],
            ['operator.deleted',        'Hapus Operator'],
            ['operator.password_reset', 'Reset Password Operator'],
            ['profile.updated',         'Update Profile'],
            ['profile.password',        'Ubah Password'],
            ['vote.submitted',          'Vote'],
            ['device.checkin_deleted',  'Hapus Device Check-in'],
            ['device.voting_deleted',   'Hapus Device Voting'],
            ['device.purged',           'Purge Device Offline'],
            ['result.exported_pdf',     'Export Hasil (PDF)'],
            ['result.exported_excel',   'Export Hasil (Excel)'],
        ];
    }

    // ==========================================
    // WARNA
    // ==========================================

    #[DataProvider('actionColorProvider')]
    public function test_get_action_color_returns_correct_color(string $action, string $expectedColor)
    {
        $log = new ActivityLog(['action' => $action]);
        $this->assertEquals($expectedColor, $log->getActionColor());
    }

    public static function actionColorProvider(): array
    {
        return [
            ['voter.verified',      'success'],
            ['candidate.created',   'success'],
            ['election.updated',    'warning'],
            ['voter.rejected',      'danger'],
            ['candidate.deleted',   'danger'],
            ['auth.login',          'info'],
            ['auth.logout',         'secondary'],
            ['device.purged',       'warning'],
            ['result.exported_pdf', 'info'],
        ];
    }

    // ==========================================
    // ICON
    // ==========================================

    #[DataProvider('actionIconProvider')]
    public function test_get_action_icon_returns_correct_icon(string $action, string $expectedIcon)
    {
        $log = new ActivityLog(['action' => $action]);
        $this->assertEquals($expectedIcon, $log->getActionIcon());
    }

    public static function actionIconProvider(): array
    {
        return [
            ['auth.login',          'bi-box-arrow-in-right'],
            ['voter.register',      'bi-people'],
            ['election.created',    'bi-calendar-event'],
            ['candidate.created',   'bi-person-badge'],
            ['session.created',     'bi-clock-history'],
            ['class.created',       'bi-mortarboard'],
            ['operator.created',    'bi-person-badge-fill'],
            ['vote.submitted',      'bi-check2-square'],
            ['checkin.success',     'bi-door-open'],
            ['profile.updated',     'bi-person'],
            ['device.purged',       'bi-hdd-network'],
            ['result.exported_pdf', 'bi-download'],
        ];
    }

    // ==========================================
    // LOG HELPER
    // ==========================================

    public function test_log_creates_activity_with_correct_data()
    {
        $log = ActivityLog::log('test.action', [
            'subject_type' => 'App\Models\Election',
            'subject_id'   => 1,
            'meta'         => ['key' => 'value'],
        ]);

        $this->assertEquals('test.action', $log->action);
        $this->assertEquals('App\Models\Election', $log->subject_type);
        $this->assertEquals(1, $log->subject_id);
        $this->assertEquals(['key' => 'value'], $log->meta);
    }

    public function test_log_uses_auth_user_id_by_default()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $log = ActivityLog::log('test.action');

        $this->assertEquals($admin->id, $log->user_id);
    }

    public function test_log_uses_explicit_user_id_when_given()
    {
        $admin = $this->createAdmin();
        $otherUser = \App\Models\User::factory()->create();
        $this->actingAs($admin);

        $log = ActivityLog::log('test.action', [], userId: $otherUser->id);

        $this->assertEquals($otherUser->id, $log->user_id);
    }

    public function test_log_can_be_system_action_with_null_user_id()
    {
        $log = ActivityLog::log('vote.submitted', [
            'user_id' => null,
        ]);

        $this->assertNull($log->user_id);
    }

    // ==========================================
    // RELATIONS
    // ==========================================

    public function test_belongs_to_user()
    {
        $admin = $this->createAdmin();
        $log = ActivityLog::create([
            'user_id' => $admin->id,
            'action'  => 'test.action',
        ]);

        $this->assertInstanceOf(\App\Models\User::class, $log->user);
        $this->assertEquals($admin->id, $log->user->id);
    }

    public function test_user_relation_can_be_null_for_system_action()
    {
        $log = ActivityLog::create([
            'user_id' => null,
            'action'  => 'vote.submitted',
        ]);

        $this->assertNull($log->user);
    }

    // ==========================================
    // CASTS
    // ==========================================

    public function test_meta_is_cast_to_array()
    {
        $log = ActivityLog::create([
            'action' => 'test.action',
            'meta'   => ['key' => 'value', 'nested' => ['a' => 1]],
        ]);

        $log->refresh();
        $this->assertIsArray($log->meta);
        $this->assertEquals('value', $log->meta['key']);
        $this->assertEquals(1, $log->meta['nested']['a']);
    }

    public function test_created_at_is_cast_to_datetime()
    {
        $log = ActivityLog::create([
            'action' => 'test.action',
        ]);

        $log->refresh();
        $this->assertInstanceOf(\Carbon\Carbon::class, $log->created_at);
    }
}
