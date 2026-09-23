<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Exports\VoterCredentialsExport;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VoterExportController extends Controller
{
    /**
     * Halaman form export.
     */
    public function index(): View
    {
        $classes = ClassRoom::active()
            ->orderBy('tingkat')
            ->orderBy('name')
            ->get();

        // ✅ Count awal (sebelum filter) — hitung semua voter
        $counts = [
            'all'      => User::where('role', UserRole::VOTER)->count(),
            'pending'  => User::where('role', UserRole::VOTER)->where('status', VoterStatus::PENDING)->count(),
            'verified' => User::where('role', UserRole::VOTER)->where('status', VoterStatus::VERIFIED)->count(),
            'rejected' => User::where('role', UserRole::VOTER)->where('status', VoterStatus::REJECTED)->count(),
        ];

        return view('admin.voters.export.index', compact('classes', 'counts'));
    }

    /**
     * Preview — hitung voter yang akan di-export + count per status.
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'class_id'       => ['nullable', 'exists:classes,id'],
            'status'         => ['nullable', 'in:all,pending,verified,rejected'],
            'reset_password' => ['nullable', 'boolean'],
        ]);

        // Count voter yang akan di-export (sesuai filter)
        $count = $this->buildQuery($validated)->count();

        // ✅ Count per status (difilter dengan class yang sama)
        $classId = $validated['class_id'] ?? null;

        $counts = [
            'all'      => $this->baseQuery($classId)->count(),
            'pending'  => $this->baseQuery($classId)->where('status', VoterStatus::PENDING)->count(),
            'verified' => $this->baseQuery($classId)->where('status', VoterStatus::VERIFIED)->count(),
            'rejected' => $this->baseQuery($classId)->where('status', VoterStatus::REJECTED)->count(),
        ];

        return response()->json([
            'count'  => $count,
            'reset'  => !empty($validated['reset_password']),
            'counts' => $counts,
        ]);
    }

    /**
     * Eksekusi export.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'class_id'       => ['nullable', 'exists:classes,id'],
            'status'         => ['nullable', 'in:all,pending,verified,rejected'],
            'reset_password' => ['nullable', 'boolean'],
        ]);

        $voters = $this->buildQuery($validated)->get();

        if ($voters->isEmpty()) {
            abort(404, 'Tidak ada voter yang cocok dengan filter.');
        }

        $plainPasswords = [];

        // Reset password massal (kalau dipilih)
        if (!empty($validated['reset_password'])) {
            foreach ($voters as $voter) {
                $newPassword = Str::password(8, symbols: false);
                $plainPasswords[$voter->id] = $newPassword;

                $voter->update([
                    'password' => Hash::make($newPassword),
                ]);
            }
        }

        // Nama file
        $filename = 'credentials-voter-' . now()->format('Ymd-His') . '.xlsx';

        // Metadata untuk log
        $meta = [
            'total'          => $voters->count(),
            'class_id'       => $validated['class_id'] ?? null,
            'status'         => $validated['status'] ?? 'all',
            'password_reset' => !empty($validated['reset_password']),
            'filename'       => $filename,
        ];

        Log::info("Voter credentials exported: {$voters->count()} voters", $meta);

        ActivityLog::log('voter.credentials_exported', [
            'meta' => $meta,
        ]);

        return Excel::download(
            new VoterCredentialsExport($voters->pluck('id')->toArray(), $plainPasswords),
            $filename
        );
    }

    /**
     * Base query — hanya role voter, optional filter by class.
     */
    private function baseQuery(?int $classId = null)
    {
        $query = User::where('role', UserRole::VOTER);

        if ($classId) {
            $query->where('class_id', $classId);
        }

        return $query;
    }

    /**
     * Build query dari filter lengkap (class + status).
     */
    private function buildQuery(array $validated)
    {
        $query = $this->baseQuery($validated['class_id'] ?? null);

        $status = $validated['status'] ?? 'all';
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return $query;
    }
}
