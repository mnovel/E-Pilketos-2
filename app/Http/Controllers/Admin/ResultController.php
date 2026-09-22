<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ElectionStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\ElectionSession;
use App\Models\Vote;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ElectionResultExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResultController extends Controller
{
    /**
     * Halaman hasil pemilihan (admin).
     */
    public function show(Election $election): View
    {
        // Load relasi
        $election->load(['candidates.classRoom', 'creator']);

        // Statistik umum
        $stats = $this->getStats($election);

        // Perolehan suara per kandidat
        $candidates = $this->getCandidateResults($election);

        // Rekap partisipasi per kelas (sesi)
        $sessions = $this->getSessionResults($election);

        return view('admin.results.show', compact(
            'election',
            'stats',
            'candidates',
            'sessions'
        ));
    }

    // ==========================================
    // PRIVATE HELPERS
    // ==========================================

    private function getStats(Election $election): array
    {
        $totalVoters = $election->voters()->count();
        $totalVoted  = $election->votes()->count();
        $golput      = $totalVoters - $totalVoted;

        return [
            'total_voters'      => $totalVoters,
            'total_voted'       => $totalVoted,
            'total_golput'      => max(0, $golput),
            'total_candidates'  => $election->candidates()->count(),
            'total_sessions'    => $election->sessions()->count(),
            'participation_pct' => $totalVoters > 0
                ? round(($totalVoted / $totalVoters) * 100, 2)
                : 0,
        ];
    }

    private function getCandidateResults(Election $election): array
    {
        $totalVotes = $election->votes()->count();

        $candidates = $election->candidates()
            ->orderBy('no_urut')
            ->withCount('votes')
            ->get();

        // Cari suara tertinggi
        $maxVotes = $candidates->max('votes_count') ?? 0;

        return $candidates->map(function ($c) use ($totalVotes, $maxVotes) {
            $votes = $c->votes_count;

            return [
                'id'          => $c->id,
                'no_urut'     => $c->no_urut,
                'nama'        => $c->nama,
                'kelas'       => $c->classRoom?->name ?? '-',
                'foto'        => $c->foto ? asset('storage/' . $c->foto) : null,
                'visi'        => $c->visi,
                'votes'       => $votes,
                'percentage'  => $totalVotes > 0
                    ? round(($votes / $totalVotes) * 100, 2)
                    : 0,
                'is_winner'   => $votes > 0 && $votes === $maxVotes,
                'rank'        => 0,
            ];
        })
            ->sortByDesc('votes')
            ->values()
            ->map(function ($c, $i) {
                $c['rank'] = $i + 1;
                return $c;
            })
            ->toArray();
    }

    private function getSessionResults(Election $election): array
    {
        return ElectionSession::where('election_id', $election->id)
            ->with('classRoom')
            ->withCount([
                'voters',
                'voters as checked_in_count' => fn($q) => $q->where('checked_in', true),
                'voters as voted_count'      => fn($q) => $q->where('has_voted', true),
            ])
            ->orderBy('class_id')
            ->get()
            ->map(function ($s) {
                $total = $s->voters_count;
                $voted = $s->voted_count;

                return [
                    'id'              => $s->id,
                    'kelas'           => $s->classRoom?->name ?? '-',
                    'status'          => $s->getRuntimeStatus(),
                    'status_label'    => $s->getRuntimeLabel(),
                    'total_voters'    => $total,
                    'checked_in'      => $s->checked_in_count,
                    'voted'           => $voted,
                    'golput'          => max(0, $total - $voted),
                    'participation'   => $total > 0
                        ? round(($voted / $total) * 100, 2)
                        : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Export hasil ke PDF.
     */
    public function exportPdf(Election $election)
    {
        $election->load(['candidates.classRoom']);

        $stats      = $this->getStats($election);
        $candidates = $this->getCandidateResults($election);
        $sessions   = $this->getSessionResults($election);

        $pdf = Pdf::loadView('admin.results.pdf', compact(
            'election',
            'stats',
            'candidates',
            'sessions'
        ));

        $pdf->setPaper('a4', 'portrait');

        $filename = 'hasil-pilketos-' . \Str::slug($election->title) . '-' . now()->format('Ymd-His') . '.pdf';

        // ✅ Activity Log
        ActivityLog::log('result.exported_pdf', [
            'subject_type' => Election::class,
            'subject_id'   => $election->id,
            'meta'         => [
                'title'         => $election->title,
                'tahun_ajaran'  => $election->tahun_ajaran,
                'filename'      => $filename,
                'total_votes'   => $stats['total_voted'],
            ],
        ]);

        return $pdf->download($filename);
    }

    /**
     * Export hasil ke Excel.
     */
    public function exportExcel(Election $election): BinaryFileResponse
    {
        $filename = 'hasil-pilketos-' . \Str::slug($election->title) . '-' . now()->format('Ymd-His') . '.xlsx';

        // ✅ Capture stats sebelum export
        $stats = $this->getStats($election);

        // ✅ Activity Log
        ActivityLog::log('result.exported_excel', [
            'subject_type' => Election::class,
            'subject_id'   => $election->id,
            'meta'         => [
                'title'         => $election->title,
                'tahun_ajaran'  => $election->tahun_ajaran,
                'filename'      => $filename,
                'total_votes'   => $stats['total_voted'],
            ],
        ]);

        return Excel::download(new ElectionResultExport($election), $filename);
    }
}
