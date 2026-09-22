<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;


class VoterController extends Controller
{
    /**
     * List voter dengan filter status & search.
     */
    public function index(Request $request): View
    {
        $query = User::where('role', UserRole::VOTER)
            ->with(['verifier', 'classRoom']);

        // Filter status
        $status = $request->input('status', 'pending');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Search by name, nis, atau nama kelas
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhereHas('classRoom', fn($qq) => $qq->where('name', 'like', "%{$search}%"));
            });
        }

        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100])) {
            $perPage = 20;
        }

        $voters = $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $counts = [
            'all'      => User::where('role', UserRole::VOTER)->count(),
            'pending'  => User::where('role', UserRole::VOTER)->where('status', VoterStatus::PENDING)->count(),
            'verified' => User::where('role', UserRole::VOTER)->where('status', VoterStatus::VERIFIED)->count(),
            'rejected' => User::where('role', UserRole::VOTER)->where('status', VoterStatus::REJECTED)->count(),
        ];

        return view('admin.voters.index', compact('voters', 'counts', 'status', 'perPage'));
    }

    /**
     * Detail voter.
     */
    public function show(User $voter): View
    {
        abort_if($voter->role !== UserRole::VOTER, 404);

        $voter->load(['verifier', 'classRoom']);

        return view('admin.voters.show', compact('voter'));
    }

    /**
     * Approve voter.
     */
    public function approve(User $voter): RedirectResponse
    {
        abort_if($voter->role !== UserRole::VOTER, 404);

        if ($voter->status === VoterStatus::VERIFIED) {
            return back()->with('warning', 'Voter sudah diverifikasi.');
        }

        $voter->update([
            'status'        => VoterStatus::VERIFIED,
            'verified_by'   => auth()->id(),
            'verified_at'   => now(),
            'alasan_reject' => null,
        ]);

        Log::info("Voter approved: {$voter->nis} by " . auth()->user()->name);
        \App\Models\ActivityLog::log('voter.verified', [
            'subject_type' => User::class,
            'subject_id'   => $voter->id,
            'meta'         => ['nis' => $voter->nis, 'name' => $voter->name],
        ]);

        return back()->with('success', "Voter {$voter->name} berhasil diverifikasi.");
    }

    /**
     * Reject voter.
     */
    public function reject(Request $request, User $voter): RedirectResponse
    {
        abort_if($voter->role !== UserRole::VOTER, 404);

        $request->validate([
            'alasan_reject' => ['required', 'string', 'max:500'],
        ], [
            'alasan_reject.required' => 'Alasan reject wajib diisi.',
        ]);

        $voter->update([
            'status'        => VoterStatus::REJECTED,
            'verified_by'   => auth()->id(),
            'verified_at'   => now(),
            'alasan_reject' => $request->alasan_reject,
        ]);

        Log::info("Voter rejected: {$voter->nis} by " . auth()->user()->name);

        return back()->with('success', "Voter {$voter->name} ditolak.");
    }

    /**
     * Bulk approve (multiple voter sekaligus).
     */
    public function bulkApprove(Request $request): RedirectResponse
    {
        $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['exists:users,id'],
        ]);

        $count = User::whereIn('id', $request->ids)
            ->where('role', UserRole::VOTER)
            ->where('status', VoterStatus::PENDING)
            ->update([
                'status'      => VoterStatus::VERIFIED,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);

        \App\Models\ActivityLog::log('voter.bulk_verified', [
            'meta' => ['count' => $count, 'ids' => $request->ids],
        ]);

        return back()->with('success', "{$count} voter berhasil diverifikasi.");
    }

    public function bulkReject(Request $request): RedirectResponse
    {
        $request->validate([
            'ids'           => ['required', 'array'],
            'ids.*'         => ['exists:users,id'],
            'alasan_reject' => ['required', 'string', 'max:500'],
        ], [
            'alasan_reject.required' => 'Alasan reject wajib diisi.',
        ]);

        $count = User::whereIn('id', $request->ids)
            ->where('role', UserRole::VOTER)
            ->where('status', '!=', VoterStatus::VERIFIED)
            ->update([
                'status'        => VoterStatus::REJECTED,
                'verified_by'   => auth()->id(),
                'verified_at'   => now(),
                'alasan_reject' => $request->alasan_reject,
            ]);

        \App\Models\ActivityLog::log('voter.bulk_rejected', [
            'meta' => ['count' => $count, 'ids' => $request->ids],
        ]);

        return back()->with('success', "{$count} voter ditolak.");
    }

    /**
     * Reset password voter (set manual).
     */
    public function resetPassword(Request $request, User $voter): RedirectResponse
    {
        abort_if($voter->role !== UserRole::VOTER, 404);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'password.required'  => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $voter->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Log activity
        \App\Models\ActivityLog::log('voter.password_reset', [
            'subject_type' => User::class,
            'subject_id'   => $voter->id,
            'meta'         => [
                'nis'  => $voter->nis,
                'name' => $voter->name,
            ],
        ]);

        return back()->with('success', "Password voter \"{$voter->name}\" berhasil direset.");
    }

    /**
     * Generate password random & reset.
     */
    public function generatePassword(User $voter): RedirectResponse
    {
        abort_if($voter->role !== UserRole::VOTER, 404);

        // Generate password 8 karakter (huruf + angka)
        $newPassword = \Illuminate\Support\Str::password(8, symbols: false);

        $voter->update([
            'password' => Hash::make($newPassword),
        ]);

        // Log activity (JANGAN log password!)
        \App\Models\ActivityLog::log('voter.password_generated', [
            'subject_type' => User::class,
            'subject_id'   => $voter->id,
            'meta'         => [
                'nis'  => $voter->nis,
                'name' => $voter->name,
            ],
        ]);

        return back()->with('generated_password', $newPassword)
            ->with('generated_for', $voter->name)
            ->with('success', "Password baru berhasil digenerate untuk \"{$voter->name}\".");
    }
}
