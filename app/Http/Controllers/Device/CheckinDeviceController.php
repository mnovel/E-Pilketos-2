<?php

namespace App\Http\Controllers\Device;

use App\Enums\ElectionStatus;
use App\Http\Controllers\Controller;
use App\Models\CheckinDevice;
use App\Models\Election;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckinDeviceController extends Controller
{
    /** ✅ Lifetime cookie device label: 30 hari */
    private const DEVICE_LABEL_COOKIE_MINUTES = 60 * 24 * 30;

    /**
     * Entry point.
     */
    public function index(): View
    {
        if (session('checkin_manually_closed')) {
            return view('device.checkin.closed');
        }

        return $this->kiosk();
    }

    /**
     * Layar kiosk.
     */
    public function kiosk(): View
    {
        $election = $this->detectActiveElection();
        $device   = null;

        if ($election) {
            $device = $this->getOrCreateDevice($election);

            session([
                'checkin_device_id'   => $device->id,
                'checkin_election_id' => $election->id,
            ]);

            Log::info("Checkin kiosk opened for election #{$election->id} (device #{$device->id})");
        } else {
            session()->forget(['checkin_device_id', 'checkin_election_id']);
        }

        return view('device.checkin.kiosk', compact('election', 'device'));
    }

    /**
     * Polling status.
     */
    public function status(Request $request): JsonResponse
    {
        $deviceId   = session('checkin_device_id');
        $electionId = session('checkin_election_id');

        if (!$deviceId || !$electionId) {
            $active = $this->detectActiveElection();

            return response()->json([
                'status' => $active ? 'new_election' : 'waiting',
                'action' => $active ? 'reload' : 'none',
            ]);
        }

        $device = CheckinDevice::with('election')->find($deviceId);

        if (!$device) {
            session()->forget(['checkin_device_id', 'checkin_election_id']);

            return response()->json([
                'status' => 'device_lost',
                'action' => 'reload',
            ]);
        }

        $election = $device->election;

        if (!$election) {
            session()->forget(['checkin_device_id', 'checkin_election_id']);

            return response()->json([
                'status' => 'election_lost',
                'action' => 'reload',
            ]);
        }

        if ($election->autoCloseIfEnded()) {
            session()->forget(['checkin_device_id', 'checkin_election_id']);

            return response()->json([
                'status'  => 'election_ended',
                'message' => 'Waktu pemilihan sudah berakhir.',
                'action'  => 'reload',
            ]);
        }

        if (!$election->isActive()) {
            session()->forget(['checkin_device_id', 'checkin_election_id']);

            return response()->json([
                'status'  => 'election_not_active',
                'message' => 'Pemilihan tidak aktif.',
                'action'  => 'reload',
            ]);
        }

        if (!$election->isWithinTimeWindow()) {
            session()->forget(['checkin_device_id', 'checkin_election_id']);

            return response()->json([
                'status'  => 'election_out_of_window',
                'message' => 'Pemilihan di luar jam operasional.',
                'action'  => 'reload',
            ]);
        }

        $device->ping();

        if (!$device->isTokenValid()) {
            $device->rotateToken(30);
        }

        $lastScan = Cache::pull("checkin:last:{$device->id}");

        $totalChecked = $election->voters()->where('checked_in', true)->count();
        $totalVoters  = $election->voters()->count();

        return response()->json([
            'status'        => $lastScan ? 'scanned' : 'active',
            'token'         => $device->device_token,
            'expires_in'    => max(0, now()->diffInSeconds($device->token_expired_at, false)),
            'voter'         => $lastScan,
            'total_checked' => $totalChecked,
            'total_voters'  => $totalVoters,
        ]);
    }

    /**
     * ✅ Tutup device — hapus device + set flag closed + hapus cookie.
     */
    public function close(): RedirectResponse
    {
        $deviceId = session('checkin_device_id');

        // Hapus device dari DB
        if ($deviceId) {
            CheckinDevice::where('id', $deviceId)->delete();
        }

        // ✅ Hapus cookie device label biar tidak reuse device lama
        Cookie::queue(Cookie::forget('checkin_device_label'));

        // Set flag closed
        session(['checkin_manually_closed' => true]);

        // Clear semua key device dari session
        session()->forget([
            'checkin_device_id',
            'checkin_election_id',
            'checkin_device_label', // backward compat — versi lama
        ]);

        Log::info("Checkin device closed (device #{$deviceId})");

        return redirect()
            ->route('device.checkin.index')
            ->with('success', 'Device check-in ditutup.');
    }

    /**
     * ✅ Buka device lagi (dari halaman closed).
     */
    public function reopen(): RedirectResponse
    {
        session()->forget('checkin_manually_closed');

        return redirect()->route('device.checkin.index');
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

    /**
     * ✅ Device label sekarang disimpan di COOKIE (bukan session).
     *
     * Kenapa? Karena session lifetime cuma 120 menit (default).
     * Kalau session expired di tengah event, label baru dibuat → device lama orphan.
     *
     * Dengan cookie 30 hari, label persist sampai device benar-benar dihapus manual
     * (via tombol close / purge).
     */
    private function getOrCreateDevice(Election $election): CheckinDevice
    {
        // Baca dari cookie dulu
        $deviceLabel = request()->cookie('checkin_device_label');

        // Fallback: backward compat ke session (kalau user upgrade dari versi lama)
        if (!$deviceLabel) {
            $deviceLabel = session('checkin_device_label');
        }

        // Kalau masih kosong juga → generate label baru
        if (!$deviceLabel) {
            $deviceLabel = 'Kiosk-' . strtoupper(Str::random(4));
        }

        // ✅ Simpan ke cookie (lifetime 30 hari)
        Cookie::queue(
            'checkin_device_label',
            $deviceLabel,
            self::DEVICE_LABEL_COOKIE_MINUTES
        );

        $device = CheckinDevice::firstOrCreate(
            [
                'election_id'  => $election->id,
                'device_label' => $deviceLabel,
            ],
            [
                'session_id'       => null,
                'device_token'     => strtoupper(Str::random(12)),
                'token_expired_at' => now()->addSeconds(30),
            ]
        );

        if (!$device->isTokenValid()) {
            $device->rotateToken(30);
        }

        return $device;
    }
}
