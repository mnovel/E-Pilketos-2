<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ElectionStatus;
use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ClassRoom;
use App\Models\ElectionSession;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class VoterController extends Controller
{
    /**
     * List voter dengan filter status & search.
     */
    public function index(Request $request): View
    {
        $query = User::where('role', UserRole::VOTER)
            ->with([
                'verifier',
                'classRoom',
                'voterRecords.election',
                'voterRecords.session',
            ]);

        $status = $request->input('status', 'pending');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

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
     * Form create voter.
     */
    public function create(): View
    {
        $classes = ClassRoom::active()
            ->orderBy('tingkat')
            ->orderBy('name')
            ->get();

        return view('admin.voters.create', compact('classes'));
    }

    /**
     * Store voter baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nis'           => ['required', 'string', 'max:50', 'unique:users,nis'],
            'name'          => ['required', 'string', 'max:255'],
            'class_id'      => ['required', 'exists:classes,id'],
            'email'         => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password'      => ['nullable', 'string', 'min:8'],
            'kartu_pelajar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'nis.required'        => 'NIS wajib diisi.',
            'nis.unique'          => 'NIS sudah terdaftar.',
            'name.required'       => 'Nama wajib diisi.',
            'class_id.required'   => 'Kelas wajib dipilih.',
            'class_id.exists'     => 'Kelas tidak valid.',
            'email.email'         => 'Format email tidak valid.',
            'email.unique'        => 'Email sudah terdaftar.',
            'password.min'        => 'Password minimal 8 karakter.',
            'kartu_pelajar.image' => 'File harus berupa gambar.',
            'kartu_pelajar.max'   => 'Ukuran gambar maksimal 2 MB.',
        ]);

        $email = $validated['email'] ?: $this->generateEmail($validated['nis']);

        $kartuPath = null;
        if ($request->hasFile('kartu_pelajar')) {
            $kartuPath = $request->file('kartu_pelajar')->store('kartu-pelajar', 'public');
        }

        $voter = User::create([
            'nis'           => $validated['nis'],
            'name'          => $validated['name'],
            'class_id'      => $validated['class_id'],
            'email'         => $email,
            'password'      => Hash::make($validated['password'] ?? 'password'),
            'role'          => UserRole::VOTER,
            'status'        => VoterStatus::PENDING,
            'kartu_pelajar' => $kartuPath,
        ]);

        try {
            if (!$voter->hasRole('voter')) {
                $voter->assignRole('voter');
            }
        } catch (\Throwable $e) {
            Log::warning('Role voter belum ada: ' . $e->getMessage());
        }

        Log::info("Voter created: {$voter->nis} by " . auth()->user()->name);

        ActivityLog::log('voter.created', [
            'subject_type' => User::class,
            'subject_id'   => $voter->id,
            'meta'         => [
                'nis'       => $voter->nis,
                'name'      => $voter->name,
                'kelas'     => $voter->classRoom?->name,
                'email'     => $voter->email,
                'has_kartu' => (bool) $kartuPath,
            ],
        ]);

        return redirect()
            ->route('admin.voters.index', ['status' => 'pending'])
            ->with('success', "Pemilih \"{$voter->name}\" berhasil ditambahkan.");
    }

    /**
     * Detail voter.
     */
    public function show(User $voter): View
    {
        abort_if($voter->role !== UserRole::VOTER, 404);

        $voter->load([
            'verifier',
            'classRoom',
            'voterRecords.election',
            'voterRecords.session',
        ]);

        return view('admin.voters.show', compact('voter'));
    }

    /**
     * Form edit voter.
     */
    public function edit(User $voter): View|RedirectResponse
    {
        abort_if($voter->role !== UserRole::VOTER, 404);

        $voter->load(['voterRecords.election', 'voterRecords.session']);

        if (!$voter->canEditVoterData()) {
            return redirect()
                ->route('admin.voters.show', $voter)
                ->with('warning', 'Tidak bisa edit data pemilih: ' . $voter->getEditLockReason() . '.');
        }

        $classes = ClassRoom::active()
            ->orderBy('tingkat')
            ->orderBy('name')
            ->get();

        $voterRecord = $voter->voterRecords->first();

        return view('admin.voters.edit', compact('voter', 'classes', 'voterRecord'));
    }

    /**
     * Update voter.
     *
     * Yang boleh diubah: nis, name, class_id, email, kartu_pelajar.
     *
     * Khusus pindah kelas:
     * - Election ACTIVE atau Session ACTIVE → DIBLOKIR.
     * - Voter sudah check-in / vote → DIBLOKIR.
     * - Voter sudah terdaftar di session → auto-assign ke session baru (kalau ada 1 cocok).
     * - Voter belum terdaftar di session → class_id di record voters tetap di-sync.
     */
    public function update(Request $request, User $voter): RedirectResponse
    {
        abort_if($voter->role !== UserRole::VOTER, 404);

        $voter->load(['voterRecords.election', 'voterRecords.session']);

        if (!$voter->canEditVoterData()) {
            return redirect()
                ->route('admin.voters.show', $voter)
                ->with('warning', 'Tidak bisa edit data pemilih: ' . $voter->getEditLockReason() . '.');
        }

        $validated = $request->validate([
            'nis'           => ['required', 'string', 'max:50', 'unique:users,nis,' . $voter->id],
            'name'          => ['required', 'string', 'max:255'],
            'class_id'      => ['required', 'exists:classes,id'],
            'email'         => ['required', 'email', 'max:255', 'unique:users,email,' . $voter->id],
            'kartu_pelajar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hapus_kartu'   => ['nullable', 'boolean'],
        ], [
            'nis.required'        => 'NIS wajib diisi.',
            'nis.unique'          => 'NIS sudah dipakai voter lain.',
            'name.required'       => 'Nama wajib diisi.',
            'class_id.required'   => 'Kelas wajib dipilih.',
            'class_id.exists'     => 'Kelas tidak valid.',
            'email.required'      => 'Email wajib diisi.',
            'email.email'         => 'Format email tidak valid.',
            'email.unique'        => 'Email sudah dipakai user lain.',
            'kartu_pelajar.image' => 'File harus berupa gambar.',
            'kartu_pelajar.max'   => 'Ukuran gambar maksimal 2 MB.',
        ]);

        // ==========================================
        // CEK PERUBAHAN KELAS
        // ==========================================
        $classChanged = (int) $voter->class_id !== (int) $validated['class_id'];

        $autoAssigned = false;
        $detached     = false;
        $autoSession  = null;

        if ($classChanged) {
            $voterRecord = $voter->voterRecords->first();

            if ($voterRecord) {
                // ✅ Selalu sync class_id + reset check-in state
                $updateData = [
                    'class_id'      => $validated['class_id'],
                    'checked_in'    => false,
                    'checked_in_at' => null,
                ];

                // ✅ Auto-assign HANYA kalau voter sudah terdaftar di session
                if ($voterRecord->session_id) {
                    $candidates = ElectionSession::where('class_id', $validated['class_id'])
                        ->where('status', SessionStatus::SCHEDULED)
                        ->whereHas('election', fn($q) => $q->where('status', ElectionStatus::DRAFT))
                        ->with('election')
                        ->get()
                        ->filter(fn($s) => !$s->hasEnded())
                        ->values();

                    if ($candidates->count() === 1) {
                        $autoSession = $candidates->first();
                        $updateData['session_id'] = $autoSession->id;
                        $autoAssigned = true;
                    } else {
                        $updateData['session_id'] = null;
                        $detached = true;
                    }
                }
                // else: voter belum di-assign → session_id tetap null, tidak perlu diubah

                $voterRecord->update($updateData);
            }
        }

        // ==========================================
        // BUILD DATA UPDATE UNTUK users
        // ==========================================
        $newData = [
            'nis'      => $validated['nis'],
            'name'     => $validated['name'],
            'class_id' => $validated['class_id'],
            'email'    => $validated['email'],
        ];

        $changes = [];
        foreach ($newData as $field => $newValue) {
            $oldValue = $voter->$field;

            if ((string) $oldValue !== (string) $newValue) {
                $changes[$field] = ['from' => $oldValue, 'to' => $newValue];
            }
        }

        if ($request->hasFile('kartu_pelajar')) {
            if ($voter->kartu_pelajar && Storage::disk('public')->exists($voter->kartu_pelajar)) {
                Storage::disk('public')->delete($voter->kartu_pelajar);
            }

            $newData['kartu_pelajar'] = $request->file('kartu_pelajar')->store('kartu-pelajar', 'public');
            $changes['kartu_pelajar'] = ['from' => $voter->kartu_pelajar, 'to' => $newData['kartu_pelajar']];
        } elseif ($request->boolean('hapus_kartu')) {
            if ($voter->kartu_pelajar && Storage::disk('public')->exists($voter->kartu_pelajar)) {
                Storage::disk('public')->delete($voter->kartu_pelajar);
            }

            $newData['kartu_pelajar'] = null;
            $changes['kartu_pelajar'] = ['from' => $voter->kartu_pelajar, 'to' => null];
        }

        $voter->update($newData);
        $voter->refresh();

        Log::info("Voter updated: {$voter->nis} by " . auth()->user()->name);

        if (!empty($changes)) {
            ActivityLog::log('voter.updated', [
                'subject_type' => User::class,
                'subject_id'   => $voter->id,
                'meta'         => [
                    'nis'             => $voter->nis,
                    'name'            => $voter->name,
                    'kelas'           => $voter->classRoom?->name,
                    'changes'         => $changes,
                    'class_changed'   => $classChanged,
                    'auto_assigned'   => $autoAssigned,
                    'detached'        => $detached,
                    'auto_session_id' => $autoSession?->id,
                ],
            ]);
        }

        $message = "Data pemilih \"{$voter->name}\" berhasil diperbarui.";

        if ($autoAssigned && $autoSession) {
            $sessionName = $autoSession->classRoom?->name ?? '-';
            $message .= " Voter otomatis di-assign ke sesi kelas {$sessionName}.";
        } elseif ($detached) {
            $message .= " Voter dilepas dari sesi lama — assign manual ke sesi kelas baru.";
        }

        return redirect()
            ->route('admin.voters.show', $voter)
            ->with('success', $message);
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

        ActivityLog::log('voter.verified', [
            'subject_type' => User::class,
            'subject_id'   => $voter->id,
            'meta'         => [
                'nis'   => $voter->nis,
                'name'  => $voter->name,
                'kelas' => $voter->classRoom?->name,
            ],
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

        ActivityLog::log('voter.rejected', [
            'subject_type' => User::class,
            'subject_id'   => $voter->id,
            'meta'         => [
                'nis'    => $voter->nis,
                'name'   => $voter->name,
                'kelas'  => $voter->classRoom?->name,
                'alasan' => $request->alasan_reject,
            ],
        ]);

        return back()->with('success', "Voter {$voter->name} ditolak.");
    }

    /**
     * Bulk approve.
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

        ActivityLog::log('voter.bulk_verified', [
            'meta' => [
                'count' => $count,
                'ids'   => $request->ids,
            ],
        ]);

        return back()->with('success', "{$count} voter berhasil diverifikasi.");
    }

    /**
     * Bulk reject.
     */
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

        ActivityLog::log('voter.bulk_rejected', [
            'meta' => [
                'count'  => $count,
                'ids'    => $request->ids,
                'alasan' => $request->alasan_reject,
            ],
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

        ActivityLog::log('voter.password_reset', [
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

        $newPassword = Str::password(8, symbols: false);

        $voter->update([
            'password' => Hash::make($newPassword),
        ]);

        ActivityLog::log('voter.password_generated', [
            'subject_type' => User::class,
            'subject_id'   => $voter->id,
            'meta'         => [
                'nis'  => $voter->nis,
                'name' => $voter->name,
            ],
        ]);

        return back()
            ->with('generated_password', $newPassword)
            ->with('generated_for', $voter->name)
            ->with('success', "Password baru berhasil digenerate untuk \"{$voter->name}\".");
    }

    // ==========================================
    // PRIVATE HELPERS
    // ==========================================

    /**
     * ✅ Generate email unik dengan format baru: siswa.{3 random}@pilketos.test
     */
    private function generateEmail(string $nis): string
    {
        do {
            $random = strtolower(Str::random(3));
            $email  = "siswa.{$random}@pilketos.test";
        } while (User::where('email', $email)->exists());

        return $email;
    }
}
