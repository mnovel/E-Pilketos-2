<?php

namespace App\Http\Controllers\Voter;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CheckinDevice;
use App\Models\CheckinLog;
use App\Models\ElectionSession;
use App\Models\Voter;
use App\Models\VotingDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ScanController extends Controller
{
    /**
     * Scan QR check-in dari HP siswa.
     */
    public function checkin(Request $request, string $token): View
    {
        $user   = auth()->user();
        $device = CheckinDevice::where('device_token', $token)
            ->with('election')
            ->first();

        // ==============================
        // 1. VALIDASI QR & DEVICE
        // ==============================
        if (!$device) {
            return $this->errorCheckin('QR Tidak Valid', 'Kode QR tidak dikenal atau sudah kadaluarsa.');
        }

        if (!$device->isTokenValid()) {
            return $this->errorCheckin('QR Kadaluarsa', 'QR sudah expired. Minta panitia refresh.');
        }

        $election = $device->election;

        if (!$election) {
            return $this->errorCheckin('Data Tidak Valid', 'Device tidak terhubung ke pemilihan manapun.');
        }

        // ==============================
        // 2. CEK ELECTION
        // ==============================
        if ($election->autoCloseIfEnded()) {
            return $this->errorCheckin('Waktu Pemilihan Habis', 'Waktu pemilihan sudah berakhir.');
        }

        if (!$election->isActive()) {
            return $this->errorCheckin('Pemilihan Tidak Aktif', 'Pemilihan belum atau tidak aktif.');
        }

        if (!$election->hasStarted()) {
            return $this->errorCheckin(
                'Belum Waktunya',
                'Pemilihan baru dibuka pada '
                    . $election->start_at->translatedFormat('d M Y, H:i')
            );
        }

        if (!$election->isWithinTimeWindow()) {
            return $this->errorCheckin('Di Luar Waktu', 'Pemilihan di luar jam operasional.');
        }

        // ==============================
        // 3. CARI VOTER
        // ==============================
        $voter = Voter::where('election_id', $election->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$voter) {
            return $this->errorCheckin(
                'Anda Belum Terdaftar',
                'Data pemilih Anda belum terdaftar di pemilihan ini. Hubungi panitia.'
            );
        }

        if ($voter->checked_in) {
            return view('voter.scan.checkin', [
                'status'  => 'info',
                'title'   => 'Sudah Check-in',
                'message' => 'Anda sudah melakukan check-in sebelumnya.',
                'voter'   => $voter,
            ]);
        }

        // ==============================
        // 4. ✅ AUTO-MATCH SESI BY KELAS
        // ==============================
        $session = ElectionSession::where('election_id', $election->id)
            ->where('class_id', $user->class_id)
            ->where('status', SessionStatus::ACTIVE)
            ->get()
            ->first(fn($s) => $s->isWithinTimeWindow());

        if (!$session) {
            return $this->errorCheckin(
                'Sesi Kelas Tidak Aktif',
                'Sesi voting untuk kelas ' . ($user->classRoom?->name ?? '-')
                    . ' sedang tidak aktif. Tunggu panitia membuka sesi kelas Anda.'
            );
        }

        // ==============================
        // 5. CATAT CHECK-IN
        // ==============================
        $voter->update([
            'session_id'    => $session->id,
            'checked_in'    => true,
            'checked_in_at' => now(),
        ]);

        CheckinLog::create([
            'election_id'       => $election->id,
            'session_id'        => $session->id,
            'voter_id'          => $voter->id,
            'checkin_device_id' => $device->id,
            'operator_id'       => $session->operator_id,
            'ip_address'        => $request->ip(),
            'scanned_at'        => now(),
        ]);

        // ==============================
        // 6. CACHE UNTUK POLLING DEVICE
        // ==============================
        Cache::put("checkin:last:{$device->id}", [
            'nama'  => $user->name,
            'kelas' => $user->classRoom?->name ?? '-',
            'time'  => now()->format('H:i:s'),
        ], now()->addSeconds(30));

        Log::info("Voter checked-in: {$user->name} ({$user->classRoom?->name}) at election #{$election->id}");

        // ✅ Activity Log — lengkapi meta
        ActivityLog::log('checkin.success', [
            'subject_type' => Voter::class,
            'subject_id'   => $voter->id,
            'meta'         => [
                'election_id'  => $election->id,
                'election'     => $election->title,
                'session_id'   => $session->id,
                'kelas'        => $user->classRoom?->name,
                'nama'         => $user->name,
                'device'       => $device->device_label,
            ],
        ]);

        return view('voter.scan.checkin', [
            'status'  => 'success',
            'title'   => 'Check-in Berhasil',
            'message' => 'Silakan masuk ke area bilik suara.',
            'voter'   => $voter,
            'session' => $session,
        ]);
    }

    /**
     * Scan QR dari HP siswa untuk akses ballot.
     */
    public function voting(Request $request, string $token): View
    {
        $user   = auth()->user();
        $device = VotingDevice::where('device_token', $token)
            ->with('election')
            ->first();

        // ==============================
        // 1. VALIDASI QR & DEVICE
        // ==============================
        if (!$device) {
            return $this->errorVoting('QR Tidak Valid', 'Kode QR tidak dikenal atau sudah kadaluarsa.');
        }

        if (!$device->isTokenValid()) {
            return $this->errorVoting('QR Kadaluarsa', 'QR sudah expired. Minta panitia refresh.');
        }

        if (!$device->isIdle()) {
            return $this->errorVoting('Device Sedang Dipakai', 'Tunggu sampai siswa sebelumnya selesai.');
        }

        $election = $device->election;

        if (!$election) {
            return $this->errorVoting('Data Tidak Valid', 'Device tidak terhubung ke pemilihan manapun.');
        }

        // ==============================
        // 2. CEK ELECTION
        // ==============================
        if ($election->autoCloseIfEnded()) {
            return $this->errorVoting('Waktu Pemilihan Habis', 'Waktu pemilihan sudah berakhir.');
        }

        if (!$election->isActive() || !$election->isWithinTimeWindow()) {
            return $this->errorVoting('Pemilihan Tidak Aktif', 'Pemilihan tidak dalam status buka.');
        }

        // ==============================
        // 3. CARI VOTER
        // ==============================
        $voter = Voter::where('election_id', $election->id)
            ->where('user_id', $user->id)
            ->with('session')
            ->first();

        if (!$voter) {
            return $this->errorVoting('Belum Terdaftar', 'Data pemilih Anda belum terdaftar. Hubungi panitia.');
        }

        if (!$voter->checked_in) {
            return $this->errorVoting('Belum Check-in', 'Silakan check-in dulu di pintu masuk.');
        }

        if ($voter->has_voted) {
            return view('voter.scan.voting', [
                'status'  => 'info',
                'title'   => 'Sudah Memilih',
                'message' => 'Anda sudah menggunakan hak suara Anda.',
                'voter'   => $voter,
            ]);
        }

        // ==============================
        // 4. ✅ VALIDASI SESSION VOTER MASIH AKTIF
        // ==============================
        $session = $voter->session;

        if (!$session) {
            return $this->errorVoting(
                'Sesi Tidak Ditemukan',
                'Data sesi Anda tidak ditemukan. Hubungi panitia.'
            );
        }

        if ($session->autoCloseAny() || !$session->isActive()) {
            return $this->errorVoting(
                'Sesi Sudah Berakhir',
                'Sesi kelas Anda sudah berakhir.'
            );
        }

        if (!$session->isWithinTimeWindow()) {
            return $this->errorVoting(
                'Di Luar Waktu Sesi',
                'Waktu sesi kelas Anda sudah lewat.'
            );
        }

        // ==============================
        // 5. ASSIGN DEVICE KE VOTER
        // ==============================
        DB::transaction(function () use ($device, $voter) {
            $device->assignTo($voter);
        });

        Log::info("Voting device assigned: voter #{$voter->id} → device #{$device->id}");

        // ✅ Activity Log — voter berhasil scan QR voting
        // (device sudah di-assign, voter siap memilih di bilik)
        ActivityLog::log('voter.voting_scanned', [
            'subject_type' => Voter::class,
            'subject_id'   => $voter->id,
            'meta'         => [
                'election_id' => $election->id,
                'election'    => $election->title,
                'session_id'  => $session->id,
                'kelas'       => $user->classRoom?->name,
                'nama'        => $user->name,
                'device'      => $device->device_label,
            ],
        ]);

        return view('voter.scan.voting', [
            'status'  => 'success',
            'title'   => 'Berhasil',
            'message' => 'Silakan pilih kandidat di layar device.',
            'voter'   => $voter,
            'session' => $session,
        ]);
    }

    // ==========================================
    // PRIVATE HELPERS
    // ==========================================

    /**
     * Shortcut: render error view untuk check-in.
     */
    private function errorCheckin(string $title, string $message): View
    {
        return view('voter.scan.checkin', [
            'status'  => 'error',
            'title'   => $title,
            'message' => $message,
        ]);
    }

    /**
     * Shortcut: render error view untuk voting.
     */
    private function errorVoting(string $title, string $message): View
    {
        return view('voter.scan.voting', [
            'status'  => 'error',
            'title'   => $title,
            'message' => $message,
        ]);
    }
}
