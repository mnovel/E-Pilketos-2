<?php

namespace App\Http\Controllers\Operator;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Models\ElectionSession;
use App\Models\Voter;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $data = Cache::remember('operator.dashboard', 30, function () {
            $activeSessions = ElectionSession::with(['election', 'classRoom'])
                ->where('status', SessionStatus::ACTIVE)
                ->get();

            $todaySessions = ElectionSession::with(['election', 'classRoom', 'operator'])
                ->whereDate('tanggal', today())
                ->orderBy('waktu_mulai')
                ->get();

            $stats = [
                'total_sessions_today' => $todaySessions->count(),
                'total_active'         => $activeSessions->count(),
                'total_checkin_today'  => Voter::whereHas('session', function ($q) {
                    $q->whereDate('tanggal', today());
                })->where('checked_in', true)->count(),
                'total_voted_today'    => Voter::whereHas('session', function ($q) {
                    $q->whereDate('tanggal', today());
                })->where('has_voted', true)->count(),
            ];

            $chartData = $this->buildChartData($activeSessions);

            return [
                'activeSessions' => $activeSessions,
                'todaySessions'  => $todaySessions,
                'stats'          => $stats,
                'chartData'      => $chartData,
            ];
        });

        return view('operator.dashboard', [
            'activeSessions' => $data['activeSessions'],
            'todaySessions'  => $data['todaySessions'],
            'stats'          => $data['stats'],
            'chartData'      => $data['chartData'],
        ]);
    }

    /**
     * Build data untuk chart.
     */
    private function buildChartData($activeSessions): array
    {
        // ==========================================
        // CHART 1: Partisipasi per Sesi Aktif
        // ==========================================
        $labels = [];
        $votedPct = [];
        $votedCount = [];
        $checkedInPct = [];
        $checkedInCount = [];
        $totalVoters = [];

        foreach ($activeSessions as $session) {
            $total = $session->voters()->count();
            $voted = $session->voters()->where('has_voted', true)->count();
            $checkedIn = $session->voters()->where('checked_in', true)->count();

            $labels[]       = $session->classRoom?->name ?? '-';
            $votedPct[]     = $total > 0 ? round(($voted / $total) * 100, 1) : 0.0;
            $votedCount[]   = $voted;
            $checkedInPct[] = $total > 0 ? round(($checkedIn / $total) * 100, 1) : 0.0;
            $checkedInCount[] = $checkedIn;
            $totalVoters[]  = $total;
        }

        $partisipasiSesi = [
            'labels'           => $labels,
            'voted_pct'        => $votedPct,
            'voted_count'      => $votedCount,
            'checked_in_pct'   => $checkedInPct,
            'checked_in_count' => $checkedInCount,
            'total_voters'     => $totalVoters,
        ];

        // ==========================================
        // CHART 2: Total Partisipasi Hari Ini (Gauge)
        // ==========================================
        $totalVotersToday = Voter::whereHas('session', function ($q) {
            $q->whereDate('tanggal', today());
        })->count();

        $totalVotedToday = Voter::whereHas('session', function ($q) {
            $q->whereDate('tanggal', today());
        })->where('has_voted', true)->count();

        // ✅ Float consistency
        $partisipasiPct = $totalVotersToday > 0
            ? round(($totalVotedToday / $totalVotersToday) * 100, 1)
            : 0.0;

        $gaugePartisipasi = [
            'percentage'   => $partisipasiPct,
            'total_voters' => $totalVotersToday,
            'total_voted'  => $totalVotedToday,
            'total_golput' => max(0, $totalVotersToday - $totalVotedToday),
        ];

        return [
            'partisipasi_sesi'   => $partisipasiSesi,
            'gauge_partisipasi'  => $gaugePartisipasi,
            'has_active_session' => $activeSessions->count() > 0,
        ];
    }
}
