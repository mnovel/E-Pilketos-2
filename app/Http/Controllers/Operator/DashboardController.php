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

            return [
                'activeSessions' => $activeSessions,
                'todaySessions'  => $todaySessions,
                'stats'          => $stats,
            ];
        });

        return view('operator.dashboard', [
            'activeSessions' => $data['activeSessions'],
            'todaySessions'  => $data['todaySessions'],
            'stats'          => $data['stats'],
        ]);
    }
}
