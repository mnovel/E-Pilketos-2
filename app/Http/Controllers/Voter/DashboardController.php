<?php

namespace App\Http\Controllers\Voter;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Models\ElectionSession;
use App\Models\Voter;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $voter = Voter::with(['election', 'session.classRoom', 'classRoom'])
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        $sessions = ElectionSession::with(['election', 'classRoom'])
            ->where('class_id', $user->class_id)
            ->whereIn('status', [SessionStatus::SCHEDULED, SessionStatus::ACTIVE])
            ->orderBy('tanggal')
            ->orderBy('waktu_mulai')
            ->get();

        // Cek status verifikasi
        $isVerified = $user->isVerified();

        return view('voter.dashboard', compact('user', 'voter', 'sessions', 'isVerified'));
    }
}
