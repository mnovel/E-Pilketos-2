<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ElectionStatus;
use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\ElectionSession;
use App\Models\User;
use App\Models\Voter;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ElectionSessionController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $electionId = $request->input('election_id');

        if (!$electionId) {
            $latest = Election::orderBy('created_at', 'desc')->first();

            if (!$latest) {
                return redirect()
                    ->route('admin.elections.index')
                    ->with('warning', 'Belum ada pemilihan.');
            }

            return redirect()->route('admin.sessions.index', ['election_id' => $latest->id]);
        }

        $election = Election::findOrFail($electionId);

        if ($election->autoActivateIfReady()) {
            return redirect()
                ->route('admin.sessions.index', ['election_id' => $election->id])
                ->with('success', 'Pemilihan otomatis diaktifkan karena waktu sudah masuk.');
        }

        if ($election->autoCloseIfEnded()) {
            return redirect()
                ->route('admin.sessions.index', ['election_id' => $election->id])
                ->with('warning', 'Waktu pemilihan sudah berakhir.');
        }

        ElectionSession::where('election_id', $election->id)
            ->where('status', SessionStatus::SCHEDULED)
            ->get()
            ->filter(fn($s) => $s->isWithinTimeWindow() && $election->isActive())
            ->each(fn($s) => $s->autoActivateIfReady());

        ElectionSession::where('election_id', $election->id)
            ->whereIn('status', [SessionStatus::ACTIVE, SessionStatus::SCHEDULED])
            ->get()
            ->each(fn($s) => $s->autoCloseAny());

        $sessions = ElectionSession::where('election_id', $election->id)
            ->with(['operator', 'classRoom'])
            ->withCount([
                'voters',
                'voters as checked_in_count' => fn($q) => $q->where('checked_in', true),
                'voters as voted_count'      => fn($q) => $q->where('has_voted', true),
            ])
            ->orderBy('tanggal')
            ->orderBy('waktu_mulai')
            ->get();

        return view('admin.sessions.index', compact('election', 'sessions'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $electionId = $request->input('election_id');

        if (!$electionId) {
            return redirect()
                ->route('admin.sessions.index')
                ->with('warning', 'Pilih pemilihan dulu.');
        }

        $election = Election::findOrFail($electionId);

        if ($election->isPublished()) {
            return redirect()
                ->route('admin.sessions.index', ['election_id' => $election->id])
                ->with('error', 'Pemilihan sudah dipublikasikan.');
        }

        if ($election->isClosed()) {
            return redirect()
                ->route('admin.sessions.index', ['election_id' => $election->id])
                ->with('error', 'Pemilihan sudah ditutup.');
        }

        if ($election->hasEnded()) {
            $election->autoCloseIfEnded();
            return redirect()
                ->route('admin.sessions.index', ['election_id' => $election->id])
                ->with('error', 'Waktu pemilihan sudah berakhir.');
        }

        $usedClassIds = ElectionSession::where('election_id', $election->id)
            ->pluck('class_id')
            ->toArray();

        $classes = ClassRoom::active()
            ->whereNotIn('id', $usedClassIds)
            ->orderBy('tingkat')
            ->orderBy('name')
            ->get();

        $operators = User::where('role', UserRole::OPERATOR)
            ->orWhere('role', UserRole::ADMIN)
            ->orderBy('name')
            ->get();

        return view('admin.sessions.create', compact('election', 'classes', 'operators'));
    }

    public function store(Request $request): RedirectResponse
    {
        $election = Election::findOrFail($request->input('election_id'));

        if (!in_array($election->status, [ElectionStatus::DRAFT, ElectionStatus::ACTIVE])) {
            return back()->with('error', 'Tidak bisa membuat sesi: pemilihan tidak dalam status draft atau aktif.');
        }

        if ($election->hasEnded()) {
            $election->autoCloseIfEnded();
            return back()->with('error', 'Waktu pemilihan sudah berakhir.');
        }

        $validated = $request->validate([
            'class_id'      => ['required', 'exists:classes,id'],
            'tanggal'       => ['required', 'date'],
            'waktu_mulai'   => ['required', 'date_format:H:i'],
            'waktu_selesai' => ['required', 'date_format:H:i', 'after:waktu_mulai'],
            'operator_id'   => ['nullable', 'exists:users,id'],
            'auto_assign'   => ['nullable', 'boolean'],
        ], [
            'class_id.required'    => 'Kelas wajib dipilih.',
            'tanggal.required'     => 'Tanggal wajib diisi.',
            'waktu_mulai.required' => 'Waktu mulai wajib diisi.',
            'waktu_selesai.after'  => 'Waktu selesai harus setelah waktu mulai.',
        ]);

        // VALIDASI: kombinasi tanggal + jam dalam range election
        $sessionStart = Carbon::parse($validated['tanggal'] . ' ' . $validated['waktu_mulai']);
        $sessionEnd   = Carbon::parse($validated['tanggal'] . ' ' . $validated['waktu_selesai']);

        if ($sessionStart < $election->start_at) {
            return back()->withInput()->withErrors([
                'waktu_mulai' => 'Waktu mulai sesi harus setelah waktu mulai pemilihan ('
                    . $election->start_at->translatedFormat('d M Y, H:i') . ').',
            ]);
        }

        if ($sessionEnd > $election->end_at) {
            return back()->withInput()->withErrors([
                'waktu_selesai' => 'Waktu selesai sesi harus sebelum waktu selesai pemilihan ('
                    . $election->end_at->translatedFormat('d M Y, H:i') . ').',
            ]);
        }

        // Validasi: kelas belum punya sesi
        $exists = ElectionSession::where('election_id', $election->id)
            ->where('class_id', $validated['class_id'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'class_id' => 'Sesi untuk kelas ini sudah ada.',
            ]);
        }

        // ✅ Validasi: operator bentrok
        if (!empty($validated['operator_id'])) {
            $conflict = ElectionSession::where('operator_id', $validated['operator_id'])
                ->whereDate('tanggal', $validated['tanggal'])
                ->where('status', '!=', SessionStatus::CLOSED)
                ->where(function ($q) use ($validated) {
                    // Proper overlap check:
                    // existing.start < new.end AND existing.end > new.start
                    $q->where('waktu_mulai', '<', $validated['waktu_selesai'])
                        ->where('waktu_selesai', '>', $validated['waktu_mulai']);
                })
                ->exists();

            if ($conflict) {
                return back()->withInput()->withErrors([
                    'operator_id' => 'Operator sudah punya sesi lain di jam yang sama.',
                ]);
            }
        }

        $session = ElectionSession::create([
            'election_id'   => $election->id,
            'class_id'      => $validated['class_id'],
            'tanggal'       => $validated['tanggal'],
            'waktu_mulai'   => $validated['waktu_mulai'],
            'waktu_selesai' => $validated['waktu_selesai'],
            'operator_id'   => $validated['operator_id'] ?? null,
            'status'        => SessionStatus::SCHEDULED,
        ]);

        $assignedCount = 0;
        if (!empty($validated['auto_assign'])) {
            $assignedCount = $this->assignVotersToSession($session);
            Log::info("Session created + {$assignedCount} voters assigned");
        }

        ActivityLog::log('session.created', [
            'subject_type' => ElectionSession::class,
            'subject_id'   => $session->id,
            'meta'         => [
                'election_id'     => $election->id,
                'election'        => $election->title,
                'kelas'           => $session->classRoom?->name,
                'tanggal'         => $session->tanggal->format('Y-m-d'),
                'waktu'           => $session->waktu_mulai . ' - ' . $session->waktu_selesai,
                'operator'        => $session->operator?->name,
                'voters_assigned' => $assignedCount,
            ],
        ]);

        return redirect()
            ->route('admin.sessions.show', $session)
            ->with('success', 'Sesi berhasil dibuat.');
    }

    public function show(ElectionSession $session): View|RedirectResponse
    {
        $session->load('election', 'operator', 'classRoom');

        if ($session->election->autoActivateIfReady()) {
            return redirect()
                ->route('admin.sessions.show', $session)
                ->with('success', 'Pemilihan otomatis diaktifkan karena waktu sudah masuk.');
        }

        if ($session->election->autoCloseIfEnded()) {
            return redirect()
                ->route('admin.sessions.show', $session)
                ->with('warning', 'Waktu pemilihan sudah berakhir.');
        }

        if ($session->autoActivateIfReady()) {
            return redirect()
                ->route('admin.sessions.show', $session)
                ->with('success', 'Sesi otomatis diaktifkan karena waktu sudah masuk.');
        }

        if ($session->autoCloseAny()) {
            return redirect()
                ->route('admin.sessions.show', $session)
                ->with('warning', 'Waktu sesi sudah berakhir.');
        }

        $voters = Voter::where('session_id', $session->id)
            ->with(['user', 'classRoom'])
            ->orderBy('id')
            ->get();

        $stats = [
            'total_voters'     => $voters->count(),
            'total_checked_in' => $voters->where('checked_in', true)->count(),
            'total_voted'      => $voters->where('has_voted', true)->count(),
        ];

        return view('admin.sessions.show', compact('session', 'voters', 'stats'));
    }

    public function edit(ElectionSession $session): View|RedirectResponse
    {
        if ($session->status !== SessionStatus::SCHEDULED) {
            return redirect()
                ->route('admin.sessions.show', $session)
                ->with('error', 'Hanya sesi terjadwal yang dapat diedit.');
        }

        if ($session->hasEnded()) {
            return redirect()
                ->route('admin.sessions.show', $session)
                ->with('error', 'Waktu sesi sudah lewat.');
        }

        $session->load('election');

        if (!in_array($session->election->status, [ElectionStatus::DRAFT, ElectionStatus::ACTIVE])) {
            return redirect()
                ->route('admin.sessions.show', $session)
                ->with('error', 'Tidak bisa edit: pemilihan tidak dalam status draft atau aktif.');
        }

        $classes = ClassRoom::active()
            ->orderBy('tingkat')
            ->orderBy('name')
            ->get();

        $operators = User::where('role', UserRole::OPERATOR)
            ->orWhere('role', UserRole::ADMIN)
            ->orderBy('name')
            ->get();

        return view('admin.sessions.edit', compact('session', 'classes', 'operators'));
    }

    public function update(Request $request, ElectionSession $session): RedirectResponse
    {
        if ($session->status !== SessionStatus::SCHEDULED) {
            return back()->with('error', 'Hanya sesi terjadwal yang dapat diedit.');
        }

        if ($session->hasEnded()) {
            return back()->with('error', 'Waktu sesi sudah lewat.');
        }

        $validated = $request->validate([
            'class_id'      => ['required', 'exists:classes,id'],
            'tanggal'       => ['required', 'date'],
            'waktu_mulai'   => ['required', 'date_format:H:i'],
            'waktu_selesai' => ['required', 'date_format:H:i', 'after:waktu_mulai'],
            'operator_id'   => ['nullable', 'exists:users,id'],
        ]);

        $sessionStart = Carbon::parse($validated['tanggal'] . ' ' . $validated['waktu_mulai']);
        $sessionEnd   = Carbon::parse($validated['tanggal'] . ' ' . $validated['waktu_selesai']);

        if ($sessionStart < $session->election->start_at) {
            return back()->withInput()->withErrors([
                'waktu_mulai' => 'Waktu mulai sesi harus setelah waktu mulai pemilihan ('
                    . $session->election->start_at->translatedFormat('d M Y, H:i') . ').',
            ]);
        }

        if ($sessionEnd > $session->election->end_at) {
            return back()->withInput()->withErrors([
                'waktu_selesai' => 'Waktu selesai sesi harus sebelum waktu selesai pemilihan ('
                    . $session->election->end_at->translatedFormat('d M Y, H:i') . ').',
            ]);
        }

        // ✅ Validasi: operator bentrok (exclude session ini sendiri)
        if (!empty($validated['operator_id'])) {
            $conflict = ElectionSession::where('operator_id', $validated['operator_id'])
                ->where('id', '!=', $session->id)
                ->whereDate('tanggal', $validated['tanggal'])
                ->where('status', '!=', SessionStatus::CLOSED)
                ->where(function ($q) use ($validated) {
                    $q->where('waktu_mulai', '<', $validated['waktu_selesai'])
                        ->where('waktu_selesai', '>', $validated['waktu_mulai']);
                })
                ->exists();

            if ($conflict) {
                return back()->withInput()->withErrors([
                    'operator_id' => 'Operator sudah punya sesi lain di jam yang sama.',
                ]);
            }
        }

        // Capture perubahan
        $changes = [];
        foreach (['class_id', 'tanggal', 'waktu_mulai', 'waktu_selesai', 'operator_id'] as $field) {
            $oldValue = $session->$field;
            $newValue = $validated[$field] ?? null;

            $oldStr = $oldValue instanceof \Carbon\Carbon ? $oldValue->format('Y-m-d') : (string) $oldValue;
            $newStr = (string) $newValue;

            if ($oldStr !== $newStr) {
                $changes[$field] = ['from' => $oldStr, 'to' => $newStr];
            }
        }

        $session->update($validated);
        $session->refresh();

        ActivityLog::log('session.updated', [
            'subject_type' => ElectionSession::class,
            'subject_id'   => $session->id,
            'meta'         => [
                'election' => $session->election?->title,
                'kelas'    => $session->classRoom?->name,
                'changes'  => $changes,
            ],
        ]);

        return redirect()
            ->route('admin.sessions.show', $session)
            ->with('success', 'Sesi berhasil diperbarui.');
    }

    public function destroy(ElectionSession $session): RedirectResponse
    {
        if ($session->status !== SessionStatus::SCHEDULED) {
            return back()->with('error', 'Hanya sesi terjadwal yang dapat dihapus.');
        }

        $electionId = $session->election_id;
        $kelasName  = $session->classRoom?->name ?? '-';

        $meta = [
            'election_id'  => $session->election_id,
            'election'     => $session->election?->title,
            'kelas'        => $session->classRoom?->name,
            'tanggal'      => $session->tanggal->format('Y-m-d'),
            'waktu'        => $session->waktu_mulai . ' - ' . $session->waktu_selesai,
            'total_voters' => $session->voters()->count(),
        ];

        Voter::where('session_id', $session->id)->update([
            'session_id'    => null,
            'checked_in'    => false,
            'checked_in_at' => null,
        ]);

        $session->delete();

        Log::warning("Session deleted: {$kelasName} by " . auth()->user()->name);

        ActivityLog::log('session.deleted', [
            'subject_type' => ElectionSession::class,
            'subject_id'   => $session->id,
            'meta'         => $meta,
        ]);

        return redirect()
            ->route('admin.sessions.index', ['election_id' => $electionId])
            ->with('success', "Sesi kelas {$kelasName} berhasil dihapus.");
    }

    public function assignVoters(ElectionSession $session): RedirectResponse
    {
        if ($session->status !== SessionStatus::SCHEDULED) {
            return back()->with('error', 'Pemilih hanya dapat di-assign saat sesi masih terjadwal.');
        }

        if ($session->hasEnded()) {
            return back()->with('error', 'Waktu sesi sudah lewat.');
        }

        if (!in_array($session->election->status, [ElectionStatus::DRAFT, ElectionStatus::ACTIVE])) {
            return back()->with('error', 'Pemilihan tidak dalam status draft atau aktif.');
        }

        $count = $this->assignVotersToSession($session);

        Log::info("Voters assigned: {$count} to session #{$session->id} by " . auth()->user()->name);

        if ($count > 0) {
            ActivityLog::log('session.assigned', [
                'subject_type' => ElectionSession::class,
                'subject_id'   => $session->id,
                'meta'         => [
                    'election' => $session->election?->title,
                    'kelas'    => $session->classRoom?->name,
                    'assigned' => $count,
                ],
            ]);
        }

        return back()->with('success', "{$count} pemilih berhasil di-assign ke sesi ini.");
    }

    public function close(ElectionSession $session): RedirectResponse
    {
        if ($session->status !== SessionStatus::ACTIVE) {
            return back()->with('error', 'Hanya sesi aktif yang dapat ditutup.');
        }

        $session->update([
            'status'    => SessionStatus::CLOSED,
            'closed_at' => now(),
        ]);

        $kelasName = $session->classRoom?->name ?? '-';

        Log::info("Session manually closed: {$kelasName} by " . auth()->user()->name);

        ActivityLog::log('session.closed', [
            'subject_type' => ElectionSession::class,
            'subject_id'   => $session->id,
            'meta'         => [
                'election' => $session->election?->title,
                'kelas'    => $session->classRoom?->name,
                'manual'   => true,
            ],
        ]);

        return back()->with('success', "Sesi kelas {$kelasName} berhasil ditutup.");
    }

    private function assignVotersToSession(ElectionSession $session): int
    {
        $users = User::where('role', UserRole::VOTER)
            ->where('status', VoterStatus::VERIFIED)
            ->where('class_id', $session->class_id)
            ->get();

        $count = 0;

        foreach ($users as $user) {
            $voter = Voter::updateOrCreate(
                [
                    'election_id' => $session->election_id,
                    'user_id'     => $user->id,
                ],
                [
                    'session_id' => $session->id,
                    'class_id'   => $session->class_id,
                ]
            );

            if ($voter->wasRecentlyCreated || $voter->wasChanged('session_id')) {
                $count++;
            }
        }

        return $count;
    }
}
