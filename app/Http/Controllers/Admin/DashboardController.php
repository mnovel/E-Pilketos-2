<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ElectionStatus;
use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Http\Controllers\Controller;
use App\Models\CheckinDevice;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\User;
use App\Models\Vote;
use App\Models\VotingDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ✅ Cache 30 detik — stats voter jarang berubah signifikan
        $stats = Cache::remember('admin.dashboard.stats', 30, function () {
            return [
                'total_voters'    => User::where('role', UserRole::VOTER)->count(),
                'pending_voters'  => User::where('role', UserRole::VOTER)
                    ->where('status', VoterStatus::PENDING)->count(),
                'verified_voters' => User::where('role', UserRole::VOTER)
                    ->where('status', VoterStatus::VERIFIED)->count(),
                'rejected_voters' => User::where('role', UserRole::VOTER)
                    ->where('status', VoterStatus::REJECTED)->count(),
            ];
        });

        // ✅ Chart data — cache 60 detik
        $chartData = Cache::remember('admin.dashboard.charts', 60, function () {
            return $this->buildChartData();
        });

        return view('admin.dashboard', compact('stats', 'chartData'));
    }

    /**
     * Build data untuk semua chart.
     */
    private function buildChartData(): array
    {
        $activeElection = Election::where('status', ElectionStatus::ACTIVE)
            ->where('start_at', '<=', now())
            ->where('end_at', '>', now())
            ->first();

        // ==========================================
        // CHART 1: Partisipasi per Kelas (Bar)
        // ==========================================
        $partisipasiPerKelas = $this->getPartisipasiPerKelas($activeElection);

        // ==========================================
        // CHART 2: Status Pemilih (Donut)
        // ==========================================
        $statusPemilih = [
            'verified' => User::where('role', UserRole::VOTER)->where('status', VoterStatus::VERIFIED)->count(),
            'pending'  => User::where('role', UserRole::VOTER)->where('status', VoterStatus::PENDING)->count(),
            'rejected' => User::where('role', UserRole::VOTER)->where('status', VoterStatus::REJECTED)->count(),
        ];

        // ==========================================
        // CHART 3: Trend Voting Per Jam (Line)
        // ==========================================
        $trendVoting = $this->getTrendVotingPerJam($activeElection);

        return [
            'has_active_election'   => $activeElection !== null,
            'election_title'        => $activeElection?->title,
            'partisipasi_per_kelas' => $partisipasiPerKelas,
            'status_pemilih'        => $statusPemilih,
            'trend_voting'          => $trendVoting,
        ];
    }

    /**
     * Partisipasi per kelas untuk election aktif.
     */
    private function getPartisipasiPerKelas(?Election $election): array
    {
        $empty = [
            'labels'       => [],
            'voted_pct'    => [],
            'voted_count'  => [],
            'total_voters' => [],
        ];

        if (!$election) {
            return $empty;
        }

        // ✅ Cek apakah ada voter di election ini
        $hasVoters = DB::table('voters')
            ->where('election_id', $election->id)
            ->exists();

        if (!$hasVoters) {
            return $empty;
        }

        // ✅ Query aggregation per kelas
        $stats = DB::table('voters')
            ->join('classes', 'voters.class_id', '=', 'classes.id')
            ->where('voters.election_id', $election->id)
            ->select(
                'classes.name as class_name',
                DB::raw('COUNT(voters.id) as total'),
                DB::raw('SUM(CASE WHEN voters.has_voted = 1 THEN 1 ELSE 0 END) as voted')
            )
            ->groupBy('classes.id', 'classes.name')
            ->orderBy('classes.name')
            ->get();

        $labels = [];
        $votedPct = [];
        $votedCount = [];
        $totalVoters = [];

        foreach ($stats as $row) {
            $total = (int) $row->total;
            $voted = (int) $row->voted;
            $pct = $total > 0 ? round(($voted / $total) * 100, 1) : 0.0;

            $labels[]      = $row->class_name;
            $votedPct[]    = $pct;
            $votedCount[]  = $voted;
            $totalVoters[] = $total;
        }

        return [
            'labels'       => $labels,
            'voted_pct'    => $votedPct,
            'voted_count'  => $votedCount,
            'total_voters' => $totalVoters,
        ];
    }

    /**
     * Trend voting per jam.
     */
    private function getTrendVotingPerJam(?Election $election): array
    {
        $emptyTrend = [
            'labels' => [],
            'counts' => [],
        ];

        if (!$election) {
            return $emptyTrend;
        }

        // ✅ Deteksi driver — HOUR() hanya ada di MySQL, SQLite pakai strftime
        $driver = DB::connection()->getDriverName();

        $hourExpr = $driver === 'sqlite'
            ? "strftime('%H', created_at)"
            : 'HOUR(created_at)';

        // ✅ Cek ada vote sama sekali
        $hasVotes = Vote::where('election_id', $election->id)->exists();

        if (!$hasVotes) {
            return $emptyTrend;
        }

        // ✅ Query per jam
        $votes = Vote::where('election_id', $election->id)
            ->select(
                DB::raw("$hourExpr as hour_alias"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(DB::raw($hourExpr))
            ->orderBy(DB::raw($hourExpr))
            ->pluck('total', 'hour_alias')
            ->toArray();

        if (empty($votes)) {
            return $emptyTrend;
        }

        // ✅ NORMALIZE KEYS ke integer
        // SQLite return "08" (string), MySQL return 8 (int).
        // PHP array key: "10" → int 10, tapi "08" & "09" tetap string (leading zero).
        // Jadi kita paksa semua key jadi int biar lookup konsisten.
        $normalizedVotes = [];
        foreach ($votes as $hour => $count) {
            $normalizedVotes[(int) $hour] = (int) $count;
        }
        $votes = $normalizedVotes;

        // Range jam (min sampai max)
        $minHour = min(array_keys($votes));
        $maxHour = max(array_keys($votes));

        $labels = [];
        $counts = [];

        for ($h = $minHour; $h <= $maxHour; $h++) {
            $labels[] = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
            $counts[] = (int) ($votes[$h] ?? 0);
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
        ];
    }

    /**
     * Endpoint JSON untuk statistik live (dipanggil via AJAX).
     */
    public function liveStats(): JsonResponse
    {
        // ✅ Cache 5 detik — endpoint ini dipolling tiap 10 detik
        $data = Cache::remember('admin.live_stats', 5, function () {
            $onlineThreshold = now()->subMinutes(2);

            $activeElection = Election::where('status', ElectionStatus::ACTIVE)
                ->where('start_at', '<=', now())
                ->where('end_at', '>', now())
                ->first();

            $activeElectionData = null;

            if ($activeElection) {
                $totalVoters = $activeElection->voters()->count();
                $totalVoted  = $activeElection->votes()->count();

                $activeElectionData = [
                    'id'                => $activeElection->id,
                    'title'             => $activeElection->title,
                    'tahun_ajaran'      => $activeElection->tahun_ajaran,
                    'total_voters'      => $totalVoters,
                    'checked_in'        => $activeElection->voters()->where('checked_in', true)->count(),
                    'has_voted'         => $totalVoted,
                    'active_sessions'   => $activeElection->sessions()
                        ->where('status', SessionStatus::ACTIVE)
                        ->count(),
                    'checkin_online'    => CheckinDevice::where('election_id', $activeElection->id)
                        ->where('last_ping_at', '>=', $onlineThreshold)
                        ->count(),
                    'checkin_total'     => CheckinDevice::where('election_id', $activeElection->id)->count(),
                    'voting_online'     => VotingDevice::where('election_id', $activeElection->id)
                        ->where('last_ping_at', '>=', $onlineThreshold)
                        ->count(),
                    'voting_total'      => VotingDevice::where('election_id', $activeElection->id)->count(),
                    'participation_pct' => $totalVoters > 0
                        ? round(($totalVoted / $totalVoters) * 100, 2)
                        : 0.0,
                ];
            }

            return [
                'active_election' => $activeElectionData,
                'updated_at'      => now()->format('H:i:s'),
            ];
        });

        return response()->json($data);
    }
}
