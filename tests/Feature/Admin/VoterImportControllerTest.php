<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Models\ActivityLog;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class VoterImportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
        Storage::fake('local');
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Simpan file CSV ke fake 'local' disk + set session.
     */
    protected function putImportFile(string $content, string $filename = 'test.csv'): string
    {
        $path = "temp-import/{$filename}";
        Storage::disk('local')->put($path, $content);
        session(['voter_import_file' => $path]);

        return $path;
    }

    /**
     * Bikin UploadedFile CSV.
     */
    protected function makeCsvUpload(string $content, string $filename = 'voters.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($filename, $content);
    }

    /**
     * Bikin UploadedFile XLSX asli pakai PhpSpreadsheet.
     */
    protected function makeXlsxUpload(array $rows, string $filename = 'voters.xlsx'): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($rows, null, 'A1');

        $tmpPath = tempnam(sys_get_temp_dir(), 'import_') . '.xlsx';

        (new Xlsx($spreadsheet))->save($tmpPath);

        return new UploadedFile(
            $tmpPath,
            $filename,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    /**
     * CSV default: 2 baris valid.
     */
    protected function validCsvContent(): string
    {
        return "nis,nama,kelas,email\n"
            . "901001,Ahmad Fauzi,X-IPA-1,ahmad@test.com\n"
            . "901002,Siti Nurhaliza,X-IPA-1,siti@test.com";
    }

    // =========================================================
    // INDEX — ACCESS
    // =========================================================

    public function test_admin_can_view_import_index()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.voters.import.index'));

        $response->assertOk();
        $response->assertViewIs('admin.voters.import.index');
    }

    public function test_guest_redirected_from_import_index()
    {
        $this->get(route('admin.voters.import.index'))
            ->assertRedirect(route('login'));
    }

    public function test_operator_cannot_access_import_index()
    {
        $this->actingAs($this->createOperator())
            ->get(route('admin.voters.import.index'))
            ->assertForbidden();
    }

    public function test_voter_cannot_access_import_index()
    {
        $this->actingAs($this->createVoter())
            ->get(route('admin.voters.import.index'))
            ->assertForbidden();
    }

    // =========================================================
    // DOWNLOAD TEMPLATE
    // =========================================================

    public function test_download_template_returns_csv()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.voters.import.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $contentDisposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('template-import-voter.csv', $contentDisposition);
    }

    public function test_download_template_contains_expected_headers()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.voters.import.template'));

        $content = $response->streamedContent();

        // Strip BOM
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        $this->assertStringContainsString('nis,nama,kelas,email', $content);
        $this->assertStringContainsString('12345678', $content);
    }

    // =========================================================
    // PREVIEW — VALIDATION & ACCESS
    // =========================================================

    public function test_preview_requires_file()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.import.preview'), []);

        $response->assertSessionHasErrors('file');
    }

    public function test_preview_rejects_invalid_mime()
    {
        $admin = $this->createAdmin();

        $file = UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf');

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.import.preview'), ['file' => $file]);

        $response->assertSessionHasErrors('file');
    }

    public function test_preview_requires_file_max_5mb()
    {
        $admin = $this->createAdmin();

        // 6 MB
        $file = UploadedFile::fake()->create('huge.csv', 6000, 'text/csv');

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.import.preview'), ['file' => $file]);

        $response->assertSessionHasErrors('file');
    }

    public function test_preview_redirects_when_file_empty()
    {
        $admin = $this->createAdmin();

        // Hanya header, tidak ada data
        $file = $this->makeCsvUpload("nis,nama,kelas,email\n");

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.import.preview'), ['file' => $file]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // =========================================================
    // PREVIEW — HAPPY PATH
    // =========================================================

    public function test_preview_shows_valid_csv()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $file = $this->makeCsvUpload($this->validCsvContent());

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.import.preview'), ['file' => $file]);

        $response->assertOk();
        $response->assertViewIs('admin.voters.import.preview');
        $response->assertViewHas('valid', 2);
        $response->assertViewHas('invalid', 0);
        $response->assertViewHas('preview');
    }

    public function test_preview_works_with_xlsx()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $file = $this->makeXlsxUpload([
            ['nis', 'nama', 'kelas', 'email'],
            ['901001', 'Ahmad Fauzi', 'X-IPA-1', 'ahmad@test.com'],
            ['901002', 'Siti Nurhaliza', 'X-IPA-1', 'siti@test.com'],
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.import.preview'), ['file' => $file]);

        $response->assertOk();
        $response->assertViewHas('valid', 2);
        $response->assertViewHas('invalid', 0);
    }

    public function test_preview_handles_bom_in_csv_header()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        // BOM + header normal
        $csv = "\xEF\xBB\xBFnis,nama,kelas,email\n"
            . "901001,Ahmad Fauzi,X-IPA-1,ahmad@test.com";

        $file = $this->makeCsvUpload($csv);

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.import.preview'), ['file' => $file]);

        $response->assertOk();
        $response->assertViewHas('valid', 1);
        $response->assertViewHas('invalid', 0);
    }

    // =========================================================
    // PREVIEW — DETEKSI ERROR
    // =========================================================

    public function test_preview_detects_missing_nis()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $csv = "nis,nama,kelas,email\n"
            . ",Ahmad Fauzi,X-IPA-1,ahmad@test.com";

        $file = $this->makeCsvUpload($csv);

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.import.preview'), ['file' => $file]);

        $response->assertViewHas('invalid', 1);
    }

    public function test_preview_detects_missing_nama()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $csv = "nis,nama,kelas,email\n"
            . "901001,,X-IPA-1,ahmad@test.com";

        $file = $this->makeCsvUpload($csv);

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.import.preview'), ['file' => $file]);

        $response->assertViewHas('invalid', 1);
    }

    public function test_preview_detects_missing_kelas()
    {
        $admin = $this->createAdmin();

        $csv = "nis,nama,kelas,email\n"
            . "901001,Ahmad,,ahmad@test.com";

        $file = $this->makeCsvUpload($csv);

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.import.preview'), ['file' => $file]);

        $response->assertViewHas('invalid', 1);
    }

    public function test_preview_detects_unknown_kelas()
    {
        $admin = $this->createAdmin();
        // Tidak ada kelas "X-UNKNOWN"

        $csv = "nis,nama,kelas,email\n"
            . "901001,Ahmad,X-UNKNOWN,ahmad@test.com";

        $file = $this->makeCsvUpload($csv);

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.import.preview'), ['file' => $file]);

        $response->assertViewHas('invalid', 1);
    }

    public function test_preview_detects_invalid_email()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $csv = "nis,nama,kelas,email\n"
            . "901001,Ahmad,X-IPA-1,bukan-email";

        $file = $this->makeCsvUpload($csv);

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.import.preview'), ['file' => $file]);

        $response->assertViewHas('invalid', 1);
    }

    public function test_preview_detects_duplicate_nis_in_db()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        // NIS 901001 sudah ada di DB
        User::factory()->create(['nis' => '901001']);

        $csv = "nis,nama,kelas,email\n"
            . "901001,Ahmad Fauzi,X-IPA-1,ahmad@test.com";

        $file = $this->makeCsvUpload($csv);

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.import.preview'), ['file' => $file]);

        $response->assertViewHas('invalid', 1);
        $response->assertViewHas('valid', 0);
    }

    public function test_preview_detects_duplicate_nis_in_file()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $csv = "nis,nama,kelas,email\n"
            . "901001,Ahmad Fauzi,X-IPA-1,ahmad@test.com\n"
            . "901001,Budi Santoso,X-IPA-1,budi@test.com";

        $file = $this->makeCsvUpload($csv);

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.import.preview'), ['file' => $file]);

        // Kedua baris di-flag duplicate
        $response->assertViewHas('invalid', 2);
        $response->assertViewHas('valid', 0);
    }

    public function test_preview_mixed_valid_and_invalid()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $csv = "nis,nama,kelas,email\n"
            . "901001,Ahmad Fauzi,X-IPA-1,ahmad@test.com\n"     // valid
            . ",Siti Nurhaliza,X-IPA-1,siti@test.com\n"          // invalid (NIS kosong)
            . "901003,Budi,X-IPA-1,budi@test.com";               // valid

        $file = $this->makeCsvUpload($csv);

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.import.preview'), ['file' => $file]);

        $response->assertViewHas('valid', 2);
        $response->assertViewHas('invalid', 1);
    }

    public function test_preview_stores_file_to_session()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $file = $this->makeCsvUpload($this->validCsvContent());

        $this->actingAs($admin)
            ->post(route('admin.voters.import.preview'), ['file' => $file]);

        $this->assertNotNull(session('voter_import_file'));
    }

    // =========================================================
    // IMPORT — ACCESS & SESSION GUARD
    // =========================================================

    public function test_import_redirects_without_session()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.import.import'));

        $response->assertRedirect(route('admin.voters.import.index'));
        $response->assertSessionHas('error');
    }

    public function test_import_redirects_when_file_missing()
    {
        $admin = $this->createAdmin();

        // Session ada tapi file tidak ada di disk
        session(['voter_import_file' => 'temp-import/nonexistent.csv']);

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.import.import'));

        $response->assertRedirect(route('admin.voters.import.index'));
        $response->assertSessionHas('error');
    }

    // =========================================================
    // IMPORT — HAPPY PATH
    // =========================================================

    public function test_import_creates_users()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $this->putImportFile($this->validCsvContent());

        $response = $this->actingAs($admin)
            ->post(route('admin.voters.import.import'));

        $response->assertRedirect(route('admin.voters.import.report'));

        $this->assertDatabaseHas('users', [
            'nis'  => '901001',
            'name' => 'Ahmad Fauzi',
        ]);
        $this->assertDatabaseHas('users', [
            'nis'  => '901002',
            'name' => 'Siti Nurhaliza',
        ]);
    }

    public function test_import_sets_default_password()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $this->putImportFile($this->validCsvContent());

        $this->actingAs($admin)->post(route('admin.voters.import.import'));

        $user = User::where('nis', '901001')->first();

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_import_uses_provided_email()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $this->putImportFile($this->validCsvContent());

        $this->actingAs($admin)->post(route('admin.voters.import.import'));

        $this->assertDatabaseHas('users', [
            'nis'   => '901001',
            'email' => 'ahmad@test.com',
        ]);
    }

    public function test_import_auto_generates_email_when_empty()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $csv = "nis,nama,kelas,email\n"
            . "901001,Ahmad Fauzi,X-IPA-1,";

        $this->putImportFile($csv);

        $this->actingAs($admin)->post(route('admin.voters.import.import'));

        $user = User::where('nis', '901001')->first();

        $this->assertNotNull($user);
        $this->assertNotEmpty($user->email);

        $this->assertMatchesRegularExpression(
            '/^siswa\.[a-z0-9]{3}@pilketos\.test$/',
            $user->email
        );
    }

    public function test_import_sets_role_and_status()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $this->putImportFile($this->validCsvContent());

        $this->actingAs($admin)->post(route('admin.voters.import.import'));

        $user = User::where('nis', '901001')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->isVoter());
        $this->assertSame(VoterStatus::PENDING, $user->status);
    }

    public function test_import_assigns_voter_spatie_role()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $this->putImportFile($this->validCsvContent());

        $this->actingAs($admin)->post(route('admin.voters.import.import'));

        $user = User::where('nis', '901001')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('voter'));
    }

    public function test_import_sets_class_id()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $this->putImportFile($this->validCsvContent());

        $this->actingAs($admin)->post(route('admin.voters.import.import'));

        $user = User::where('nis', '901001')->first();

        $this->assertNotNull($user);
        $this->assertSame($class->id, $user->class_id);
    }

    // =========================================================
    // IMPORT — SKIP INVALID
    // =========================================================

    public function test_import_skips_invalid_rows()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $csv = "nis,nama,kelas,email\n"
            . "901001,Ahmad Fauzi,X-IPA-1,ahmad@test.com\n"     // valid
            . ",Invalid,X-IPA-1,invalid@test.com\n"             // invalid (NIS kosong)
            . "901003,Budi,X-IPA-1,budi@test.com";              // valid

        $this->putImportFile($csv);

        $this->actingAs($admin)->post(route('admin.voters.import.import'));

        // Hanya 2 yang masuk
        $this->assertDatabaseHas('users', ['nis' => '901001']);
        $this->assertDatabaseHas('users', ['nis' => '901003']);
        $this->assertSame(2, User::where('role', UserRole::VOTER)->count());
    }

    public function test_import_reports_valid_and_failed_counts()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $csv = "nis,nama,kelas,email\n"
            . "901001,Ahmad Fauzi,X-IPA-1,ahmad@test.com\n"     // valid
            . ",Invalid,X-IPA-1,invalid@test.com\n"             // invalid
            . "901003,Budi,X-IPA-1,budi@test.com";              // valid

        $this->putImportFile($csv);

        $this->actingAs($admin)->post(route('admin.voters.import.import'));

        $result = session('import_result');

        $this->assertNotNull($result);
        $this->assertSame(2, $result['imported']);
        $this->assertSame(1, $result['failed']);
    }

    // =========================================================
    // IMPORT — SIDE EFFECT
    // =========================================================

    public function test_import_deletes_temp_file()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $path = $this->putImportFile($this->validCsvContent());

        Storage::disk('local')->assertExists($path);

        $this->actingAs($admin)->post(route('admin.voters.import.import'));

        Storage::disk('local')->assertMissing($path);
    }

    public function test_import_clears_session()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $this->putImportFile($this->validCsvContent());

        $this->actingAs($admin)->post(route('admin.voters.import.import'));

        $this->assertNull(session('voter_import_file'));
    }

    public function test_import_logs_activity()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $this->putImportFile($this->validCsvContent());

        $this->actingAs($admin)->post(route('admin.voters.import.import'));

        $log = ActivityLog::where('action', 'voter.imported')->first();

        $this->assertNotNull($log);
        $this->assertSame(2, $log->meta['imported']);
        $this->assertSame(0, $log->meta['failed']);
        $this->assertSame(2, $log->meta['total']);
    }

    public function test_import_logs_activity_with_failed_count()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $csv = "nis,nama,kelas,email\n"
            . "901001,Ahmad Fauzi,X-IPA-1,ahmad@test.com\n"
            . ",Invalid,X-IPA-1,invalid@test.com";

        $this->putImportFile($csv);

        $this->actingAs($admin)->post(route('admin.voters.import.import'));

        $log = ActivityLog::where('action', 'voter.imported')->first();

        $this->assertNotNull($log);
        $this->assertSame(1, $log->meta['imported']);
        $this->assertSame(1, $log->meta['failed']);
    }

    // =========================================================
    // REPORT
    // =========================================================

    public function test_report_shows_import_result()
    {
        $admin = $this->createAdmin();

        session([
            'import_result' => [
                'imported' => 5,
                'failed'   => 2,
                'errors'   => ['Baris 3: NIS kosong.'],
            ],
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.voters.import.report'));

        $response->assertOk();
        $response->assertViewIs('admin.voters.import.report');
        $response->assertViewHas('result');
    }

    public function test_report_redirects_when_no_session()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.voters.import.report'));

        $response->assertRedirect(route('admin.voters.import.index'));
    }
}
