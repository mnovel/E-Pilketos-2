<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckinDevice;
use App\Models\Election;
use App\Models\VotingDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DeviceLogController extends Controller
{
    /** Device dianggap online kalau ping < 2 menit */
    private const ONLINE_THRESHOLD_MINUTES = 2;

    /** Device dianggap stale kalau ping > 10 menit */
    private const STALE_THRESHOLD_MINUTES = 10;

    /**
     * List semua device (check-in & voting).
     */
    public function index(Request $request): View
    {
        $type       = $request->input('type', 'all');    // all|checkin|voting
        $status     = $request->input('status', 'all');  // all|online|offline
        $electionId = $request->input('election_id');

        $onlineThreshold = now()->subMinutes(self::ONLINE_THRESHOLD_MINUTES);

        // === CHECK-IN DEVICES ===
        $checkinDevices = collect();
        if (in_array($type, ['all', 'checkin'], true)) {
            $q = CheckinDevice::with(['election', 'session.classRoom'])
                ->orderByDesc('last_ping_at');

            $this->applyFilters($q, $electionId, $status, $onlineThreshold);
            $checkinDevices = $q->get();
        }

        // === VOTING DEVICES ===
        $votingDevices = collect();
        if (in_array($type, ['all', 'voting'], true)) {
            $q = VotingDevice::with([
                'election',
                'session.classRoom',
                'assignedVoter.user',
                'assignedVoter.classRoom',
            ])->orderByDesc('last_ping_at');

            $this->applyFilters($q, $electionId, $status, $onlineThreshold);
            $votingDevices = $q->get();
        }

        // === STATS ===
        $stats = [
            'checkin_total'  => CheckinDevice::count(),
            'checkin_online' => CheckinDevice::where('last_ping_at', '>=', $onlineThreshold)->count(),
            'voting_total'   => VotingDevice::count(),
            'voting_online'  => VotingDevice::where('last_ping_at', '>=', $onlineThreshold)->count(),
            'stale_count'    => CheckinDevice::where(function ($q) use ($onlineThreshold) {
                $q->where('last_ping_at', '<', $onlineThreshold)->orWhereNull('last_ping_at');
            })->count()
                + VotingDevice::where(function ($q) use ($onlineThreshold) {
                    $q->where('last_ping_at', '<', $onlineThreshold)->orWhereNull('last_ping_at');
                })->count(),
        ];

        $elections = Election::orderBy('created_at', 'desc')->get(['id', 'title']);

        return view('admin.device-logs.index', compact(
            'checkinDevices',
            'votingDevices',
            'stats',
            'elections',
            'type',
            'status',
            'electionId'
        ));
    }

    /**
     * Hapus 1 device check-in.
     */
    public function destroyCheckin(CheckinDevice $device): RedirectResponse
    {
        $label = $device->device_label;
        $device->delete();

        Log::info("CheckinDevice deleted via log panel: {$label} by " . auth()->user()->name);

        return back()->with('success', "Device check-in \"{$label}\" dihapus.");
    }

    /**
     * Hapus 1 device voting.
     */
    public function destroyVoting(VotingDevice $device): RedirectResponse
    {
        $label = $device->device_label;
        $device->delete();

        Log::info("VotingDevice deleted via log panel: {$label} by " . auth()->user()->name);

        return back()->with('success', "Device voting \"{$label}\" dihapus.");
    }

    /**
     * Hapus semua device yang offline (ping > 10 menit atau NULL).
     */
    public function purgeOffline(): RedirectResponse
    {
        $threshold = now()->subMinutes(self::STALE_THRESHOLD_MINUTES);

        $checkin = CheckinDevice::where(function ($q) use ($threshold) {
            $q->where('last_ping_at', '<', $threshold)->orWhereNull('last_ping_at');
        })->delete();

        $voting = VotingDevice::where(function ($q) use ($threshold) {
            $q->where('last_ping_at', '<', $threshold)->orWhereNull('last_ping_at');
        })->delete();

        Log::warning("Purged stale devices: {$checkin} checkin, {$voting} voting by " . auth()->user()->name);

        return back()->with('success', "Berhasil hapus {$checkin} check-in & {$voting} voting device yang offline.");
    }

    // ==========================================
    // HELPERS
    // ==========================================

    private function applyFilters($query, ?string $electionId, string $status, $onlineThreshold): void
    {
        if ($electionId) {
            $query->where('election_id', $electionId);
        }

        if ($status === 'online') {
            $query->where('last_ping_at', '>=', $onlineThreshold);
        } elseif ($status === 'offline') {
            $query->where(function ($q) use ($onlineThreshold) {
                $q->where('last_ping_at', '<', $onlineThreshold)
                    ->orWhereNull('last_ping_at');
            });
        }
    }
}
