<?php

namespace App\Http\Controllers\Public;

use App\Enums\ElectionStatus;
use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionSession;
use Illuminate\View\View;

class ResultController extends Controller
{
    /**
     * Halaman list pemilihan yang sudah dipublikasi.
     */
    public function index(): View
    {
        $elections = Election::where('status', ElectionStatus::PUBLISHED)
            ->orderBy('hasil_published_at', 'desc')
            ->withCount(['candidates', 'votes'])
            ->get();

        return view('public.result.index', compact('elections'));
    }

    /**
     * Halaman detail hasil pemilihan.
     */
    public function show(Election $election): View
    {
        // ✅ Harus published
        abort_if(
            $election->status !== ElectionStatus::PUBLISHED,
            404,
            'Hasil pemilihan belum dipublikasikan.'
        );

        $election->load(['candidates.classRoom']);

        $stats      = $this->getStats($election);
        $candidates = $this->getCandidateResults($election);
        $sessions   = $this->getSessionResults($election);

        return view('public.result.show', compact(
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
        $golput      = max(0, $totalVoters - $totalVoted);

        return [
            'total_voters'      => $totalVoters,
            'total_voted'       => $totalVoted,
            'total_golput'      => $golput,
            'total_candidates'  => $election->candidates()->count(),
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

        $maxVotes = $candidates->max('votes_count') ?? 0;

        return $candidates
            ->map(function ($c) use ($totalVotes, $maxVotes) {
                $votes = $c->votes_count;

                return [
                    'id'         => $c->id,
                    'no_urut'    => $c->no_urut,
                    'nama'       => $c->nama,
                    'kelas'      => $c->classRoom?->name ?? '-',
                    'foto'       => $c->foto ? asset('storage/' . $c->foto) : null,
                    'visi'       => $c->visi,
                    'votes'      => $votes,
                    'percentage' => $totalVotes > 0
                        ? round(($votes / $totalVotes) * 100, 2)
                        : 0,
                    'is_winner'  => $votes > 0 && $votes === $maxVotes,
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
                'voters as voted_count' => fn($q) => $q->where('has_voted', true),
            ])
            ->orderBy('class_id')
            ->get()
            ->map(function ($s) {
                $total = $s->voters_count;
                $voted = $s->voted_count;

                return [
                    'kelas'         => $s->classRoom?->name ?? '-',
                    'total_voters'  => $total,
                    'voted'         => $voted,
                    'golput'        => max(0, $total - $voted),
                    'participation' => $total > 0
                        ? round(($voted / $total) * 100, 2)
                        : 0,
                ];
            })
            ->toArray();
    }
}
