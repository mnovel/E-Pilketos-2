<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VoterImportController extends Controller
{
    /**
     * Halaman upload.
     */
    public function index(): View
    {
        return view('admin.voters.import.index');
    }

    /**
     * Download template CSV.
     */
    public function downloadTemplate()
    {
        $filename = 'template-import-voter.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            // BOM UTF-8 untuk Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header
            fputcsv($file, ['nis', 'nama', 'kelas', 'email']);

            // Contoh
            fputcsv($file, ['12345678', 'Ahmad Fauzi', 'X-IPA-1', 'siswa-12345678@example.com']);
            fputcsv($file, ['12345679', 'Siti Nurhaliza', 'X-IPA-2', 'siswa-12345679@example.com']);
            fputcsv($file, ['12345680', 'Budi Santoso', 'X-IPS-1', 'siswa-12345680@example.com']);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Preview — parse & validate.
     */
    public function preview(Request $request): View|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
        ], [
            'file.required' => 'File wajib diupload.',
            'file.mimes'    => 'Format file harus CSV, XLSX, atau XLS.',
            'file.max'      => 'Ukuran file maksimal 5 MB.',
        ]);

        $file = $request->file('file');
        $rows = $this->parseFile($file);

        if (empty($rows)) {
            return back()->with('error', 'File kosong atau format salah. Pastikan header kolom benar.');
        }

        // Validasi tiap baris
        $preview = $this->validateRows($rows);

        // Simpan file sementara
        $tempPath = $file->store('temp-import', 'local');
        session(['voter_import_file' => $tempPath]);

        $valid   = collect($preview)->where('valid', true)->count();
        $invalid = collect($preview)->where('valid', false)->count();

        return view('admin.voters.import.preview', compact('preview', 'valid', 'invalid'));
    }

    /**
     * Eksekusi import.
     */
    public function import(Request $request): RedirectResponse
    {
        $tempPath = session('voter_import_file');

        if (!$tempPath || !Storage::disk('local')->exists($tempPath)) {
            return redirect()
                ->route('admin.voters.import.index')
                ->with('error', 'Sesi import kadaluarsa. Upload ulang file.');
        }

        $rows = $this->parseFileFromPath(Storage::disk('local')->path($tempPath));
        $preview = $this->validateRows($rows);

        $imported = 0;
        $failed   = 0;
        $errors   = [];

        foreach ($preview as $item) {
            if (!$item['valid']) {
                $failed++;
                continue;
            }

            try {
                $email = $item['data']['email'] ?: $this->generateEmail($item['data']['nis']);

                // Cek email duplikat
                if (User::where('email', $email)->exists()) {
                    $failed++;
                    $errors[] = "Baris {$item['row']}: email {$email} sudah terdaftar.";
                    continue;
                }

                $user = User::create([
                    'nis'      => $item['data']['nis'],
                    'name'     => $item['data']['nama'],
                    'class_id' => $item['data']['class_id'],
                    'email'    => $email,
                    'password' => Hash::make('password'),
                    'role'     => UserRole::VOTER,
                    'status'   => VoterStatus::PENDING,
                ]);

                try {
                    if (!$user->hasRole('voter')) {
                        $user->assignRole('voter');
                    }
                } catch (\Throwable $e) {
                    // skip
                }

                $imported++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "Baris {$item['row']}: " . $e->getMessage();
                Log::error('Import voter error: ' . $e->getMessage());
            }
        }

        // Hapus file temp
        Storage::disk('local')->delete($tempPath);
        session()->forget('voter_import_file');

        Log::info("Voter import: {$imported} imported, {$failed} failed by " . auth()->user()->name);

        return redirect()
            ->route('admin.voters.import.report')
            ->with([
                'import_result' => [
                    'imported' => $imported,
                    'failed'   => $failed,
                    'errors'   => $errors,
                ],
            ]);
    }

    /**
     * Halaman report.
     */
    public function report(): View|RedirectResponse
    {
        $result = session('import_result');

        if (!$result) {
            return redirect()->route('admin.voters.import.index');
        }

        return view('admin.voters.import.report', compact('result'));
    }

    // ==========================================
    // PRIVATE HELPERS
    // ==========================================

    /**
     * Parse file upload (CSV/Excel).
     */
    private function parseFile($file): array
    {
        return $this->parseFileFromPath(
            $file->getRealPath(),
            $file->getClientOriginalExtension()
        );
    }

    /**
     * Parse file dari path.
     */
    private function parseFileFromPath(string $path, ?string $ext = null): array
    {
        $ext = $ext ?: strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($ext, ['csv', 'txt'])) {
            return $this->parseCsv($path);
        }

        if (in_array($ext, ['xlsx', 'xls'])) {
            return $this->parseExcel($path);
        }

        return [];
    }

    /**
     * Parse CSV.
     */
    private function parseCsv(string $path): array
    {
        $rows = [];
        $headers = [];

        if (($handle = fopen($path, 'r')) === false) {
            return [];
        }

        $firstRow = true;

        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            // Skip baris kosong
            if (empty(array_filter($data))) {
                continue;
            }

            if ($firstRow) {
                // ✅ Hapus BOM UTF-8 dari kolom pertama header
                if (isset($data[0])) {
                    $data[0] = $this->stripBom((string) $data[0]);
                }

                $headers = array_map(fn($h) => strtolower(trim((string) $h)), $data);
                $firstRow = false;
                continue;
            }

            if (count($data) === count($headers)) {
                $rows[] = array_combine($headers, $data);
            }
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Parse Excel (via Maatwebsite).
     */
    private function parseExcel(string $path): array
    {
        try {
            $sheets = \Maatwebsite\Excel\Facades\Excel::toArray([], $path);
            $data = $sheets[0] ?? [];

            if (empty($data)) {
                return [];
            }

            // ✅ Strip BOM dari header (jaga-jaga)
            $headers = array_map(function ($h) {
                return strtolower(trim($this->stripBom((string) $h)));
            }, $data[0]);

            $rows = [];

            for ($i = 1; $i < count($data); $i++) {
                $row = $data[$i];

                if (empty(array_filter($row))) {
                    continue;
                }

                if (count($row) === count($headers)) {
                    $rows[] = array_combine($headers, $row);
                }
            }

            return $rows;
        } catch (\Throwable $e) {
            Log::error('Excel parse error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Validasi tiap baris.
     */
    private function validateRows(array $rows): array
    {
        // Cache kelas untuk lookup cepat
        $classes = ClassRoom::pluck('id', 'name')->toArray();

        $preview = [];

        // NIS duplikat dalam file
        $nisInFile = collect($rows)->pluck('nis')->map(fn($n) => trim((string) $n))->toArray();
        $nisDuplicate = array_diff_assoc($nisInFile, array_unique($nisInFile));

        foreach ($rows as $i => $row) {
            $rowNumber = $i + 2;  // +2 karena header + index mulai 0
            $errors = [];

            $nis   = trim((string) ($row['nis'] ?? ''));
            $nama  = trim((string) ($row['nama'] ?? ''));
            $kelas = trim((string) ($row['kelas'] ?? ''));
            $email = trim((string) ($row['email'] ?? ''));

            // Validasi NIS
            if (empty($nis)) {
                $errors[] = 'NIS kosong.';
            } elseif (User::where('nis', $nis)->exists()) {
                $errors[] = "NIS {$nis} sudah terdaftar.";
            } elseif (in_array($nis, $nisDuplicate)) {
                $errors[] = "NIS {$nis} duplikat di file.";
            }

            // Validasi nama
            if (empty($nama)) {
                $errors[] = 'Nama kosong.';
            }

            // Validasi kelas
            $classId = null;
            if (empty($kelas)) {
                $errors[] = 'Kelas kosong.';
            } elseif (!isset($classes[$kelas])) {
                $errors[] = "Kelas \"{$kelas}\" tidak ditemukan.";
            } else {
                $classId = $classes[$kelas];
            }

            // Validasi email (kalau diisi)
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Email \"{$email}\" tidak valid.";
            }

            $preview[] = [
                'row'   => $rowNumber,
                'data'  => [
                    'nis'      => $nis,
                    'nama'     => $nama,
                    'kelas'    => $kelas,
                    'class_id' => $classId,
                    'email'    => $email,
                ],
                'valid'  => empty($errors),
                'errors' => $errors,
            ];
        }

        return $preview;
    }

    /**
     * Generate email otomatis dari NIS.
     */
    private function generateEmail(string $nis): string
    {
        do {
            $random = strtolower(Str::random(4));
            $email = "siswa-{$nis}-{$random}@pilketos.test";
        } while (User::where('email', $email)->exists());

        return $email;
    }

    /**
     * Hapus BOM UTF-8 (Byte Order Mark) dari string.
     *
     * BOM sering ditambahkan Excel/Google Sheets di awal file CSV/Excel
     * sehingga header pertama bisa jadi "\xEF\xBB\xBFnis" bukan "nis".
     */
    private function stripBom(string $value): string
    {
        // BOM UTF-8: EF BB BF
        if (str_starts_with($value, "\xEF\xBB\xBF")) {
            $value = substr($value, 3);
        }

        // BOM UTF-16 BE: FE FF (jarang, tapi jaga-jaga)
        if (str_starts_with($value, "\xFE\xFF")) {
            $value = substr($value, 2);
        }

        // BOM UTF-16 LE: FF FE
        if (str_starts_with($value, "\xFF\xFE")) {
            $value = substr($value, 2);
        }

        return $value;
    }
}
