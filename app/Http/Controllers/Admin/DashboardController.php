<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ElectionStatus;
use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Http\Controllers\Controller;
use App\Models\CheckinDevice;
use App\Models\Election;
use App\Models\User;
use App\Models\VotingDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

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

        return view('admin.dashboard', compact('stats'));
    }

    /**
     * Endpoint JSON untuk statistik live (dipanggil via AJAX).
     */
    public function liveStats(): JsonResponse
    {
        // ✅ Cache 5 detik — endpoint ini dipolling tiap 10 detik
        // Kalau 3 admin buka dashboard, tetap 1 query per 5 detik
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
                        : 0,
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
