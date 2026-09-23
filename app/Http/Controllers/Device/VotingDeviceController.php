<?php

namespace App\Http\Controllers\Device;

use App\Enums\DeviceStatus;
use App\Enums\ElectionStatus;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Vote;
use App\Models\Voter;
use App\Models\VotingDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VotingDeviceController extends Controller
{
    /**
     * Entry point — langsung ke kiosk.
     */
    public function index(): View
    {
        if (session('voting_manually_closed')) {
            return view('device.voting.closed');
        }

        return $this->kiosk();
    }

    /**
     * Layar kiosk — auto-detect election aktif.
     */
    public function kiosk(): View
    {
        $election = $this->detectActiveElection();
        $device   = null;

        if ($election && $election->autoCloseIfEnded()) {
            $election = null;
        }

        if ($election) {
            $device = $this->getOrCreateDevice($election);

            session([
                'voting_device_id'   => $device->id,
                'voting_election_id' => $election->id,
            ]);

            Log::info("Voting kiosk opened for election #{$election->id} (device #{$device->id})");
        } else {
            session()->forget(['voting_device_id', 'voting_election_id']);
        }

        return view('device.voting.kiosk', compact('election', 'device'));
    }

    /**
     * Polling status.
     */
    public function status(): JsonResponse
    {
        $deviceId   = session('voting_device_id');
        $electionId = session('voting_election_id');

        // ==============================
        // BELUM ADA DEVICE — CEK ELECTION
        // ==============================
        if (!$deviceId || !$electionId) {
            $active = $this->detectActiveElection();

            return response()->json([
                'status' => $active ? 'new_election' : 'waiting',
                'action' => $active ? 'reload' : 'none',
            ]);
        }

        // ==============================
        // LOAD DEVICE + ELECTION
        // ==============================
        $device = VotingDevice::with('election')->find($deviceId);

        if (!$device) {
            session()->forget(['voting_device_id', 'voting_election_id']);

            return response()->json([
                'status' => 'device_lost',
                'action' => 'reload',
            ]);
        }

        $election = $device->election;

        if (!$election) {
            session()->forget(['voting_device_id', 'voting_election_id']);

            return response()->json([
                'status' => 'election_lost',
                'action' => 'reload',
            ]);
        }

        // ==============================
        // CEK ELECTION MASIH VALID
        // ==============================
        if ($election->autoCloseIfEnded()) {
            session()->forget(['voting_device_id', 'voting_election_id']);

            return response()->json([
                'status'  => 'election_ended',
                'message' => 'Waktu pemilihan sudah berakhir.',
                'action'  => 'reload',
            ]);
        }

        if (!$election->isActive() || !$election->isWithinTimeWindow()) {
            session()->forget(['voting_device_id', 'voting_election_id']);

            return response()->json([
                'status'  => 'election_not_active',
                'message' => 'Pemilihan tidak aktif.',
                'action'  => 'reload',
            ]);
        }

        // ==============================
        // PING + ROTATE TOKEN
        // ==============================
        $device->ping();

        if ($device->isIdle() && !$device->isTokenValid()) {
            $device->rotateToken(30);
        }

        // ==============================
        // JIKA DEVICE DI-ASSIGN KE VOTER
        // ==============================
        if ($device->isAssigned() && $device->assignedVoter) {
            $voter = $device->assignedVoter->load('user', 'classRoom', 'session');

            $candidates = Candidate::where('election_id', $election->id)
                ->orderBy('no_urut')
                ->get()
                ->map(fn($c) => [
                    'id'      => $c->id,
                    'no_urut' => $c->no_urut,
                    'nama'    => $c->nama,
                    'kelas'   => $c->classRoom?->name ?? '-',
                    'foto'    => $c->foto ? asset('storage/' . $c->foto) : null,
                    'visi'    => $c->visi,
                    'misi'    => $c->misi,
                ]);

            return response()->json([
                'status'       => 'assigned',
                'token'        => $device->device_token,
                'voter'        => [
                    'nama'  => $voter->user?->name,
                    'nis'   => $voter->user?->nis,
                    'kelas' => $voter->classRoom?->name,
                ],
                'candidates'   => $candidates,
                'total_voted'  => $election->voters()->where('has_voted', true)->count(),
                'total_voters' => $election->voters()->count(),
            ]);
        }

        // ==============================
        // IDLE
        // ==============================
        return response()->json([
            'status'       => 'idle',
            'token'        => $device->device_token,
            'expires_in'   => max(0, now()->diffInSeconds($device->token_expired_at, false)),
            'total_voted'  => $election->voters()->where('has_voted', true)->count(),
            'total_voters' => $election->voters()->count(),
        ]);
    }

    /**
     * Submit vote.
     */
    public function submit(Request $request): JsonResponse
    {
        $request->validate([
            'device_id'    => ['required', 'exists:voting_devices,id'],
            'candidate_id' => ['required', 'exists:candidates,id'],
        ]);

        try {
            DB::transaction(function () use ($request) {
                $device = VotingDevice::where('id', $request->device_id)
                    ->where('status', DeviceStatus::ASSIGNED)
                    ->lockForUpdate()
                    ->firstOrFail();

                $voter = Voter::lockForUpdate()->findOrFail($device->assigned_voter_id);

                if ($voter->has_voted) {
                    throw new \Exception('Anda sudah memilih.');
                }

                if (!$voter->checked_in) {
                    throw new \Exception('Anda belum check-in.');
                }

                // ==========================================
                // VALIDASI SESSION VOTER MASIH AKTIF
                // ==========================================
                $session = $voter->session;

                if (!$session || !$session->isActive() || $session->hasEnded()) {
                    throw new \Exception('Sesi kelas Anda sudah berakhir.');
                }

                // ==========================================
                // ✅ VALIDASI KELAS VOTER = KELAS SESI
                // ==========================================
                if ($voter->class_id !== $session->class_id) {
                    throw new \Exception('Kelas Anda tidak sesuai dengan sesi ini. Hubungi panitia.');
                }

                // ==========================================
                // ✅ VALIDASI ELECTION MASIH AKTIF & DALAM WINDOW
                // ==========================================
                $election = Election::find($device->election_id);

                if (!$election) {
                    throw new \Exception('Data pemilihan tidak ditemukan.');
                }

                if (!$election->isActive()) {
                    throw new \Exception('Pemilihan tidak dalam status aktif.');
                }

                if (!$election->isWithinTimeWindow()) {
                    throw new \Exception('Pemilihan sudah di luar waktu yang ditentukan.');
                }

                // ==========================================
                // PASTIKAN KANDIDAT DI ELECTION INI
                // ==========================================
                $candidate = Candidate::where('id', $request->candidate_id)
                    ->where('election_id', $device->election_id)
                    ->firstOrFail();

                // ==========================================
                // SIMPAN VOTE (ANONIM)
                // ==========================================
                Vote::create([
                    'election_id'  => $device->election_id,
                    'session_id'   => $voter->session_id,
                    'candidate_id' => $candidate->id,
                    'hash'         => hash('sha256', Str::uuid() . now()),
                ]);

                // Tandai voter
                $voter->update([
                    'has_voted' => true,
                    'voted_at'  => now(),
                ]);

                // ✅ Clear cache setelah vote berhasil
                Cache::forget('operator.dashboard');
                Cache::forget('admin.live_stats');

                // Reset device
                $device->update([
                    'status'            => DeviceStatus::IDLE,
                    'assigned_voter_id' => null,
                    'assigned_at'       => null,
                ]);
                $device->rotateToken(30);

                Log::info("Vote submitted: voter #{$voter->id} at election {$device->election_id}");

                // ✅ Activity Log — user_id NULL (system action, vote anonim)
                ActivityLog::log('vote.submitted', [
                    'user_id' => null,
                    'meta'    => [
                        'election_id'  => $device->election_id,
                        'session_id'   => $voter->session_id,
                        'candidate_id' => $candidate->id,
                        'via_device'   => $device->device_label,
                    ],
                ]);
            });

            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reset device manual (kalau stuck).
     */
    public function reset(): RedirectResponse
    {
        $deviceId = session('voting_device_id');
        $device   = VotingDevice::find($deviceId);

        if ($device) {
            $device->resetToIdle();
        }

        return back()->with('success', 'Device berhasil di-reset.');
    }

    /**
     * Tutup device voting.
     */
    public function close(): RedirectResponse
    {
        $deviceId = session('voting_device_id');

        if ($deviceId) {
            VotingDevice::where('id', $deviceId)->delete();
        }

        session([
            'voting_manually_closed' => true,
        ]);

        session()->forget([
            'voting_device_id',
            'voting_election_id',
            'voting_device_label',
        ]);

        Log::info("Voting device closed (device #{$deviceId})");

        return redirect()
            ->route('device.voting.index')
            ->with('success', 'Device voting ditutup.');
    }

    /**
     * Buka device lagi.
     */
    public function reopen(): RedirectResponse
    {
        session()->forget('voting_manually_closed');

        return redirect()->route('device.voting.index');
    }

    // ==========================================
    // PRIVATE HELPERS
    // ==========================================

    private function detectActiveElection(): ?Election
    {
        return Election::where('status', ElectionStatus::ACTIVE)
            ->where('start_at', '<=', now())
            ->where('end_at', '>', now())
            ->first();
    }

    private function getOrCreateDevice(Election $election): VotingDevice
    {
        $deviceLabel = session('voting_device_label');

        if (!$deviceLabel) {
            $deviceLabel = 'Bilik-' . strtoupper(Str::random(4));
            session(['voting_device_label' => $deviceLabel]);
        }

        $device = VotingDevice::firstOrCreate(
            [
                'election_id'  => $election->id,
                'device_label' => $deviceLabel,
            ],
            [
                'session_id'       => null,
                'device_token'     => strtoupper(Str::random(12)),
                'token_expired_at' => now()->addSeconds(30),
                'status'           => DeviceStatus::IDLE,
            ]
        );

        $device->resetToIdle();

        return $device;
    }
}
