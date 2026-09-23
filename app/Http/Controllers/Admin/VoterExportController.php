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

        $counts = [
            'all'      => User::where('role', UserRole::VOTER)->count(),
            'pending'  => User::where('role', UserRole::VOTER)->where('status', VoterStatus::PENDING)->count(),
            'verified' => User::where('role', UserRole::VOTER)->where('status', VoterStatus::VERIFIED)->count(),
            'rejected' => User::where('role', UserRole::VOTER)->where('status', VoterStatus::REJECTED)->count(),
        ];

        return view('admin.voters.export.index', compact('classes', 'counts'));
    }

    /**
     * Preview — lihat berapa voter yang akan di-export.
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'class_id'       => ['nullable', 'exists:classes,id'],
            'status'         => ['nullable', 'in:all,pending,verified,rejected'],
            'reset_password' => ['nullable', 'boolean'],
        ]);

        $query = $this->buildQuery($validated);
        $count = $query->count();

        return response()->json([
            'count' => $count,
            'reset' => !empty($validated['reset_password']),
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

        // ✅ Reset password massal (kalau dipilih)
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
     * Build query dari filter.
     */
    private function buildQuery(array $validated)
    {
        $query = User::where('role', UserRole::VOTER);

        if (!empty($validated['class_id'])) {
            $query->where('class_id', $validated['class_id']);
        }

        $status = $validated['status'] ?? 'all';
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return $query;
    }
}
