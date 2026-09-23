<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ClassRoom;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Response;

class VoterCardController extends Controller
{
    /**
     * Halaman form cetak kartu.
     */
    public function index(): View
    {
        $classes = ClassRoom::active()
            ->orderBy('tingkat')
            ->orderBy('name')
            ->get();

        // ✅ Count awal (sebelum filter)
        $counts = [
            'all'      => User::where('role', UserRole::VOTER)->count(),
            'verified' => User::where('role', UserRole::VOTER)->where('status', VoterStatus::VERIFIED)->count(),
        ];

        return view('admin.voter-cards.index', compact('classes', 'counts'));
    }

    /**
     * Preview — hitung voter + count per status (difilter kelas).
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'status'   => ['nullable', 'in:all,verified'],
        ]);

        // Count voter yang akan dicetak (sesuai filter)
        $count = $this->buildQuery($validated)->count();

        // ✅ Count per status (difilter kelas yang sama)
        $classId = $validated['class_id'];

        $counts = [
            'all'      => $this->baseQuery($classId)->count(),
            'verified' => $this->baseQuery($classId)->where('status', VoterStatus::VERIFIED)->count(),
        ];

        return response()->json([
            'count'  => $count,
            'counts' => $counts,
        ]);
    }

    /**
     * Generate PDF kartu voter.
     */
    public function download(Request $request): Response
    {
        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'status'   => ['nullable', 'in:all,verified'],
        ]);

        $class = ClassRoom::findOrFail($validated['class_id']);
        $voters = $this->buildQuery($validated)
            ->with('classRoom')
            ->orderBy('name')
            ->get();

        if ($voters->isEmpty()) {
            abort(404, 'Tidak ada voter di kelas ini.');
        }

        // ✅ Cek apakah imagick / gd tersedia untuk PNG
        $usePng = extension_loaded('imagick') || extension_loaded('gd');

        // Generate QR untuk setiap voter
        $cards = $voters->map(function (User $voter) use ($usePng) {
            $qrContent = $voter->qr_token ?? $voter->nis ?? '-';

            $qrGenerator = QrCode::size(300)->margin(1)->errorCorrection('M');

            if ($usePng) {
                $qrImage = base64_encode($qrGenerator->format('png')->generate($qrContent));
                $qrType  = 'png';
            } else {
                $qrImage = base64_encode($qrGenerator->format('svg')->generate($qrContent));
                $qrType  = 'svg';
            }

            return [
                'nama'    => $voter->name,
                'nis'     => $voter->nis ?? '-',
                'kelas'   => $voter->classRoom?->name ?? '-',
                'token'   => $qrContent,
                'qr'      => $qrImage,
                'qr_type' => $qrType,
            ];
        });

        $pdf = Pdf::loadView('admin.voter-cards.pdf', [
            'class'   => $class,
            'cards'   => $cards,
            'school'  => 'SMA Negeri 1 Kota Pasuruan',
            'use_png' => $usePng,
        ]);

        $pdf->setPaper('a4', 'portrait');

        $filename = 'kartu-voter-'
            . Str::slug($class->name)
            . '-'
            . now()->format('Ymd-His')
            . '.pdf';

        Log::info("Voter cards generated: {$voters->count()} cards for {$class->name} (format: " . ($usePng ? 'PNG' : 'SVG') . ")");

        ActivityLog::log('voter.cards_printed', [
            'meta' => [
                'class_id' => $class->id,
                'class'    => $class->name,
                'total'    => $voters->count(),
                'filename' => $filename,
                'format'   => $usePng ? 'png' : 'svg',
            ],
        ]);

        return $pdf->download($filename);
    }

    /**
     * Base query — hanya role voter, optional filter by class.
     */
    private function baseQuery(int $classId)
    {
        return User::where('role', UserRole::VOTER)
            ->where('class_id', $classId);
    }

    /**
     * Build query dari filter lengkap (class + status).
     */
    private function buildQuery(array $validated)
    {
        $query = $this->baseQuery($validated['class_id']);

        $status = $validated['status'] ?? 'verified';
        if ($status === 'verified') {
            $query->where('status', VoterStatus::VERIFIED);
        }

        return $query;
    }
}
